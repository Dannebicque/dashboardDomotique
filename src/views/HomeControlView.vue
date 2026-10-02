<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { ChevronDown, ChevronUp, Lightbulb, Square } from 'lucide-vue-next'
import SceneQuickActions from '../components/SceneQuickActions.vue'
import TopBar from '../components/TopBar.vue'
import { homeService, realHomeApi } from '../services/homeService'
import type { Light, Room } from '../types/home'

const rooms = ref<Room[]>([])
const selectedRoomId = ref('')
const loading = ref(true)
const error = ref('')

const room = computed(() => rooms.value.find((item) => item.id === selectedRoomId.value) ?? rooms.value[0])
const totalLightsOn = computed(() => rooms.value.flatMap((item) => item.lights).filter((light) => light.on).length)
const totalShuttersOpen = computed(() => rooms.value.flatMap((item) => item.shutters).filter((shutter) => shutter.position > 0).length)

onMounted(async () => {
  try {
    rooms.value = await homeService.getRooms()
    selectedRoomId.value = rooms.value[0]?.id ?? ''
  } catch {
    error.value = 'Impossible de charger les équipements de la maison.'
  } finally {
    loading.value = false
  }
})

type LightUpdate = { on?: boolean; brightness?: number; colorTemperature?: number; color?: { x: number; y: number } }

const colorPresets = [
  { name: 'Chaud', x: 0.526, y: 0.413 },
  { name: 'Rose', x: 0.45, y: 0.24 },
  { name: 'Bleu', x: 0.17, y: 0.12 },
  { name: 'Vert', x: 0.21, y: 0.71 },
]

async function updateLight(light: Light, payload: LightUpdate) {
  const previous = { on: light.on, brightness: light.brightness, status: light.status }
  light.status = 'updating'

  if (payload.on !== undefined) light.on = payload.on
  if (payload.brightness !== undefined) light.brightness = payload.brightness

  try {
    Object.assign(light, await realHomeApi.updateLight(light.id, payload))
  } catch {
    Object.assign(light, previous)
    error.value = `Impossible de piloter « ${light.name} ».`
  }
}

async function turnAllLightsOff() {
  const active = rooms.value.flatMap((item) => item.lights).filter((light) => light.on && light.status !== 'offline')
  await Promise.all(active.map((light) => updateLight(light, { on: false })))
}

function closeAllShutters() {
  rooms.value.forEach((item) => item.shutters.forEach((shutter) => { if (shutter.status !== 'offline') shutter.position = 0 }))
}

function toggleLight(light: Light) {
  if (light.status === 'offline' || light.status === 'updating') return
  void updateLight(light, { on: !light.on, ...(!light.on && light.brightness === 0 ? { brightness: 50 } : {}) })
}

function changeBrightness(light: Light) {
  if (!light.on || light.status === 'offline' || light.status === 'updating') return
  void updateLight(light, { brightness: light.brightness })
}

function changeColorTemperature(light: Light) {
  if (!light.on || light.status === 'offline' || light.status === 'updating' || light.colorTemperature == null) return
  void updateLight(light, { colorTemperature: light.colorTemperature })
}

function setColor(light: Light, x: number, y: number) {
  if (!light.on || light.status === 'offline' || light.status === 'updating') return
  void updateLight(light, { color: { x, y } })
}

function moveShutter(id: string, direction: 'up' | 'down' | 'stop') {
  const shutter = room.value?.shutters.find((item) => item.id === id)
  if (!shutter || shutter.status === 'offline') return
  if (direction === 'up') shutter.position = 100
  if (direction === 'down') shutter.position = 0
}
</script>

