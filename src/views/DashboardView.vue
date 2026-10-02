<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { CloudRain, Droplets, House, Lightbulb, Wind } from 'lucide-vue-next'
import SpotifyCard from '../components/SpotifyCard.vue'
import TopBar from '../components/TopBar.vue'
import { homeService, realHomeApi, type ClimateSensor, type DashboardData } from '../services/homeService'
import { indoor as fallbackIndoor, outdoor as fallbackOutdoor, rooms as fallbackRooms, spotify as fallbackSpotify, upstairs as fallbackUpstairs, weather as fallbackWeather } from '../data/mock'

const dashboard = ref<DashboardData>({ indoor: structuredClone(fallbackIndoor), outdoor: structuredClone(fallbackOutdoor), upstairs: structuredClone(fallbackUpstairs), weather: structuredClone(fallbackWeather), spotify: structuredClone(fallbackSpotify), rooms: structuredClone(fallbackRooms) })
const climate = ref<ClimateSensor[]>([])
const hueRooms = ref(fallbackRooms)
const spotify = computed(() => dashboard.value.spotify)
const weather = computed(() => dashboard.value.weather)
const lightsOn = computed(() => hueRooms.value.flatMap((room) => room.lights).filter((light) => light.on).length)
const shuttersOpen = computed(() => dashboard.value.rooms.flatMap((room) => room.shutters).filter((shutter) => shutter.position > 0).length)
const temperatures = computed(() => {
  const wanted = ['salon', 'etage', 'étage', 'jardin']
  return wanted.map((name) => climate.value.find((sensor) => sensor.name.toLowerCase().includes(name) && sensor.temperature !== null)).filter((sensor, index, list) => sensor && list.indexOf(sensor) === index).slice(0, 3) as ClimateSensor[]
})
const wind = computed(() => climate.value.find((sensor) => sensor.kind === 'wind'))

async function refreshDashboard() {
  const [data, climateData, roomsData] = await Promise.all([homeService.getDashboard(), homeService.getClimate(), homeService.getRooms()])
  dashboard.value = data
  climate.value = climateData.items
  hueRooms.value = roomsData
}
let refreshTimer: number | undefined
let hueEvents: EventSource | undefined

async function refreshHue() {
  hueRooms.value = await homeService.getRooms()
}

function refreshWhenVisible() {
  if (document.visibilityState === 'visible') void refreshDashboard()
}

onMounted(() => {
  void refreshDashboard()
  refreshTimer = window.setInterval(() => {
    if (document.visibilityState === 'visible') void refreshDashboard()
  }, 5 * 60 * 1000)
  document.addEventListener('visibilitychange', refreshWhenVisible)
  hueEvents = realHomeApi.hueEvents(() => { void refreshHue() })
})

onBeforeUnmount(() => {
  if (refreshTimer !== undefined) window.clearInterval(refreshTimer)
  document.removeEventListener('visibilitychange', refreshWhenVisible)
  hueEvents?.close()
})
</script>

<template>
  <div class="page dashboard-page">
    <TopBar />
    <section class="hero">
      <div><p class="hero-kicker">Bonjour</p><h1>La maison est <span>au calme.</span></h1></div>
      <div class="hero-summary"><House :size="22" /><span>Tout semble normal</span></div>
    </section>

    <section class="home-overview">
      <article class="panel weather-card">
        <div class="panel-heading">
          <div><div class="card-eyebrow">Prévisions</div><h2>Météo</h2></div>
          <CloudRain :size="26" />
        </div>
        <div class="weather-main">
          <div><div class="weather-temperature">{{ weather.temperature }}°</div><div class="weather-label">{{ weather.label }}</div></div>
          <div class="weather-range">{{ weather.min }}° / {{ weather.max }}°</div>
        </div>
        <div class="hourly-list">
          <div v-for="hour in weather.hourly" :key="hour.time" class="hourly-item"><span>{{ hour.time }}</span><strong>{{ hour.temperature }}°</strong></div>
        </div>
      </article>

      <div class="home-climate-summary">
        <RouterLink v-for="sensor in temperatures" :key="sensor.id" to="/climat" class="panel climate-zone">
          <div class="card-eyebrow">{{ sensor.kind === 'outdoor' ? 'Extérieur · Netatmo' : 'Intérieur · Netatmo' }}</div>
          <h2>{{ sensor.name }}</h2>
          <div class="climate-zone-value">{{ sensor.temperature?.toFixed(1) }}°</div>
          <div class="climate-zone-meta">
            <span><Droplets :size="14" /> {{ sensor.humidity ?? '—' }}%</span>
            <span v-if="sensor.co2 !== null">{{ sensor.co2 }} ppm CO₂</span>
          </div>
        </RouterLink>
        <RouterLink to="/climat" class="panel climate-zone wind-summary" :class="{ offline: wind && !wind.reachable }">
          <div class="card-eyebrow">Extérieur · Netatmo</div>
          <h2>Vent</h2>
          <div class="wind-summary-value"><Wind :size="28" /><strong>{{ wind?.windStrength !== null && wind?.windStrength !== undefined ? wind.windStrength + ' km/h' : '—' }}</strong></div>
          <div class="climate-zone-meta">
            <span v-if="wind?.gustStrength !== null && wind?.gustStrength !== undefined">Rafales {{ wind.gustStrength }} km/h</span>
            <span v-else>{{ wind?.reachable === false ? 'Capteur hors ligne' : 'Mesure indisponible' }}</span>
          </div>
        </RouterLink>
      </div>
    </section>

    <section class="dashboard-grid dashboard-grid--compact">
      <SpotifyCard :track="spotify" @refresh="refreshDashboard" />
      <article class="panel home-state-card">
        <div class="panel-heading"><div><div class="card-eyebrow">Maison</div><h2>État rapide</h2></div></div>
        <div class="state-row"><div class="state-icon state-icon--light"><Lightbulb :size="22" /></div><div><strong>{{ lightsOn }} lumières</strong><span>allumées</span></div></div>
        <div class="state-row"><div class="state-icon"><House :size="22" /></div><div><strong>{{ shuttersOpen }} volets</strong><span>ouverts ou partiels</span></div></div>
        <RouterLink to="/eclairage" class="primary-button">Piloter l’éclairage</RouterLink>
      </article>
    </section>
  </div>
</template>
