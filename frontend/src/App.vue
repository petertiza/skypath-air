<template>
  <div>
    <header class="app-header">
      <div class="container flex justify-between items-center py-4">
        
        <img
          :src="logo"
          alt="SkyPath AIR"
          class="logo"
          @click="goHome"
        />

        <button class="hamburger" @click="isMenuOpen = !isMenuOpen">
          ☰
        </button>

        <nav class="space-x-6 flex items-center"
             :class="{ 'menu-open': isMenuOpen }">

          <a href="#/cart" class="hover:underline flex items-center gap-1 relative" 
             style="padding: 6px 12px; background: rgba(255,255,255,0.1); border-radius: 6px; font-weight: bold;">
            🛒 Košík ({{ cartCount }})
            <span v-if="cartCount > 0" 
                  style="position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; font-size: 12px; padding: 1px 5px; border-radius: 10px; font-weight: bold; min-width: 18px; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">
              {{ cartCount }}
            </span>
          </a>

          <a href="#/" class="hover:underline">Domov</a>
          <a href="#/explore" class="hover:underline">Nájsť destinácie</a>
          <a href="#/reservations" class="hover:underline">Rezervácie</a>
          <a href="#/profile" class="hover:underline">Profil</a>
          <a href="#/contact" class="hover:underline">Kontakt</a>

          <span v-if="user && user.role === 'admin'" style="margin-left: 20px;">
            <a href="#/admin" class="hover:underline" 
               style="color: #ffd700; font-weight: bold; padding: 4px 8px; background: rgba(255,215,0,0.1); border-radius: 4px;">
              🔧 Admin
            </a>
          </span>

          <button @click="toggleDark" class="dark-toggle">
            {{ isDark ? "☀" : "🌙" }}
          </button>

          <template v-if="!user">
            <a href="#/login" class="hover:underline">Prihlásenie</a>
          </template>

          <template v-else>
            <span class="text-sm opacity-90 mr-2">👋 {{ user.fullname }}</span>
            <button
              @click="logout"
              style="background: white; color: #0078D7; padding: 4px 12px; border-radius: 6px; font-weight: bold; border: none; cursor: pointer;"
            >
              Odhlásiť
            </button>
          </template>
        </nav>
      </div>
    </header>

    <main class="content-wrapper">
      <router-view />
    </main>

    <footer class="app-footer">
      © {{ currentYear }} SkyPath AIR – každý let je nový príbeh ✈️
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import logo from "@/assets/logo-light.png"

const router = useRouter();
const user = ref(null);
const cartCount = ref(0);
const isMenuOpen = ref(false);
const isDark = ref(false);

const currentYear = ref(new Date().getFullYear());

onMounted(() => {
  loadUser()
  window.addEventListener("user-changed", loadUser)

  updateCart()
  setInterval(updateCart, 500)

  const savedTheme = localStorage.getItem("theme")
  if (savedTheme === "dark") {
    document.body.classList.add("dark")
    isDark.value = true
  }
})

function toggleDark() {
  document.body.classList.toggle("dark")
  isDark.value = document.body.classList.contains("dark")
  localStorage.setItem("theme", isDark.value ? "dark" : "light")
}

function loadUser() {
  const u = localStorage.getItem("user")
  user.value = u ? JSON.parse(u) : null
}

const updateCart = () => {  
  try {
    const cart = JSON.parse(localStorage.getItem('flightCart') || '[]');
    cartCount.value = cart.length;
  } catch {
    cartCount.value = 0;
  }
};

function logout() {
  localStorage.removeItem("user");
  user.value = null;
  router.push("/login");
}

function goHome() {
  router.push("/");
}
</script>

<style>
@import "./style.css";

.app-header {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  z-index: 50;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  box-shadow: var(--shadow-md);
  backdrop-filter: blur(20px);
}

.container {
  max-width: 1200px;
  width: 100%;
  margin: auto;
  padding-left: 20px;
  padding-right: 20px;
}

.content-wrapper {
  padding-top: 110px;
  width: 100%;
  max-width: none;
  margin: 0;
  padding-left: 0;
  padding-right: 0;
  min-height: calc(100vh - 200px);
  box-sizing: border-box;
}

body {
  background: var(--bg);
}

.logo {
  height: 40px;
  cursor: pointer;
  object-fit: contain;
}

