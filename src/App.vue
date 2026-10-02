<template>
  <div class="app-shell">
    <main class="app-content"><RouterView /></main>
    <nav class="bottom-nav" aria-label="Navigation principale">
      <RouterLink to="/" class="nav-item" exact-active-class="is-active"><span class="nav-dot">⌂</span><span>Accueil</span></RouterLink>
      <RouterLink to="/maison" class="nav-item" active-class="is-active"><span class="nav-dot">◉</span><span>Maison</span></RouterLink>
      <RouterLink to="/musique" class="nav-item" active-class="is-active"><span class="nav-dot">♫</span><span>Musique</span></RouterLink>
      <RouterLink to="/reglages" class="nav-item" active-class="is-active"><span class="nav-dot">⚙</span><span>Réglages</span></RouterLink>
    </nav>
  </div>
</template>