<template>
  <div class="page">
    <TopBar />

    <section class="control-heading">
      <div>
        <p class="hero-kicker">Contrôle</p>
        <h1>Piloter la maison</h1>
      </div>
      <div v-if="rooms.length" class="room-tabs" role="tablist" aria-label="Pièces">
        <button
          v-for="item in rooms"
          :key="item.id"
          type="button"
          class="room-tab"
          :class="{ 'is-active': item.id === selectedRoomId }"
          @click="selectedRoomId = item.id"
        >
          {{ item.name }}
        </button>
      </div>
    </section>

    <p v-if="error" role="alert">{{ error }}</p>
    <p v-if="loading">Chargement des équipements…</p>

    <div v-if="!loading && rooms.length" class="home-actions">
      <SceneQuickActions />
      <div class="global-actions">
        <button type="button" class="global-action" @click="turnAllLightsOff">
          <Lightbulb :size="20" />
          <span><strong>Tout éteindre</strong><small>{{ totalLightsOn }} allumées</small></span>
        </button>
        <button v-if="rooms.some((item) => item.shutters.length)" type="button" class="global-action" @click="closeAllShutters">
          <ChevronDown :size="20" />
          <span><strong>Fermer les volets</strong><small>{{ totalShuttersOpen }} ouverts</small></span>
        </button>
      </div>
    </div>

    <section v-if="room && !loading" class="control-sections">
      <div>
        <div class="section-title">
          <div>
            <div class="card-eyebrow">{{ room.name }}</div>
            <h2>Lumières</h2>
          </div>
          <span>{{ room.lights.filter((light) => light.on).length }} / {{ room.lights.length }} allumées</span>
        </div>

        <div class="device-grid">
          <article
            v-for="light in room.lights"
            :key="light.id"
            class="device-card"
            :class="{ 'is-on': light.on, 'is-offline': light.status === 'offline' }"
          >
            <button class="device-toggle" type="button" :disabled="light.status === 'updating'" @click="toggleLight(light)">
              <span class="device-icon"><Lightbulb :size="25" /></span>
              <span>
                <strong>{{ light.name }}</strong>
                <small>{{ light.status === 'offline' ? 'Indisponible' : light.status === 'updating' ? 'Mise à jour…' : light.on ? 'Allumée' : 'Éteinte' }}</small>
              </span>
              <span class="switch" :class="{ 'is-on': light.on }"><span></span></span>
            </button>

            <div v-if="light.capabilities?.dimming !== false" class="device-slider">
              <input
                v-model.number="light.brightness"
                type="range"
                min="0"
                max="100"
                :disabled="!light.on || light.status === 'offline' || light.status === 'updating'"
                :aria-label="`Luminosité de ${light.name}`"
                @change="changeBrightness(light)"
              >
              <span>{{ light.brightness }}%</span>
            </div>

            <div v-if="light.capabilities?.colorTemperature && light.colorTemperatureMin != null && light.colorTemperatureMax != null" class="device-slider device-slider--temperature">
              <small>Froid</small>
              <input
                v-model.number="light.colorTemperature"
                type="range"
                :min="light.colorTemperatureMin"
                :max="light.colorTemperatureMax"
                :disabled="!light.on || light.status === 'offline' || light.status === 'updating'"
                :aria-label="`Température de couleur de ${light.name}`"
                @change="changeColorTemperature(light)"
              >
              <small>Chaud</small>
            </div>

            <div v-if="light.capabilities?.color" class="light-color-presets" aria-label="Ambiances colorées">
              <button
                v-for="preset in colorPresets"
                :key="preset.name"
                type="button"
                :disabled="!light.on || light.status === 'offline' || light.status === 'updating'"
                @click="setColor(light, preset.x, preset.y)"
              >
                {{ preset.name }}
              </button>
              <span v-if="light.capabilities.gradient" class="capability-badge">Gradient</span>
            </div>
          </article>
        </div>
      </div>

      <div v-if="room.shutters.length">
        <div class="section-title">
          <div>
            <div class="card-eyebrow">{{ room.name }}</div>
            <h2>Volets</h2>
          </div>
        </div>

        <div class="shutter-grid">
          <article v-for="shutter in room.shutters" :key="shutter.id" class="shutter-card" :class="{ 'is-offline': shutter.status === 'offline' }">
            <div class="shutter-top">
              <div>
                <strong>{{ shutter.name }}</strong>
                <span>{{ shutter.position === 0 ? 'Fermé' : shutter.position === 100 ? 'Ouvert' : `${shutter.position}% ouvert` }}</span>
              </div>
              <div class="shutter-value">{{ shutter.position }}%</div>
            </div>
            <div class="shutter-visual">
              <div class="shutter-fill" :style="{ height: `${100 - shutter.position}%` }"></div>
            </div>
            <div class="shutter-controls">
              <button type="button" aria-label="Ouvrir" @click="moveShutter(shutter.id, 'up')"><ChevronUp :size="24" /></button>
              <button type="button" aria-label="Stop" @click="moveShutter(shutter.id, 'stop')"><Square :size="19" /></button>
              <button type="button" aria-label="Fermer" @click="moveShutter(shutter.id, 'down')"><ChevronDown :size="24" /></button>
            </div>
          </article>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.device-slider--temperature small{font-size:.68rem;opacity:.7;white-space:nowrap}.light-color-presets{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}.light-color-presets button,.capability-badge{border:1px solid rgba(127,127,127,.22);background:rgba(127,127,127,.08);border-radius:999px;padding:5px 9px;font-size:.7rem}.light-color-presets button:not(:disabled){cursor:pointer}.light-color-presets button:disabled{opacity:.4}.capability-badge{opacity:.65}
</style>
