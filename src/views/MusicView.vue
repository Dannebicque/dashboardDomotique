<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Airplay, Heart, ListMusic, Pause, Play, Repeat2, Shuffle, SkipBack, SkipForward, Volume2 } from 'lucide-vue-next'
import TopBar from '../components/TopBar.vue'
import { integrationApi, type SpotifyPlayback, type SpotifyTrackApi } from '../services/homeService'
import { spotify as fallbackSpotify } from '../data/mock'

const playback = ref<SpotifyPlayback | null>(null)
const queue = ref<Array<{ title: string; artist: string; duration: string }>>([])
const busy = ref(false)
const devices = ref<Array<{ id: string; name: string; type: string; is_active: boolean; volume_percent: number | null }>>([])
const liveProgressMs = ref(0)
let progressTimer: number | undefined
let syncTimer: number | undefined
let playbackGuardUntil = 0
let expectedPlaying: boolean | null = null

const isPlaying = computed(() => playback.value?.is_playing ?? false)
const shuffleEnabled = computed(() => Boolean((playback.value as SpotifyPlayback & { shuffle_state?: boolean } | null)?.shuffle_state))
const repeatState = computed(() => ((playback.value as SpotifyPlayback & { repeat_state?: 'off' | 'context' | 'track' } | null)?.repeat_state ?? 'off'))
const current = computed(() => {
  const item = playback.value?.item
  return item ? {
    title: item.name,
    artist: item.artists?.map((artist) => artist.name).join(', ') ?? '',
    album: item.album?.name ?? '',
    coverUrl: item.album?.images?.[0]?.url ?? fallbackSpotify.coverUrl,
    durationMs: item.duration_ms,
  } : fallbackSpotify
})
const volume = computed(() => playback.value?.device?.volume_percent ?? 0)
const progressMs = computed(() => liveProgressMs.value)
const progress = computed(() => current.value.durationMs > 0 ? Math.min(100, (progressMs.value / current.value.durationMs) * 100) : 0)

function syncProgressTimer() {
  if (progressTimer) window.clearInterval(progressTimer)
  progressTimer = undefined
  liveProgressMs.value = playback.value?.progress_ms ?? 0
  if (isPlaying.value) {
    progressTimer = window.setInterval(() => {
      liveProgressMs.value = Math.min(liveProgressMs.value + 1000, current.value.durationMs)
    }, 1000)
  }
}

watch(() => [playback.value?.progress_ms, playback.value?.is_playing, playback.value?.item?.duration_ms], syncProgressTimer)
const deviceName = computed(() => playback.value?.device?.name ?? 'Aucun appareil')
const deviceType = computed(() => playback.value?.device?.type ?? 'Spotify Connect')

