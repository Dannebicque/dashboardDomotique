<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { CloudRain, Droplets, Gauge, House, Lightbulb, Wind } from 'lucide-vue-next'
import MetricCard from '../components/MetricCard.vue'
import SpotifyCard from '../components/SpotifyCard.vue'
import TopBar from '../components/TopBar.vue'
import { homeService, type DashboardData } from '../services/homeService'
import { indoor as fallbackIndoor, outdoor as fallbackOutdoor, rooms as fallbackRooms, spotify as fallbackSpotify, upstairs as fallbackUpstairs, weather as fallbackWeather } from '../data/mock'

const dashboard = ref<DashboardData>({
  indoor: structuredClone(fallbackIndoor),
  outdoor: structuredClone(fallbackOutdoor),
  upstairs: structuredClone(fallbackUpstairs),
  weather: structuredClone(fallbackWeather),
  spotify: structuredClone(fallbackSpotify),
  rooms: structuredClone(fallbackRooms),
})

const indoor = computed(() => dashboard.value.indoor)
const outdoor = computed(() => dashboard.value.outdoor)
const upstairs = computed(() => dashboard.value.upstairs)
const weather = computed(() => dashboard.value.weather)
const spotify = computed(() => dashboard.value.spotify)
const lightsOn = computed(() => dashboard.value.rooms.flatMap((room) => room.lights).filter((light) => light.on).length)
const shuttersOpen = computed(() => dashboard.value.rooms.flatMap((room) => room.shutters).filter((shutter) => shutter.position > 0).length)

async function refreshDashboard() { dashboard.value = await homeService.getDashboard() }

onMounted(refreshDashboard)
</script>

<template>
  <div class="page dashboard-page">
    <TopBar />

    <section class="hero">
      <div>
        <p class="hero-kicker">Bonjour</p>
        <h1>La maison est <span>au calme.</span></h1>
      </div>
      <div class="hero-summary">
        <House :size="22" />
        <span>Tout semble normal</span>
      </div>
    </section>

    <section class="dashboard-grid">
      <article class="panel weather-card">
        <div class="panel-heading">
          <div>
            <div class="card-eyebrow">Prévisions</div>
            <h2>Météo</h2>
          </div>
          <CloudRain :size="26" />
        </div>
        <div class="weather-main">
          <div>
            <div class="weather-temperature">{{ weather.temperature }}°</div>
            <div class="weather-label">{{ weather.label }}</div>
          </div>
          <div class="weather-range">{{ weather.min }}° / {{ weather.max }}°</div>
        </div>
        <div class="hourly-list">
          <div v-for="hour in weather.hourly" :key="hour.time" class="hourly-item">
            <span>{{ hour.time }}</span>
            <strong>{{ hour.temperature }}°</strong>
          </div>
        </div>
      </article>

      <div class="metrics-stack">
        <MetricCard
          eyebrow="Salon · Netatmo"
          :value="`${indoor.temperature}°`"
          label="Température intérieure"
          :meta="`${indoor.humidity}% humidité · Étage ${upstairs.temperature}°`"
          tone="success"
        />
        <MetricCard
          eyebrow="Qualité de l’air"
          :value="`${indoor.co2} ppm`"
          label="CO₂ · Excellent"
          :meta="`${indoor.pressure} hPa`"
          tone="success"
        />
      </div>

      <article class="panel outside-card">
        <div class="panel-heading">
          <div>
            <div class="card-eyebrow">Capteur extérieur</div>
            <h2>Dehors</h2>
          </div>
          <Wind :size="26" />
        </div>
        <div class="outside-value">{{ outdoor.temperature }}°</div>
        <div class="outside-stats">
          <span><Droplets :size="17" /> {{ outdoor.humidity }}%</span>
          <span><Gauge :size="17" /> stable</span>
        </div>
      </article>

      <SpotifyCard :track="spotify" @refresh="refreshDashboard" />

      <article class="panel home-state-card">
        <div class="panel-heading">
          <div>
            <div class="card-eyebrow">Maison</div>
            <h2>État rapide</h2>
          </div>
        </div>
        <div class="state-row">
          <div class="state-icon state-icon--light"><Lightbulb :size="22" /></div>
          <div>
            <strong>{{ lightsOn }} lumières</strong>
            <span>allumées</span>
          </div>
        </div>
        <div class="state-row">
          <div class="state-icon"><House :size="22" /></div>
          <div>
            <strong>{{ shuttersOpen }} volets</strong>
            <span>ouverts ou partiels</span>
          </div>
        </div>
        <RouterLink to="/maison" class="primary-button">Piloter la maison</RouterLink>
      </article>
    </section>
  </div>
</template>
