<template>
  <div class="app-shell">
    <main class="app-content"><RouterView /></main>
    <nav class="bottom-nav" aria-label="Navigation principale">
      <RouterLink to="/" class="nav-item" exact-active-class="is-active"><span class="nav-dot">⌂</span><span>Accueil</span></RouterLink>
      <RouterLink to="/eclairage" class="nav-item" active-class="is-active"><span class="nav-dot">☀</span><span>Éclairage</span></RouterLink>
      <RouterLink to="/volets" class="nav-item" active-class="is-active"><span class="nav-dot">▤</span><span>Volets</span></RouterLink>
      <RouterLink to="/musique" class="nav-item" active-class="is-active"><span class="nav-dot">♫</span><span>Musique</span></RouterLink>
      <RouterLink to="/climat" class="nav-item" active-class="is-active"><span class="nav-dot">◌</span><span>Climat</span></RouterLink>
      <RouterLink to="/reglages" class="nav-item" active-class="is-active"><span class="nav-dot">⚙</span><span>Réglages</span></RouterLink>
    </nav>
  </div>
</template>


<style scoped>
.bottom-nav {
  width: min(94vw, 760px);
  max-width: none;
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  flex-wrap: nowrap;
}

.nav-item {
  min-width: 0;
  white-space: nowrap;
}

@media (max-width: 760px) {
  .bottom-nav {
    width: calc(100vw - 24px);
    gap: 2px;
    padding-inline: 8px;
  }

  .nav-item {
    padding-inline: 4px;
    font-size: .72rem;
  }
}
</style>
