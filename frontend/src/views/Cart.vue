<template>
  <div class="page">

    <div class="cart-header">
      <router-link to="/" class="back-home">
        ← Domov
      </router-link>
    </div>

    <h1 class="title">
      Košík <span>({{ cart.length }})</span>
    </h1>

    <div v-if="cart.length" class="cart-layout">

      <div class="items">
        <div
          v-for="flight in cart"
          :key="flight.id"
          class="cart-card"
        >
          <div class="flight-info">
            <div class="flight-number">
              ✈️ {{ flight.flight_number }}
            </div>

            <div class="route">
              {{ flight.departure_city }} → {{ flight.arrival_city }}
            </div>

            <div class="passengers">
              👤 {{ flight.passengers }} {{ flight.passengers === 1 ? 'osoba' : 'osoby' }}
            </div>
          </div>

          <div class="price">
            {{ (flight.price * flight.passengers).toFixed(2) }} €
          </div>

          <button
            class="remove"
            @click="removeFromCart(flight.id)"
          >
            Odstrániť
          </button>
        </div>
      </div>

      <div class="summary">
        <h2>Prehľad platby</h2>

        <div class="summary-row">
          <span>Počet letov</span>
          <span>{{ cart.length }}</span>
        </div>

        <div class="summary-row">
          <span>Počet osôb</span>
          <span>{{ totalPassengers }}</span>
        </div>

        <div class="summary-row">
          <span>Medzisúčet</span>
          <span>{{ total }} €</span>
        </div>

        <div class="summary-total">
          <span>Celkom</span>
          <span>{{ total }} €</span>
        </div>

        <button
          class="checkout"
          @click="goCheckout"
        >
          Pokračovať k checkoutu
        </button>
      </div>

    </div>

    <div v-else class="empty">
      Košík je prázdny
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()
const cart = ref([])

function loadCart() {
  try {
    const raw = JSON.parse(localStorage.getItem('flightCart') || '[]')
    cart.value = Array.isArray(raw) ? raw : []
  } catch {
    cart.value = []
  }
}

const total = computed(() =>
  cart.value
    .reduce(
      (sum, f) =>
        sum + Number(f.price || 0) * Number(f.passengers || 1),
      0
    )
    .toFixed(2)
)

const totalPassengers = computed(() =>
  cart.value.reduce((sum, f) => sum + Number(f.passengers || 1), 0)
)

watch(
  () => route.fullPath,
  () => {
    loadCart()
  },
  { immediate: true }
)

const removeFromCart = (id) => {
  cart.value = cart.value.filter(f => f.id !== id)
  localStorage.setItem('flightCart', JSON.stringify(cart.value))
}

const goCheckout = () => {
  if (!cart.value.length) return
  router.push('/checkout')
}
</script>

<style scoped>
:root {
  --blue: #1E90FF;
  --dark-blue: #0A2540;
  --success: #00C9A7;
  --light-gray: #F8FAFC;
  --white: #FFFFFF;
  --shadow: 0 20px 40px rgba(0,0,0,0.08);
}

.page {
  min-height: 100vh;
  background: transparent;
  padding: 80px 20px 60px;
  font-family: "Space Grotesk", system-ui, sans-serif;
  max-width: 1200px;
  margin: 0 auto;
}

.cart-header {
  margin-bottom: 16px;
}

.back-home {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--dark-blue);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: rgba(30,144,255,0.08);
  border-radius: 12px;
  transition: all 0.2s ease;
}

.back-home:hover {
  background: rgba(30,144,255,0.15);
  transform: translateX(-2px);
}

.title {
  font-size: 2.2rem;
  font-weight: 800;
  background: linear-gradient(135deg, var(--dark-blue), var(--blue));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 2.5rem;
  max-width: 400px;
}

.title span {
  font-weight: 600;
  background: rgba(30,144,255,0.2);
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 1.1rem;
}

.cart-layout {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2.5rem;
  align-items: start;
}

.items {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.cart-card {
  background: var(--bg-alt);
  border-radius: 20px;
  padding: 2rem;
  box-shadow: var(--shadow);
  border: 1px solid rgba(30,144,255,0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.cart-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--blue), var(--success));
}

.cart-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 30px 60px rgba(30,144,255,0.15);
}

.flight-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 1;
}

.flight-number {
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--dark-blue);
  display: flex;
  align-items: center;
  gap: 8px;
}

.route {
  color: #64748B;
  font-size: 1.1rem;
  font-weight: 500;
}

.passengers {
  font-size: 0.95rem;
  color: #6B7280;
  background: rgba(0,201,167,0.1);
  padding: 4px 12px;
  border-radius: 20px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  width: fit-content;
}

