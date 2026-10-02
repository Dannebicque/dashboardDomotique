<template>
  <div class="app-shell">
    <main class="app-content">
      <RouterView />
    </main>

    <nav class="bottom-nav" aria-label="Navigation principale">
      <RouterLink to="/" class="nav-item" exact-active-class="is-active">
        <span class="nav-dot">⌂</span>
        <span>Accueil</span>
      </RouterLink>
      <RouterLink to="/maison" class="nav-item" active-class="is-active">
        <span class="nav-dot">◉</span>
        <span>Maison</span>
      </RouterLink>
      <button class="nav-item nav-button" type="button">
        <span class="nav-dot">♫</span>
        <span>Musique</span>
      </button>
      <button class="nav-item nav-button" type="button">
        <span class="nav-dot">⚙</span>
        <span>Réglages</span>
      </button>
    </nav>
  </div>
</template>
