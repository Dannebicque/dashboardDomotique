<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { Pause, Play, SkipBack, SkipForward } from 'lucide-vue-next'
import type { SpotifySnapshot } from '../types/home'
import { integrationApi } from '../services/homeService'

const props = defineProps<{ track: SpotifySnapshot }>()
const emit = defineEmits<{ refresh: [] }>()
const busy = ref(false)
const optimisticPlaying = ref<boolean | null>(null)
const displayedPlaying = computed(() => optimisticPlaying.value ?? props.track.isPlaying)
const liveProgressMs = ref(props.track.progressMs)
let timer: number | undefined

function syncTimer() {
  if (timer) window.clearInterval(timer)
  timer = undefined
  liveProgressMs.value = props.track.progressMs
  if (displayedPlaying.value) {
    timer = window.setInterval(() => {
      liveProgressMs.value = Math.min(liveProgressMs.value + 1000, props.track.durationMs)
    }, 1000)
  }
}

watch(() => [props.track.progressMs, props.track.durationMs, displayedPlaying.value], syncTimer, { immediate: true })
onBeforeUnmount(() => { if (timer) window.clearInterval(timer) })

const progress = computed(() => props.track.durationMs > 0 ? Math.min(100, (liveProgressMs.value / props.track.durationMs) * 100) : 0)

async function command(action: 'play' | 'pause' | 'next' | 'previous') {
  if (busy.value) return
  busy.value = true
  const changesPlayingState = action === 'play' || action === 'pause'
  if (changesPlayingState) {
    optimisticPlaying.value = action === 'play'
    syncTimer()
  }
  try {
    await integrationApi.spotifyCommand(action)
    if (changesPlayingState) {
      window.setTimeout(() => {
        optimisticPlaying.value = null
        emit('refresh')
      }, 3000)
    } else {
      window.setTimeout(() => emit('refresh'), 900)
    }
  } catch (error) {
    optimisticPlaying.value = null
    throw error
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <article class="panel spotify-card">
    <div class="panel-heading">
      <div>
        <div class="card-eyebrow">Spotify</div>
        <h2>{{ displayedPlaying ? 'En cours de lecture' : 'Lecture en pause' }}</h2>
      </div>
      <span class="service-chip">{{ track.connected === false ? 'À connecter' : 'Connecté' }}</span>
    </div>

    <div class="now-playing">
      <img :src="track.coverUrl" :alt="`Pochette de ${track.album}`" class="album-cover">
      <div class="track-info">
        <div class="track-title">{{ track.title }}</div>
        <div class="track-artist">{{ track.artist }}</div>
        <div class="track-album">{{ track.album }}</div>
        <div v-if="track.deviceName" class="track-album">Sur {{ track.deviceName }}<template v-if="track.volumePercent != null"> · {{ track.volumePercent }}%</template></div>

        <div class="progress-track" aria-label="Progression de la lecture">
          <span :style="{ width: `${progress}%` }"></span>
        </div>

        <div class="player-controls">
          <button :disabled="busy" aria-label="Titre précédent" @click="command('previous')"><SkipBack :size="18" /></button>
          <button :disabled="busy" class="play-button" :aria-label="displayedPlaying ? 'Pause' : 'Lecture'" @click="command(displayedPlaying ? 'pause' : 'play')">
            <Pause v-if="displayedPlaying" :size="20" />
            <Play v-else :size="20" />
          </button>
          <button :disabled="busy" aria-label="Titre suivant" @click="command('next')"><SkipForward :size="18" /></button>
        </div>
      </div>
    </div>
  </article>
</template>
