<script setup lang="ts">
import { ref } from 'vue'
import { CloudSun, HousePlug, Music2, Radio, Router, Thermometer, Waves } from 'lucide-vue-next'
import TopBar from '../components/TopBar.vue'

const darkMode = ref(true)
const keepAwake = ref(true)
const showWeather = ref(true)
const compactHome = ref(false)

const integrations = [
  { name: 'Philips Hue', detail: 'Éclairage', icon: HousePlug, status: 'Prêt à connecter', connected: false },
  { name: 'Somfy TaHoma', detail: 'Volets', icon: Waves, status: 'Prêt à connecter', connected: false },
  { name: 'Netatmo', detail: 'Capteurs', icon: Thermometer, status: 'Données simulées', connected: false },
  { name: 'Spotify', detail: 'Musique', icon: Music2, status: 'Données simulées', connected: false },
  { name: 'Météo', detail: 'Prévisions', icon: CloudSun, status: 'Données simulées', connected: false },
]
</script>

<template>
  <div class="page">
    <TopBar />
    <section class="control-heading">
      <div><p class="hero-kicker">Dashboard</p><h1>Réglages</h1></div>
      <div class="hero-summary"><Router :size="21" /> Configuration locale</div>
    </section>

    <section class="settings-layout">
      <div>
        <div class="section-title"><div><div class="card-eyebrow">Services</div><h2>Intégrations</h2></div></div>
        <div class="integration-list">
          <article v-for="integration in integrations" :key="integration.name" class="integration-card">
            <div class="integration-icon"><component :is="integration.icon" :size="23" /></div>
            <div class="integration-info"><strong>{{ integration.name }}</strong><span>{{ integration.detail }}</span></div>
            <div class="integration-status"><span :class="{ connected: integration.connected }"></span>{{ integration.status }}</div>
            <button class="secondary-button">Configurer</button>
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
          <p>Version MVP · données simulées</p>
          <div class="network-state"><span></span> Interface opérationnelle</div>
        </article>
      </aside>
    </section>
  </div>
</template>
