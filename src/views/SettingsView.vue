<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { CloudSun, HousePlug, Music2, Radio, Router, Thermometer, Waves } from 'lucide-vue-next'
import TopBar from '../components/TopBar.vue'
import { settingsApi, type IntegrationStatus } from '../services/homeService'

type IntegrationKey = 'hue' | 'tahoma' | 'netatmo' | 'spotify' | 'weather'

const darkMode = ref(true)
const keepAwake = ref(true)
const showWeather = ref(true)
const compactHome = ref(false)
const loading = ref(true)
const busy = ref<IntegrationKey | null>(null)
const error = ref('')
const tahomaToken = ref('')
const statuses = ref<Partial<Record<IntegrationKey, IntegrationStatus>>>({})

const definitions = [
  { key: 'hue' as const, name: 'Philips Hue', detail: 'Éclairage', icon: HousePlug },
  { key: 'tahoma' as const, name: 'Somfy TaHoma', detail: 'Volets', icon: Waves },
  { key: 'netatmo' as const, name: 'Netatmo', detail: 'Capteurs', icon: Thermometer },
  { key: 'spotify' as const, name: 'Spotify', detail: 'Musique', icon: Music2 },
  { key: 'weather' as const, name: 'Météo', detail: 'Prévisions', icon: CloudSun },
]

const integrations = computed(() => definitions.map((definition) => {
  const state = statuses.value[definition.key]
  const connected = state?.configured ?? false
  return {
    ...definition,
    connected,
    status: loading.value ? 'Vérification…'
      : definition.key === 'tahoma' && state?.status === 'pending' ? 'Bientôt configurable'
      : connected ? 'Connecté' : definition.key === 'weather' ? 'À renseigner sur le serveur' : 'À connecter',
  }
}))

async function refresh() {
  loading.value = true
  error.value = ''
  try {
    statuses.value = await settingsApi.integrations()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'API indisponible'
  } finally {
    loading.value = false
  }
}

async function configure(key: IntegrationKey) {
  error.value = ''
  const connected = statuses.value[key]?.configured ?? false

  if (connected && key !== 'weather') {
    busy.value = key
    try {
      if (key === 'hue') await settingsApi.disconnectHue()
      if (key === 'spotify') await settingsApi.disconnectSpotify()
      if (key === 'netatmo') await settingsApi.disconnectNetatmo()
      if (key === 'tahoma') await settingsApi.disconnectTahoma()
      await refresh()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Déconnexion impossible'
    } finally {
      busy.value = null
    }
    return
  }

  if (key === 'spotify') {
    window.location.href = settingsApi.connectSpotifyUrl
    return
  }
  if (key === 'netatmo') {
    window.location.href = settingsApi.connectNetatmoUrl
    return
  }
  if (key === 'tahoma') {
    if (!tahomaToken.value.trim()) {
      error.value = 'Saisis le token local TaHoma avant de connecter la box.'
      return
    }
    busy.value = key
    try {
      await settingsApi.configureTahoma(tahomaToken.value.trim())
      tahomaToken.value = ''
      await refresh()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Connexion TaHoma impossible'
    } finally {
      busy.value = null
    }
    return
  }

  if (key === 'hue') {
    busy.value = key
    try {
      await settingsApi.pairHue()
      await refresh()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Association Hue impossible'
    } finally {
      busy.value = null
    }
  }
}

onMounted(refresh)
</script>

<template>
  <div class="page">
    <TopBar />
    <section class="control-heading">
      <div><p class="hero-kicker">Dashboard</p><h1>Réglages</h1></div>
      <div class="hero-summary"><Router :size="21" /> Configuration locale</div>
    </section>

    <p v-if="error" class="settings-error">{{ error }}</p>

    <section class="settings-layout">
      <div>
        <div class="section-title"><div><div class="card-eyebrow">Services</div><h2>Intégrations</h2></div></div>
        <div class="integration-list">
          <article v-for="integration in integrations" :key="integration.key" class="integration-card">
            <div class="integration-icon"><component :is="integration.icon" :size="23" /></div>
            <div class="integration-info"><strong>{{ integration.name }}</strong><span>{{ integration.detail }}</span></div>
            <div class="integration-status"><span :class="{ connected: integration.connected }"></span>{{ integration.status }}</div>
            <button
              class="secondary-button"
              :disabled="integration.key === 'weather' || busy === integration.key"
              @click="configure(integration.key)"
            >
              {{ integration.connected ? 'Déconnecter' : 'Configurer' }}
            </button>
          <div v-if="integration.key === 'tahoma' && !integration.connected" class="integration-config">
              <input v-model="tahomaToken" type="password" autocomplete="off" placeholder="Token local TaHoma" aria-label="Token local TaHoma">
              <small>Généré après activation du mode développeur TaHoma.</small>
            </div>
          </article>
        </div>
      </div>

      <aside class="settings-side">
        <article class="panel">
          <div class="panel-heading"><div><div class="card-eyebrow">Tablette</div><h2>Affichage</h2></div><Radio :size="23" /></div>
          <label class="setting-row"><span><strong>Mode sombre</strong><small>Thème optimisé pour la tablette</small></span><input v-model="darkMode" type="checkbox" role="switch"></label>
          <label class="setting-row"><span><strong>Écran toujours actif</strong><small>Pour un usage en mode kiosque</small></span><input v-model="keepAwake" type="checkbox" role="switch"></label>
          <label class="setting-row"><span><strong>Afficher la météo</strong><small>Sur l'écran d'accueil</small></span><input v-model="showWeather" type="checkbox" role="switch"></label>
          <label class="setting-row"><span><strong>Vue compacte</strong><small>Réduit la taille des cartes</small></span><input v-model="compactHome" type="checkbox" role="switch"></label>
        </article>
        <article class="panel network-card">
          <div class="card-eyebrow">Système</div><h2>Dashboard local</h2>
          <p>API locale · intégrations persistantes</p>
          <div class="network-state"><span></span> {{ error ? 'API à vérifier' : 'Interface opérationnelle' }}</div>
        </article>
      </aside>
    </section>
  </div>
</template>
