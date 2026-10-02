<script setup lang="ts">
import { Clapperboard, DoorOpen, Moon, Sun } from 'lucide-vue-next'
import { ref } from 'vue'

const active = ref<string | null>(null)
const scenes = [
  { id: 'day', label: 'Journée', detail: 'Volets ouverts', icon: Sun },
  { id: 'evening', label: 'Soirée', detail: 'Lumière douce', icon: Moon },
  { id: 'cinema', label: 'Cinéma', detail: 'Salon tamisé', icon: Clapperboard },
  { id: 'away', label: 'Départ', detail: 'Tout éteindre', icon: DoorOpen },
]

function activate(id: string) {
  active.value = id
  window.setTimeout(() => {
    active.value = null
  }, 1400)
}
</script>

<template>
  <section class="scenes-section">
    <div class="section-title scenes-title"><div><div class="card-eyebrow">Raccourcis</div><h2>Scènes</h2></div></div>
    <div class="scene-grid">
      <button v-for="scene in scenes" :key="scene.id" class="scene-card" :class="{ active: active === scene.id }" type="button" @click="activate(scene.id)">
        <span class="scene-icon"><component :is="scene.icon" :size="22" /></span>
        <span><strong>{{ scene.label }}</strong><small>{{ active === scene.id ? 'Activée' : scene.detail }}</small></span>
      </button>
    </div>
  </section>
</template>
