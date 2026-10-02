<script setup lang="ts">
defineProps<{
  eyebrow: string
  value: string
  label: string
  meta?: string
  tone?: 'neutral' | 'success' | 'warning'
}>()
</script>

<template>
  <article class="metric-card" :class="tone ? `metric-card--${tone}` : ''">
    <div class="card-eyebrow">{{ eyebrow }}</div>
    <div class="metric-value">{{ value }}</div>
    <div class="metric-label">{{ label }}</div>
    <div v-if="meta" class="metric-meta">{{ meta }}</div>
  </article>
</template>
