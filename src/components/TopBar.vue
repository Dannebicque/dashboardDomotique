<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const now = ref(new Date())
let timer: number | undefined

onMounted(() => {
  timer = window.setInterval(() => {
    now.value = new Date()
  }, 30000)
})

onBeforeUnmount(() => {
  if (timer) window.clearInterval(timer)
})

const time = computed(() => new Intl.DateTimeFormat('fr-FR', {
  hour: '2-digit',
  minute: '2-digit',
}).format(now.value))

const date = computed(() => new Intl.DateTimeFormat('fr-FR', {
  weekday: 'short',
  day: 'numeric',
  month: 'short',
}).format(now.value))
</script>

<template>
  <header class="topbar">
    <div>
      <div class="topbar-time">{{ time }}</div>
      <div class="topbar-date">{{ date }}</div>
    </div>
    <div class="home-title">
      <span class="home-title-mark"></span>
      Ma maison
    </div>
    <div class="connection-pill"><span></span> Connecté</div>
  </header>
</template>
