<script setup lang="ts">
import { computed } from 'vue'
import type { SpotifySnapshot } from '../types/home'

const props = defineProps<{ track: SpotifySnapshot }>()

const progress = computed(() => Math.round((props.track.progressMs / props.track.durationMs) * 100))
</script>

<template>
  <article class="panel spotify-card">
    <div class="panel-heading">
      <div>
        <div class="card-eyebrow">Spotify</div>
        <h2>En cours de lecture</h2>
      </div>
      <span class="service-chip">Connecté</span>
    </div>

    <div class="now-playing">
      <img :src="track.coverUrl" :alt="`Pochette de ${track.album}`" class="album-cover">
      <div class="track-info">
        <div class="track-title">{{ track.title }}</div>
        <div class="track-artist">{{ track.artist }}</div>
        <div class="track-album">{{ track.album }}</div>

        <div class="progress-track" aria-label="Progression de la lecture">
          <span :style="{ width: `${progress}%` }"></span>
        </div>

        <div class="player-controls">
          <button aria-label="Titre précédent">‹‹</button>
          <button class="play-button" :aria-label="track.isPlaying ? 'Pause' : 'Lecture'">
            {{ track.isPlaying ? 'Ⅱ' : '▶' }}
          </button>
          <button aria-label="Titre suivant">››</button>
        </div>
      </div>
    </div>
  </article>
</template>
