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
const lastRecalledSceneId = ref<string | null>(null)

const demoShutters = ref([
  { id: 'demo-bay', name: 'Baie vitrée', position: 35, status: 'online' as const },
  { id: 'demo-window', name: 'Fenêtre salon', position: 0, status: 'online' as const },
  { id: 'demo-kitchen', name: 'Fenêtre cuisine', position: 100, status: 'online' as const },
])

const room = computed(() => rooms.value.find((item) => item.id === selectedRoomId.value) ?? rooms.value[0])
const totalLightsOn = computed(() => rooms.value.flatMap((item) => item.lights).filter((light) => light.on).length)
const totalShuttersOpen = computed(() => rooms.value.flatMap((item) => item.shutters).filter((shutter) => shutter.position > 0).length)
const roomScenes = computed(() => {
  const hueRoomIds = room.value?.hueRoomIds ?? [room.value?.id].filter((id): id is string => Boolean(id))
  return scenes.value.filter((scene) => scene.roomId !== null && hueRoomIds.includes(scene.roomId))
})
const activeRoomScene = computed(() => roomScenes.value.find((scene) => scene.status !== 'inactive') ?? roomScenes.value.find((scene) => scene.id === lastRecalledSceneId.value) ?? null)
const displayedShutters = computed(() => room.value?.shutters.length ? room.value.shutters : demoShutters.value)

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
  demoShutters.value.forEach((shutter) => { shutter.position = 0 })
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
    lastRecalledSceneId.value = scene.id
    await new Promise((resolve) => window.setTimeout(resolve, 250))
    const selectedZone = selectedRoomId.value
    rooms.value = await realHomeApi.rooms()
    scenes.value = await realHomeApi.scenes()
    selectedRoomId.value = rooms.value.some((item) => item.id === selectedZone) ? selectedZone : (rooms.value[0]?.id ?? '')
  } catch {
    error.value = `Impossible d’activer la scène « ${scene.name} ».`
  } finally {
    sceneLoadingId.value = ''
  }
}

function moveShutter(id: string, direction: 'up' | 'down' | 'stop') {
  const shutter = displayedShutters.value.find((item) => item.id === id)
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
        <button type="button" class="global-action" @click="closeAllShutters">
          <ChevronDown :size="20" />
          <span><strong>Fermer les volets</strong><small>{{ rooms.some((item) => item.shutters.length) ? `${totalShuttersOpen} ouverts` : 'Mode démo' }}</small></span>
        </button>
      </div>
    </div>

    <section v-if="room && !loading" class="control-sections">
      <div v-if="roomScenes.length" class="room-scenes">
        <div class="section-title scene-section-title">
          <div class="scene-heading">
            <div class="card-eyebrow">{{ room.name }}</div>
            <h2>Scènes Hue</h2>
          </div>
          <div v-if="activeRoomScene" class="active-scene-summary">
            <span class="active-scene-dot"></span>
            <span>Active : <strong>{{ activeRoomScene.name }}</strong></span>
          </div>
        </div>
        <div class="scene-grid">
          <button
            v-for="scene in roomScenes"
            :key="scene.id"
            type="button"
            class="scene-button"
            :class="{ 'is-active': scene.id === activeRoomScene?.id }"
            :disabled="Boolean(sceneLoadingId)"
            @click="recallScene(scene)"
          >
            <span>{{ scene.name }}</span>
            <small v-if="sceneLoadingId === scene.id">Activation…</small>
            <small v-else-if="scene.id === activeRoomScene?.id">●</small>
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

      <div>
        <div class="section-title">
          <div>
            <div class="card-eyebrow">{{ room.shutters.length ? room.name : 'Prévisualisation' }}</div>
            <h2>Volets</h2>
          </div>
        </div>

        <p v-if="!room.shutters.length" class="demo-note">Commandes fictives en attendant la connexion Somfy TaHoma.</p>
        <div class="shutter-grid">
          <article v-for="shutter in displayedShutters" :key="shutter.id" class="shutter-card" :class="{ 'is-offline': shutter.status === 'offline' }">
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
.device-slider--temperature small{font-size:.68rem;opacity:.7;white-space:nowrap}.light-color-control{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:10px}.light-color-control label{display:flex;align-items:center;gap:8px;font-size:.72rem;opacity:.9}.light-color-control input[type="color"]{width:42px;height:30px;padding:2px;border:1px solid rgba(127,127,127,.25);border-radius:8px;background:transparent}.capability-badge{border:1px solid rgba(127,127,127,.22);background:rgba(127,127,127,.08);border-radius:999px;padding:5px 9px;font-size:.7rem;opacity:.65}.room-scenes{margin-bottom:12px}.scene-section-title{margin-bottom:7px}.scene-heading{display:flex;align-items:baseline;gap:8px}.scene-heading .card-eyebrow{margin:0}.scene-heading h2{font-size:1rem;margin:0}.scene-grid{display:flex;gap:7px;overflow-x:auto;padding:2px 2px 6px;scrollbar-width:thin;overscroll-behavior-x:contain}.scene-button{display:flex;flex:0 0 auto;align-items:center;gap:6px;min-height:36px;padding:7px 11px;border:1px solid rgba(127,127,127,.18);border-radius:13px;background:rgba(127,127,127,.07);text-align:left}.scene-button span{font-weight:650;white-space:nowrap;font-size:.78rem}.scene-button small{font-size:.68rem;opacity:.8}.scene-button.is-active{border-color:currentColor;background:rgba(127,127,127,.16);box-shadow:inset 0 0 0 2px currentColor,0 5px 16px rgba(0,0,0,.08);transform:translateY(-1px)}.scene-button.is-active span{font-weight:800}.scene-button.is-active small{opacity:1;font-weight:700}.active-scene-summary{display:flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;background:rgba(127,127,127,.1);font-size:.74rem}.active-scene-dot{width:8px;height:8px;border-radius:50%;background:currentColor;box-shadow:0 0 0 4px rgba(127,127,127,.12)}.demo-note{margin:-5px 0 12px;font-size:.76rem;opacity:.65}.scene-button:not(:disabled){cursor:pointer}.scene-button:disabled{opacity:.55}
</style>
