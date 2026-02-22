<template>
  <nav class="fixed top-0 left-0 w-full h-20 bg-[#0078D7] text-white px-8 flex justify-between items-center shadow-md z-50">
    <h1
      class="font-bold text-xl cursor-pointer"
      @click="goHome"
      style="font-weight: bold;"
    >
      ✈️ SkyPath AIR
    </h1>

    <div style="display: flex; align-items: center; gap: 16px;">
      <button
        @click="goCart"
        style="
          position: relative;
          padding: 8px 16px;
          background: rgba(255,255,255,0.15);
          border: 1px solid rgba(255,255,255,0.2);
          border-radius: 8px;
          color: white;
          font-weight: bold;
          cursor: pointer;
          transition: background 0.2s;
        "
        onmouseover="this.style.background='rgba(255,255,255,0.25)'"
        onmouseout="this.style.background='rgba(255,255,255,0.15)'"
      >
        🛒 Košík ({{ cartCount }})

        <span
          v-if="cartCount > 0"
          style="
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ef4444;
            color: white;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 12px;
            min-width: 20px;
            text-align: center;
            font-weight: bold;
          "
        >
          {{ cartCount }}
        </span>
      </button>

      <button
        @click="goFlights"
        style="background: none; border: none; color: white; text-decoration: underline; cursor: pointer;"
      >
        Lety
      </button>

      <button
        @click="goContact"
        style="background: none; border: none; color: white; text-decoration: underline; cursor: pointer;"
      >
        Kontakt
      </button>

      <template v-if="user">
        <button
          @click="goReservations"
          style="background: none; border: none; color: white; text-decoration: underline; cursor: pointer;"
        >
          Moje rezervácie
        </button>

        <span style="opacity: 0.9; font-size: 14px;">
          👋 {{ user.fullname }}
        </span>

        <button
          @click="logout"
          style="
            background: white;
            color: #0078D7;
            padding: 4px 12px;
            border-radius: 6px;
            font-weight: bold;
            border: none;
            cursor: pointer;
          "
        >
          Odhlásiť
        </button>
      </template>

      <template v-else>
        <button
          @click="goLogin"
          style="background: none; border: none; color: white; text-decoration: underline; cursor: pointer;"
        >
          Prihlásenie
        </button>

        <button
          @click="goRegister"
          style="background: none; border: none; color: white; text-decoration: underline; cursor: pointer;"
        >
          Registrácia
        </button>
      </template>
    </div>
  </nav>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";

const router = useRouter();
const user = ref(null);
const cartCount = ref(0);

let cartInterval;

onMounted(() => {
  const u = localStorage.getItem("user");
  if (u) user.value = JSON.parse(u);

  updateCartCount();
  cartInterval = setInterval(updateCartCount, 300);
});

onUnmounted(() => {
  if (cartInterval) clearInterval(cartInterval);
});

const updateCartCount = () => {
  try {
    const cart = JSON.parse(localStorage.getItem("flightCart") || "[]");
    cartCount.value = cart.length;
  } catch {
    cartCount.value = 0;
  }
};

function logout() {
  localStorage.removeItem("user");
  router.push("/login");
  window.location.reload();
}

function goHome() { router.push("/"); }
function goFlights() { router.push("/flights"); }
function goReservations() { router.push("/reservations"); }
function goLogin() { router.push("/login"); }
function goRegister() { router.push("/register"); }
function goCart() { router.push("/cart"); }
function goContact() { router.push("/contact"); }
</script>
