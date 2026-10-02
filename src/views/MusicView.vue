<script setup lang="ts">
import { computed, ref } from 'vue'
import { Airplay, Heart, ListMusic, Pause, Play, Repeat2, Shuffle, SkipBack, SkipForward, Volume2 } from 'lucide-vue-next'
import TopBar from '../components/TopBar.vue'
import { spotify } from '../data/mock'

const isPlaying = ref(spotify.isPlaying)
const volume = ref(62)
const progress = computed(() => Math.round((spotify.progressMs / spotify.durationMs) * 100))

const queue = [
  { title: 'Selfless', artist: 'The Strokes', duration: '3:42' },
  { title: 'Brooklyn Bridge to Chorus', artist: 'The Strokes', duration: '3:55' },
  { title: 'Bad Decisions', artist: 'The Strokes', duration: '4:53' },
]
</script>

<template>
  <div class="page">
    <TopBar />
    <section class="control-heading">
      <div><p class="hero-kicker">Spotify</p><h1>Musique</h1></div>
      <div class="service-chip">Salon · Apple TV</div>
    </section>

    <section class="music-layout">
      <article class="panel music-player">
        <img :src="spotify.coverUrl" :alt="`Pochette de ${spotify.album}`" class="music-cover">
        <div class="music-player-body">
          <div class="card-eyebrow">Lecture en cours</div>
          <h2 class="music-title">{{ spotify.title }}</h2>
          <p class="music-artist">{{ spotify.artist }} · {{ spotify.album }}</p>
          <div class="music-progress"><span :style="{ width: `${progress}%` }"></span></div>
          <div class="music-time"><span>2:12</span><span>5:09</span></div>
          <div class="music-controls">
            <button aria-label="Lecture aléatoire"><Shuffle :size="20" /></button>
            <button aria-label="Précédent"><SkipBack :size="25" /></button>
            <button class="music-play" :aria-label="isPlaying ? 'Pause' : 'Lecture'" @click="isPlaying = !isPlaying">
              <Pause v-if="isPlaying" :size="28" />
              <Play v-else :size="28" />
            </button>
            <button aria-label="Suivant"><SkipForward :size="25" /></button>
            <button aria-label="Répéter"><Repeat2 :size="20" /></button>
          </div>
          <div class="volume-control"><Volume2 :size="20" /><input v-model.number="volume" type="range" min="0" max="100" aria-label="Volume"><span>{{ volume }}%</span></div>
        </div>
      </article>

      <aside class="music-side">
        <article class="panel output-card">
          <div class="panel-heading"><div><div class="card-eyebrow">Sortie audio</div><h2>Salon</h2></div><Airplay :size="24" /></div>
          <p>Apple TV · Connecté</p>
          <button class="secondary-button">Changer d'appareil</button>
        </article>
        <article class="panel queue-card">
          <div class="panel-heading"><div><div class="card-eyebrow">À suivre</div><h2>File d'attente</h2></div><ListMusic :size="24" /></div>
          <div v-for="item in queue" :key="item.title" class="queue-row">
            <button aria-label="Ajouter aux favoris"><Heart :size="17" /></button>
            <div><strong>{{ item.title }}</strong><span>{{ item.artist }}</span></div><small>{{ item.duration }}</small>
          </div>
        </article>
      </aside>
    </section>
  </div>
</template>
