<template>
  <div class="admin">
    <aside class="sidebar">
      <h2>✈️ Admin</h2>

      <nav>
        <RouterLink to="/admin" class="nav-link">Dashboard</RouterLink>
        <RouterLink to="/admin/flights" class="nav-link">Lety</RouterLink>
        <RouterLink to="/admin/aircrafts" class="nav-link">Lietadlá</RouterLink>
        <RouterLink to="/admin/reservations" class="nav-link">Rezervácie</RouterLink>
        <RouterLink to="/admin/users" class="nav-link">Používatelia</RouterLink>
      </nav>
    </aside>

    <main class="content">
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { RouterLink } from "vue-router"
</script>

<style scoped>
.admin {
  display: flex;
  min-height: 100vh;
  background: transparent;
}

.sidebar {
  width: 260px;
  background: linear-gradient(180deg, #0a2540 0%, #1e3a5f 100%);
  color: white;
  padding: 32px 24px;
  box-shadow: 4px 0 25px rgba(0,0,0,0.15);
  min-height: 100vh;
  position: relative;
  top: 0;
}

.sidebar h2 {
  margin: 0 0 32px 0;
  font-size: 24px;
  font-weight: 700;
  padding-bottom: 20px;
  border-bottom: 2px solid rgba(255,255,255,0.1);
}

nav {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.nav-link {
  display: block;
  color: rgba(255,255,255,0.9);
  text-decoration: none;
  padding: 14px 16px;
  border-radius: 12px;
  font-weight: 500;
  font-size: 15px;
  transition: all 0.2s ease;
}

.nav-link:hover {
  background: rgba(255,255,255,0.15);
  color: white;
  transform: translateX(4px);
  box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.nav-link.router-link-active {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  font-weight: 600;
  box-shadow: 0 6px 20px rgba(59,130,246,0.3);
}

.content {
  flex: 1;
  padding: 40px;
  background: transparent;
  overflow-y: auto;
  height: 100vh;
}

body.dark .admin {
  background: #0f172a;
}

body.dark .sidebar {
  background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
  box-shadow: 4px 0 40px rgba(0,0,0,0.6);
}

body.dark .sidebar h2 {
  border-bottom: 2px solid rgba(255,255,255,0.08);
}

body.dark .nav-link {
  color: #cbd5e1;
}

body.dark .nav-link:hover {
  background: rgba(59,130,246,0.2);
  color: #e2e8f0;
  box-shadow: 0 6px 20px rgba(0,0,0,0.5);
}

body.dark .nav-link.router-link-active {
  background: linear-gradient(135deg, #3b82f6, #0f172a);
  box-shadow: 0 8px 25px rgba(59,130,246,0.4);
}

body.dark .content {
  background: #0f172a;
}

@media (max-width: 1024px) {
  .admin {
    flex-direction: column;
  }

  .sidebar {
    width: 100%;
    height: auto;
    position: relative;
    padding: 20px;
  }

  nav {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;      
    gap: 10px;
  }

  .nav-link {
    flex: 1 1 auto;        
    text-align: center;
  }

  .content {
    padding: 20px;
  }
}
</style>