header {
  background: linear-gradient(135deg, #1E90FF, #0A2540) !important;
  backdrop-filter: blur(20px);
}

header .container {
  padding: 1rem 20px;
}

nav {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

nav a {
  color: white !important;
  text-decoration: none;
  font-weight: 500;
  padding: 8px 16px;
  border-radius: 8px;
  transition: all 0.2s ease;
  white-space: nowrap;
}

nav a:hover {
  background: rgba(255,255,255,0.15);
  transform: translateY(-1px);
}

nav a[href="#/cart"] {
  position: relative;
  background: rgba(255,255,255,0.15) !important;
  padding: 8px 16px !important;
}

nav a[href="#/cart"] span {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #EF4444 !important;
  color: white !important;
  font-size: 12px;
  padding: 2px 6px;
  border-radius: 12px;
  font-weight: bold;
  min-width: 20px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(239,68,68,0.4);
}

nav a[href="#/admin"] {
  background: rgba(255,215,0,0.2) !important;
  color: #FFD700 !important;
}

button[style*="Odhlásiť"] {
  background: white !important;
  color: #1E90FF !important;
  padding: 8px 16px !important;
  border-radius: 8px !important;
  font-weight: 600 !important;
  border: none !important;
  cursor: pointer !important;
  transition: all 0.2s ease !important;
}

button[style*="Odhlásiť"]:hover {
  background: #F8FAFC !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(30,144,255,0.3);
}

.hamburger {
  display: none;
  font-size: 28px;
  background: none;
  border: none;
  color: white;
  cursor: pointer;
}

@media (max-width: 1100px) {

  .hamburger {
    display: block;
  }

  nav {
    display: none !important;
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    background: linear-gradient(135deg, #1E90FF, #0A2540);
    flex-direction: column;
    gap: 1rem;
    padding: 1rem 0;
  }

  nav.menu-open {
    display: flex !important;
  }

}

footer {
  background: linear-gradient(135deg, #0A2540, #1E40AF) !important;
  color: white !important;
  text-align: center;
  padding: 2rem 20px !important;
  margin-top: 4rem !important;
  width: 100vw !important;
  position: relative;
  left: 50%;
  right: 50%;
  margin-left: -50vw;
  margin-right: -50vw;
  box-shadow: 0 -4px 20px rgba(10,37,64,0.3);
}

footer::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, transparent, #1E90FF, transparent);
}

body.dark .app-header,
body.dark header {
  background: linear-gradient(135deg, #0f172a, #1e3a8a) !important;
  box-shadow: 0 10px 30px rgba(0,0,0,0.6);
}

body.dark nav a {
  color: #e2e8f0 !important;
}

body.dark nav a:hover {
  background: rgba(59,130,246,0.25);
}

body.dark nav a[href="#/cart"] {
  background: rgba(59,130,246,0.2) !important;
}

body.dark nav a[href="#/cart"] span {
  background: #ef4444 !important;
  box-shadow: 0 2px 8px rgba(239,68,68,0.6);
}

body.dark nav a[href="#/admin"] {
  background: rgba(250,204,21,0.15) !important;
  color: #facc15 !important;
}

body.dark .dark-toggle {
  background: rgba(59,130,246,0.2);
  color: #e2e8f0;
}

body.dark .dark-toggle:hover {
  background: rgba(59,130,246,0.35);
}

body.dark button[style*="Odhlásiť"] {
  background: #1e293b !important;
  color: #60a5fa !important;
  box-shadow: 0 4px 12px rgba(59,130,246,0.4);
}

body.dark button[style*="Odhlásiť"]:hover {
  background: #0f172a !important;
}

body.dark footer {
  background: linear-gradient(135deg, #0f172a, #1e293b) !important;
  color: #cbd5e1 !important;
  box-shadow: 0 -4px 20px rgba(0,0,0,0.6);
}

body.dark footer::after {
  background: linear-gradient(90deg, transparent, #3b82f6, transparent);
}

@media (max-width: 640px) {
  footer {
    padding: 1.5rem 16px !important;
    font-size: 0.9rem;
  }
}

@media (max-width: 1100px) {
  body.dark nav {
    background: linear-gradient(135deg, #0f172a, #1e3a8a);
  }
}
</style>
