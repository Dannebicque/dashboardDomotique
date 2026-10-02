<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { ChevronDown, ChevronUp, Lightbulb, Square } from 'lucide-vue-next'
import SceneQuickActions from '../components/SceneQuickActions.vue'
import TopBar from '../components/TopBar.vue'
import { rooms as initialRooms } from '../data/mock'
import type { Room } from '../types/home'

const rooms = reactive<Room[]>(structuredClone(initialRooms))
const selectedRoomId = ref(rooms[0]?.id ?? '')
const room = computed(() => rooms.find((item) => item.id === selectedRoomId.value) ?? rooms[0])

function toggleLight(id: string) {
  const light = room.value?.lights.find((item) => item.id === id)
  if (!light) return
  light.on = !light.on
  if (light.on && light.brightness === 0) light.brightness = 50
}

function moveShutter(id: string, direction: 'up' | 'down' | 'stop') {
  const shutter = room.value?.shutters.find((item) => item.id === id)
  if (!shutter) return
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
      <div class="room-tabs" role="tablist" aria-label="Pièces">
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

    <SceneQuickActions />

    <section v-if="room" class="control-sections">
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
            :class="{ 'is-on': light.on }"
          >
            <button class="device-toggle" type="button" @click="toggleLight(light.id)">
              <span class="device-icon"><Lightbulb :size="25" /></span>
              <span>
                <strong>{{ light.name }}</strong>
                <small>{{ light.on ? 'Allumée' : 'Éteinte' }}</small>
              </span>
              <span class="switch" :class="{ 'is-on': light.on }"><span></span></span>
            </button>

            <div class="device-slider">
              <input
                v-model.number="light.brightness"
                type="range"
                min="0"
                max="100"
                :disabled="!light.on"
                :aria-label="`Luminosité de ${light.name}`"
              >
              <span>{{ light.brightness }}%</span>
            </div>
          </article>
        </div>
      </div>

      <div>
        <div class="section-title">
          <div>
            <div class="card-eyebrow">{{ room.name }}</div>
            <h2>Volets</h2>
          </div>
        </div>

        <div class="shutter-grid">
          <article v-for="shutter in room.shutters" :key="shutter.id" class="shutter-card">
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
