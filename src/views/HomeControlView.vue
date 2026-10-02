<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { ChevronDown, ChevronUp, Lightbulb, Square } from 'lucide-vue-next'
import SceneQuickActions from '../components/SceneQuickActions.vue'
import TopBar from '../components/TopBar.vue'
import { homeService, realHomeApi } from '../services/homeService'
import type { HueScene } from '../services/homeService'
import type { Light, Room } from '../types/home'

const rooms = ref<Room[]>([])
const selectedRoomId = ref('')
const loading = ref(true)
const error = ref('')
const scenes = ref<HueScene[]>([])
const sceneLoadingId = ref('')

const room = computed(() => rooms.value.find((item) => item.id === selectedRoomId.value) ?? rooms.value[0])
const totalLightsOn = computed(() => rooms.value.flatMap((item) => item.lights).filter((light) => light.on).length)
const totalShuttersOpen = computed(() => rooms.value.flatMap((item) => item.shutters).filter((shutter) => shutter.position > 0).length)
const roomScenes = computed(() => scenes.value.filter((scene) => scene.roomId === room.value?.id))

onMounted(async () => {
  try {
    const [loadedRooms, loadedScenes] = await Promise.all([homeService.getRooms(), realHomeApi.scenes()])
    rooms.value = loadedRooms
    scenes.value = loadedScenes
    selectedRoomId.value = rooms.value[0]?.id ?? ''
  } catch {
    error.value = 'Impossible de charger les équipements de la maison.'
  } finally {
    loading.value = false
  }
})

type LightUpdate = { on?: boolean; brightness?: number; colorTemperature?: number; color?: { x: number; y: number } }

function hexToXy(hex: string): { x: number; y: number } {
  const value = hex.replace('#', '')
  const srgb = [0, 2, 4].map((offset) => parseInt(value.slice(offset, offset + 2), 16) / 255)
  const [r, g, b] = srgb.map((channel) => channel > 0.04045 ? Math.pow((channel + 0.055) / 1.055, 2.4) : channel / 12.92)
  const X = r * 0.664511 + g * 0.154324 + b * 0.162028
  const Y = r * 0.283881 + g * 0.668433 + b * 0.047685
  const Z = r * 0.000088 + g * 0.072310 + b * 0.986039
  const total = X + Y + Z
  return total === 0 ? { x: 0.3227, y: 0.329 } : { x: X / total, y: Y / total }
}

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

function setColor(light: Light, event: Event) {
  if (!light.on || light.status === 'offline' || light.status === 'updating') return
  const input = event.target as HTMLInputElement
  void updateLight(light, { color: hexToXy(input.value) })
}

async function recallScene(scene: HueScene) {
  if (sceneLoadingId.value) return
  sceneLoadingId.value = scene.id
  error.value = ''
  try {
    await realHomeApi.recallScene(scene.id)
    await new Promise((resolve) => window.setTimeout(resolve, 250))
    rooms.value = await realHomeApi.rooms()
    scenes.value = await realHomeApi.scenes()
  } catch {
    error.value = `Impossible d’activer la scène « ${scene.name} ».`
  } finally {
    sceneLoadingId.value = ''
  }
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
      <div v-if="roomScenes.length" class="room-scenes">
        <div class="section-title">
          <div>
            <div class="card-eyebrow">{{ room.name }}</div>
            <h2>Scènes Hue</h2>
          </div>
        </div>
        <div class="scene-grid">
          <button
            v-for="scene in roomScenes"
            :key="scene.id"
            type="button"
            class="scene-button"
            :class="{ 'is-active': scene.status === 'static' || scene.status === 'dynamic_palette' }"
            :disabled="Boolean(sceneLoadingId)"
            @click="recallScene(scene)"
          >
            <span>{{ scene.name }}</span>
            <small>{{ sceneLoadingId === scene.id ? 'Activation…' : scene.status === 'inactive' ? 'Activer' : 'Active' }}</small>
          </button>
        </div>
      </div>

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

            <div v-if="light.capabilities?.color" class="light-color-control">
              <label>
                <span>Couleur</span>
                <input
                  type="color"
                  value="#ffb45c"
                  :disabled="!light.on || light.status === 'offline' || light.status === 'updating'"
                  :aria-label="`Couleur de ${light.name}`"
                  @change="setColor(light, $event)"
                >
              </label>
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
.device-slider--temperature small{font-size:.68rem;opacity:.7;white-space:nowrap}.light-color-control{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:10px}.light-color-control label{display:flex;align-items:center;gap:8px;font-size:.72rem;opacity:.9}.light-color-control input[type="color"]{width:42px;height:30px;padding:2px;border:1px solid rgba(127,127,127,.25);border-radius:8px;background:transparent}.capability-badge{border:1px solid rgba(127,127,127,.22);background:rgba(127,127,127,.08);border-radius:999px;padding:5px 9px;font-size:.7rem;opacity:.65}.room-scenes{margin-bottom:18px}.scene-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(135px,1fr));gap:9px}.scene-button{display:flex;flex-direction:column;align-items:flex-start;gap:3px;min-height:58px;padding:11px 13px;border:1px solid rgba(127,127,127,.18);border-radius:13px;background:rgba(127,127,127,.07);text-align:left}.scene-button span{font-weight:650}.scene-button small{font-size:.68rem;opacity:.65}.scene-button.is-active{box-shadow:inset 0 0 0 1px currentColor}.scene-button:not(:disabled){cursor:pointer}.scene-button:disabled{opacity:.55}
</style>