function formatTime(ms: number) {
  const seconds = Math.floor(ms / 1000)
  return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`
}

function mapQueue(items: SpotifyTrackApi[] = []) {
  return items.slice(0, 8).map((item) => ({
    title: item.name,
    artist: item.artists?.map((artist) => artist.name).join(', ') ?? '',
    duration: formatTime(item.duration_ms),
  }))
}

async function refresh() {
  const [status, queueData, deviceData] = await Promise.all([integrationApi.spotify(), integrationApi.spotifyQueue(), integrationApi.spotifyDevices()])
  const incoming = status.playback
  if (incoming) {
    const guarded = expectedPlaying !== null && Date.now() < playbackGuardUntil
    playback.value = guarded ? { ...incoming, is_playing: expectedPlaying } : incoming
  } else {
    playback.value = incoming
  }
  queue.value = mapQueue(queueData.queue)
  devices.value = deviceData.devices ?? []
}

async function command(action: 'play' | 'pause' | 'next' | 'previous') {
  if (busy.value) return
  busy.value = true

  const previousPlayback = playback.value
  const changesPlayingState = action === 'play' || action === 'pause'

  if (changesPlayingState) {
    expectedPlaying = action === 'play'
    playbackGuardUntil = Date.now() + 5000
    if (playback.value) {
      playback.value = { ...playback.value, is_playing: expectedPlaying }
    }
    syncProgressTimer()
  }

  try {
    await integrationApi.spotifyCommand(action)
    if (changesPlayingState) {
      window.setTimeout(async () => {
        try {
          const status = await integrationApi.spotify()
          if (status.playback?.is_playing === expectedPlaying) {
            playback.value = status.playback
            expectedPlaying = null
            playbackGuardUntil = 0
            syncProgressTimer()
          }
        } catch {
          // Keep the local state until the normal refresh cycle.
        }
      }, 2500)
    } else {
      window.setTimeout(() => void refresh(), 900)
    }
  } catch (error) {
    expectedPlaying = null
    playbackGuardUntil = 0
    playback.value = previousPlayback
    syncProgressTimer()
    throw error
  } finally {
    busy.value = false
  }
}

async function toggleShuffle() {
  await integrationApi.spotifyShuffle(!shuffleEnabled.value)
  await refresh()
}

async function cycleRepeat() {
  const next = repeatState.value === 'off' ? 'context' : repeatState.value === 'context' ? 'track' : 'off'
  await integrationApi.spotifyRepeat(next)
  await refresh()
}

async function changeVolume(event: Event) {
  const value = Number((event.target as HTMLInputElement).value)
  await integrationApi.spotifyVolume(value)
  await refresh()
}

async function changeDevice() {
  const available = devices.value.filter((device) => device.id)
  if (available.length < 2) return
  const activeIndex = available.findIndex((device) => device.is_active)
  const next = available[(activeIndex + 1) % available.length]
  await integrationApi.spotifyTransfer(next.id)
  await new Promise((resolve) => window.setTimeout(resolve, 650))
  await refresh()
}

onMounted(() => {
  void refresh()
  syncTimer = window.setInterval(() => {
    if (expectedPlaying === null || Date.now() >= playbackGuardUntil) {
      expectedPlaying = null
      void refresh()
    }
  }, 15000)
})

onBeforeUnmount(() => {
  if (progressTimer) window.clearInterval(progressTimer)
  if (syncTimer) window.clearInterval(syncTimer)
})
</script>

<template>
  <div class="page">
    <TopBar />
    <section class="control-heading">
      <div><p class="hero-kicker">Spotify</p><h1>Musique</h1></div>
      <div class="service-chip">{{ deviceName }}</div>
    </section>

    <section class="music-layout">
      <article class="panel music-player">
        <img :src="current.coverUrl" :alt="`Pochette de ${current.album}`" class="music-cover">
        <div class="music-player-body">
          <div class="card-eyebrow">Lecture en cours</div>
          <h2 class="music-title">{{ current.title }}</h2>
          <p class="music-artist">{{ current.artist }} · {{ current.album }}</p>
          <div class="music-progress"><span :style="{ width: `${progress}%` }"></span></div>
          <div class="music-time"><span>{{ formatTime(progressMs) }}</span><span>{{ formatTime(current.durationMs) }}</span></div>
          <div class="music-controls">
            <button :aria-pressed="shuffleEnabled" aria-label="Lecture aléatoire" @click="toggleShuffle"><Shuffle :size="20" /></button>
            <button :disabled="busy" aria-label="Précédent" @click="command('previous')"><SkipBack :size="25" /></button>
            <button :disabled="busy" class="music-play" :aria-label="isPlaying ? 'Pause' : 'Lecture'" @click="command(isPlaying ? 'pause' : 'play')">
              <Pause v-if="isPlaying" :size="28" />
              <Play v-else :size="28" />
            </button>
            <button :disabled="busy" aria-label="Suivant" @click="command('next')"><SkipForward :size="25" /></button>
            <button :aria-pressed="repeatState !== 'off'" :aria-label="`Répéter : ${repeatState}`" @click="cycleRepeat"><Repeat2 :size="20" /></button>
          </div>
          <div class="volume-control"><Volume2 :size="20" /><input :value="volume" type="range" min="0" max="100" aria-label="Volume" @change="changeVolume"><span>{{ volume }}%</span></div>
        </div>
      </article>

      <aside class="music-side">
        <article class="panel output-card">
          <div class="panel-heading"><div><div class="card-eyebrow">Sortie audio</div><h2>{{ deviceName }}</h2></div><Airplay :size="24" /></div>
          <p>{{ deviceType }} · Connecté</p>
          <button class="secondary-button" :disabled="devices.length < 2" @click="changeDevice">Changer d'appareil</button>
        </article>
        <article class="panel queue-card">
          <div class="panel-heading"><div><div class="card-eyebrow">À suivre</div><h2>File d'attente</h2></div><ListMusic :size="24" /></div>
          <div v-for="item in queue" :key="`${item.title}-${item.artist}`" class="queue-row">
            <button aria-label="Ajouter aux favoris"><Heart :size="17" /></button>
            <div><strong>{{ item.title }}</strong><span>{{ item.artist }}</span></div><small>{{ item.duration }}</small>
          </div>
        </article>
      </aside>
    </section>
  </div>
</template>
