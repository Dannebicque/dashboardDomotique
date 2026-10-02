<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Activity, Battery, CloudRain, CloudSun, Droplets, Gauge, Thermometer, Volume2, Wind } from 'lucide-vue-next'
import TopBar from '../components/TopBar.vue'
import { homeService, type ClimateSensor } from '../services/homeService'

const sensors = ref<ClimateSensor[]>([])
const available = ref(false)
const ordered = computed(() => [...sensors.value].sort((a, b) => {
  const order = ['salon', 'etage', 'étage', 'jardin', 'vent', 'pluie']
  const rank = (sensor: ClimateSensor) => {
    const index = order.findIndex((part) => sensor.name.toLowerCase().includes(part))
    return index === -1 ? 99 : index
  }
  return rank(a) - rank(b)
}))
function trendLabel(trend: string | null) { return trend === 'up' ? 'En hausse' : trend === 'down' ? 'En baisse' : trend === 'stable' ? 'Stable' : '—' }
function updatedAt(timestamp: number | null) {
  if (!timestamp) return 'Dernière mesure inconnue'
  return 'Mesure à ' + new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(timestamp * 1000))
}
function airLabel(co2: number | null) {
  if (co2 === null) return null
  if (co2 < 800) return 'Excellent'
  if (co2 < 1000) return 'Bon'
  if (co2 < 1400) return 'À aérer'
  return 'Élevé'
}
function kindLabel(sensor: ClimateSensor) {
  if (sensor.kind === 'wind') return 'Anémomètre'
  if (sensor.kind === 'rain') return 'Pluviomètre'
  return sensor.kind === 'outdoor' ? 'Extérieur' : 'Intérieur'
}
async function refresh() {
  const data = await homeService.getClimate()
  available.value = data.available
  sensors.value = data.items
}
onMounted(refresh)
</script>

<template>
  <div class="page climate-page">
    <TopBar />
    <section class="hero">
      <div><p class="hero-kicker">Netatmo</p><h1>Climat de la <span>maison.</span></h1></div>
      <div class="hero-summary"><CloudSun :size="22" /><span>{{ available ? 'Capteurs connectés' : 'Données indisponibles' }}</span></div>
    </section>

    <section class="climate-grid">
      <article v-for="sensor in ordered" :key="sensor.id" class="panel climate-card" :class="{ 'climate-card--offline': !sensor.reachable }">
        <div class="panel-heading">
          <div><div class="card-eyebrow">{{ sensor.moduleType }} · {{ kindLabel(sensor) }}</div><h2>{{ sensor.name }}</h2></div>
          <div class="climate-status" :class="{ offline: !sensor.reachable }"><span class="climate-status-dot"></span>{{ sensor.reachable ? 'En ligne' : 'Hors ligne' }}</div>
        </div>

        <template v-if="sensor.kind === 'wind'">
          <div class="climate-card-main"><div class="climate-temperature climate-temperature--icon"><Wind :size="34" /> {{ sensor.windStrength !== null ? sensor.windStrength + ' km/h' : '—' }}</div></div>
          <div class="climate-measures">
            <div class="climate-measure"><span>Vent moyen</span><strong>{{ sensor.windStrength !== null ? sensor.windStrength + ' km/h' : '—' }}</strong></div>
            <div class="climate-measure"><span>Direction</span><strong>{{ sensor.windAngle !== null ? sensor.windAngle + '°' : '—' }}</strong></div>
            <div class="climate-measure"><span>Rafales</span><strong>{{ sensor.gustStrength !== null ? sensor.gustStrength + ' km/h' : '—' }}</strong></div>
          </div>
        </template>

        <template v-else-if="sensor.kind === 'rain'">
          <div class="climate-card-main"><div class="climate-temperature climate-temperature--icon"><CloudRain :size="34" /> {{ sensor.rain !== null ? sensor.rain + ' mm' : '—' }}</div></div>
          <div class="climate-measures">
            <div class="climate-measure"><span>Pluie actuelle</span><strong>{{ sensor.rain !== null ? sensor.rain + ' mm' : '—' }}</strong></div>
            <div class="climate-measure"><span>Cumul 24 h</span><strong>{{ sensor.rain24h !== null ? sensor.rain24h + ' mm' : '—' }}</strong></div>
          </div>
        </template>

        <template v-else>
          <div class="climate-card-main">
            <div class="climate-temperature">{{ sensor.temperature !== null ? sensor.temperature.toFixed(1) + '°' : '—' }}</div>
            <div class="weather-range">{{ sensor.minTemperature ?? '—' }}° / {{ sensor.maxTemperature ?? '—' }}°</div>
          </div>
          <div class="climate-measures">
            <div class="climate-measure"><span><Droplets :size="13" /> Humidité</span><strong>{{ sensor.humidity ?? '—' }} %</strong></div>
            <div v-if="sensor.co2 !== null" class="climate-measure"><span><Activity :size="13" /> CO₂</span><strong>{{ sensor.co2 }} ppm · {{ airLabel(sensor.co2) }}</strong></div>
            <div v-if="sensor.pressure !== null" class="climate-measure"><span><Gauge :size="13" /> Pression</span><strong>{{ sensor.pressure }} hPa</strong></div>
            <div v-if="sensor.noise !== null" class="climate-measure"><span><Volume2 :size="13" /> Bruit</span><strong>{{ sensor.noise }} dB</strong></div>
            <div class="climate-measure"><span><Thermometer :size="13" /> Tendance</span><strong>{{ trendLabel(sensor.temperatureTrend) }}</strong></div>
          </div>
        </template>

        <div class="climate-footer">
          <span>{{ updatedAt(sensor.lastSeen) }}</span>
          <span v-if="sensor.batteryPercent !== null" class="climate-battery" :class="{ low: sensor.batteryPercent < 20 }"><Battery :size="13" /> {{ sensor.batteryPercent }} %</span>
        </div>
      </article>
    </section>
  </div>
</template>
