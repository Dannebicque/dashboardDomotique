<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { ChevronDown, ChevronUp, Square } from 'lucide-vue-next'
import TopBar from '../components/TopBar.vue'
import { homeService } from '../services/homeService'
import type { Room, Shutter } from '../types/home'

const rooms = ref<Room[]>([])
const selectedRoomId = ref('')
const loading = ref(true)

const demoByZone: Record<string, Shutter[]> = {
  salon: [
    { id: 'demo-bay', name: 'Baie vitrée', position: 35, status: 'online' },
    { id: 'demo-window', name: 'Fenêtre salon', position: 0, status: 'online' },
  ],
  cuisine: [{ id: 'demo-kitchen', name: 'Fenêtre cuisine', position: 100, status: 'online' }],
}
const fallbackDemo: Shutter[] = [{ id: 'demo-window', name: 'Fenêtre', position: 50, status: 'online' }]

const room = computed(() => rooms.value.find((item) => item.id === selectedRoomId.value) ?? rooms.value[0])
const shutters = computed(() => room.value?.shutters.length ? room.value.shutters : (demoByZone[room.value?.id ?? ''] ?? fallbackDemo))

onMounted(async () => {
  rooms.value = await homeService.getRooms()
  selectedRoomId.value = rooms.value[0]?.id ?? ''
  loading.value = false
})

function move(shutter: Shutter, direction: 'up' | 'down' | 'stop') {
  if (shutter.status === 'offline' || direction === 'stop') return
  shutter.position = direction === 'up' ? 100 : 0
}

function closeAll() {
  shutters.value.forEach((shutter) => { if (shutter.status !== 'offline') shutter.position = 0 })
}
</script>

<template>
  <div class="page">
    <TopBar />
    <section class="control-heading">
      <div><p class="hero-kicker">Contrôle</p><h1>Volets</h1></div>
      <div v-if="rooms.length" class="room-tabs" role="tablist" aria-label="Pièces">
        <button v-for="item in rooms" :key="item.id" type="button" class="room-tab" :class="{ 'is-active': item.id === selectedRoomId }" @click="selectedRoomId = item.id">{{ item.name }}</button>
      </div>
    </section>
    <p v-if="loading">Chargement…</p>
    <section v-else-if="room" class="control-sections">
      <div class="section-title">
        <div><div class="card-eyebrow">{{ room.name }}</div><h2>Volets</h2></div>
        <button type="button" class="compact-action" @click="closeAll"><ChevronDown :size="17" /> Tout fermer</button>
      </div>
      <p v-if="!room.shutters.length" class="demo-note">Prévisualisation en attendant la connexion Somfy TaHoma.</p>
      <div class="shutter-grid">
        <article v-for="shutter in shutters" :key="shutter.id" class="shutter-card">
          <div class="shutter-top"><div><strong>{{ shutter.name }}</strong><span>{{ shutter.position === 0 ? 'Fermé' : shutter.position === 100 ? 'Ouvert' : `${shutter.position}% ouvert` }}</span></div><div class="shutter-value">{{ shutter.position }}%</div></div>
          <div class="shutter-visual"><div class="shutter-fill" :style="{ height: `${100 - shutter.position}%` }"></div></div>
          <div class="shutter-controls">
            <button type="button" aria-label="Ouvrir" @click="move(shutter, 'up')"><ChevronUp :size="24" /></button>
            <button type="button" aria-label="Stop" @click="move(shutter, 'stop')"><Square :size="19" /></button>
            <button type="button" aria-label="Fermer" @click="move(shutter, 'down')"><ChevronDown :size="24" /></button>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<style scoped>
.demo-note{margin:-5px 0 12px;font-size:.76rem;opacity:.65}.compact-action{display:flex;align-items:center;gap:6px;padding:7px 10px;border:1px solid rgba(127,127,127,.18);border-radius:10px;background:rgba(127,127,127,.07)}
</style>
