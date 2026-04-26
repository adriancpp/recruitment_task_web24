<script setup>
import { RouterLink, RouterView } from 'vue-router'
</script>

<template>
  <div class="app">
    <header class="header">
      <RouterLink to="/" class="brand">Import transakcji</RouterLink>
      <nav class="nav">
        <RouterLink to="/">Upload</RouterLink>
        <RouterLink to="/imports">Historia importów</RouterLink>
      </nav>
    </header>
    <main class="main">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.5rem;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
}

.brand {
  font-weight: 600;
  font-size: 1.1rem;
  color: var(--text);
  text-decoration: none;
}

.brand:hover {
  color: var(--accent);
  text-decoration: none;
}

.nav {
  display: flex;
  gap: 1.25rem;
}

.main {
  flex: 1;
  padding: 1.5rem;
  max-width: 1100px;
  width: 100%;
  margin: 0 auto;
}
</style>