.price {
  font-size: 2rem;
  font-weight: 800;
  color: var(--success) !important;
  -webkit-text-fill-color: var(--success) !important;
  background: transparent !important;
  margin-left: 1rem;
}

.remove {
  background: rgba(239,68,68,0.1);
  color: #DC2626;
  border: 1px solid rgba(220,38,38,0.2);
  padding: 10px 20px;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.remove:hover {
  background: #FEE2E2;
  transform: translateX(4px);
}

.summary {
  background: var(--bg-alt);
  border-radius: 24px;
  padding: 2.5rem 2rem;
  box-shadow: var(--shadow);
  border: 1px solid rgba(30,144,255,0.15);
  height: fit-content;
  position: sticky;
  top: 100px;
}

.summary h2 {
  font-size: 1.5rem;
  color: var(--dark-blue);
  margin-bottom: 2rem;
  font-weight: 700;
  padding-bottom: 1rem;
  border-bottom: 2px solid rgba(30,144,255,0.2);
}

.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 1rem;
  font-size: 1rem;
  color: #374151;
  font-weight: 500;
}

.summary-row:last-child {
  margin-bottom: 0;
}

.summary-total {
  display: flex;
  justify-content: space-between;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 3px solid var(--blue);
  font-size: 1.8rem;
  font-weight: 800;
  color: var(--dark-blue);
}

.summary-total span:first-child {
  color: var(--dark-blue);
}

.summary-total span:last-child {
  color: var(--success) !important;
  -webkit-text-fill-color: var(--success) !important;
  background: transparent !important;
}

.checkout {
  margin-top: 2rem;
  width: 100%;
  background: linear-gradient(135deg, var(--blue), var(--dark-blue));
  color: var(--white);
  border: none;
  padding: 18px 24px;
  border-radius: 16px;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 12px 30px rgba(30,144,255,0.3);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.checkout:hover {
  transform: translateY(-2px);
  box-shadow: 0 20px 40px rgba(30,144,255,0.4);
}

.checkout:active {
  transform: translateY(0);
}

.empty {
  text-align: center;
  padding: 160px 0;
  color: #6B7280;
  font-size: 1.4rem;
}

.empty::before {
  content: "🧳";
  display: block;
  font-size: 4rem;
  margin-bottom: 1.5rem;
}

body.dark .page {
  background: transparent;
}

body.dark .back-home {
  color: #60a5fa;
  background: rgba(59,130,246,0.15);
}

body.dark .back-home:hover {
  background: rgba(59,130,246,0.25);
}

body.dark .title {
  background: linear-gradient(135deg, #60a5fa, #22d3ee);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

body.dark .title span {
  background: rgba(59,130,246,0.25);
  color: #e2e8f0;
}

body.dark .cart-card,
body.dark .summary {
  background: #1e293b;
  border-color: #334155;
  box-shadow: 0 25px 50px rgba(0,0,0,0.6);
}

body.dark .cart-card::before {
  background: linear-gradient(90deg, #3b82f6, #22d3ee);
}

body.dark .flight-number {
  color: #e2e8f0;
}

body.dark .route {
  color: #94a3b8;
}

body.dark .passengers {
  background: rgba(34,211,238,0.15);
  color: #67e8f9;
}

body.dark .price {
  color: #22d3ee !important;
  -webkit-text-fill-color: #22d3ee !important;
}

body.dark .remove {
  background: rgba(239,68,68,0.15);
  color: #f87171;
  border-color: rgba(239,68,68,0.3);
}

body.dark .remove:hover {
  background: rgba(239,68,68,0.25);
}

body.dark .summary h2 {
  color: #e2e8f0;
  border-bottom: 2px solid rgba(59,130,246,0.3);
}

body.dark .summary-row {
  color: #cbd5e1;
}

body.dark .summary-total {
  border-top: 3px solid #3b82f6;
  color: #e2e8f0;
}

body.dark .summary-total span:first-child {
  color: #e2e8f0;
}

body.dark .summary-total span:last-child {
  color: #22d3ee !important;
  -webkit-text-fill-color: #22d3ee !important;
}

body.dark .checkout {
  background: linear-gradient(135deg, #3b82f6, #0f172a);
  box-shadow: 0 15px 35px rgba(59,130,246,0.35);
}

body.dark .empty {
  color: #94a3b8;
}


@media (max-width: 900px) {
  .cart-layout {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  
  .summary {
    position: static;
  }
  
  .title {
    font-size: 1.8rem;
  }
}

@media (max-width: 640px) {
  .page {
    padding: 70px 16px 50px;
  }
  
  .cart-card {
    flex-direction: column;
    gap: 1.5rem;
    text-align: center;
  }
  
  .price {
    margin-left: 0;
    font-size: 1.8rem;
  }
  
  .summary {
    padding: 2rem 1.5rem;
  }
}
</style>
