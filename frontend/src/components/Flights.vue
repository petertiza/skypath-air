<template>
  <div class="page">

    <button class="back-btn" @click="$router.push('/')">
      ← Späť na vyhľadávanie
    </button>

    <h1 class="title">
      Dostupné lety
      <span class="subtitle">
        ({{ passengers }} {{ passengers === 1 ? 'osoba' : 'osoby' }})
      </span>
    </h1>

    <div v-if="selectedFlight" class="flight-detail">

      <div class="detail-header">
        <span class="badge">Priamy let</span>
        <h2>
          ✈️ {{ selectedFlight.departure_city }}
          <span>→</span>
          {{ selectedFlight.arrival_city }}
        </h2>
      </div>

      <div class="detail-info">
        <div>
          <label>Číslo letu</label>
          <p>{{ selectedFlight.flight_number }}</p>
        </div>
        <div>
          <label>Odlet</label>
          <p>{{ new Date(selectedFlight.departure_time).toLocaleString() }}</p>
        </div>
        <div>
          <label>Prílet</label>
          <p>{{ new Date(selectedFlight.arrival_time).toLocaleString() }}</p>
        </div>
        <div>
          <label>Voľné miesta</label>
          <p>{{ selectedFlight.seats_available }}</p>
        </div>
        <div class="price-box">
          <label>Cena / osoba</label>
          <p>{{ selectedFlight.price }} €</p>
        </div>
      </div>

      <div class="actions">
        <button class="btn-primary" @click="addToCart(selectedFlight)">
          Rezervovať
        </button>
        <button class="btn-secondary" @click="selectedFlight = null">
          Späť
        </button>
      </div>

    </div>

    <div v-else-if="filteredFlights.length" class="flight-list">

      <div
        v-for="f in filteredFlights"
        :key="f.id"
        class="flight-card"
      >
        <div class="flight-main">
          <h3>
            ✈️ {{ f.departure_city }}
            <span>→</span>
            {{ f.arrival_city }}
          </h3>
          <p class="time">
            {{ new Date(f.departure_time).toLocaleString() }}
          </p>
          <p class="seats">
            Voľné miesta: <b>{{ f.seats_available }}</b>
          </p>
        </div>

        <div class="flight-side">
          <div class="price">
            {{ f.price }} € / osoba
          </div>

          <div class="card-actions">
            <button class="btn-outline" @click="showDetails(f)">
              Detail
            </button>

            <button
              class="btn-primary"
              :disabled="f.seats_available < passengers"
              :class="{ soldout: f.seats_available < passengers }"
              @click="addToCart(f)"
            >
              {{
                f.seats_available < passengers
                  ? 'Nedostatok miest'
                  : 'Rezervovať'
              }}
            </button>
          </div>
        </div>
      </div>

    </div>

    <p v-else class="empty">
      Žiadne lety s dostatočným počtom miest
    </p>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { useRouter, useRoute } from "vue-router";

const router = useRouter();
const route = useRoute();

const flights = ref([]);
const selectedFlight = ref(null);
const passengers = ref(1);

function showDetails(flight) {
  selectedFlight.value = flight;
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function addToCart(flight) {
  const user = JSON.parse(localStorage.getItem("user"));
  if (!user) {
    router.push("/login");
    return;
  }

  const cartItem = {
    id: flight.id,
    flight_number: flight.flight_number,
    departure_city: flight.departure_city,
    arrival_city: flight.arrival_city,
    departure_time: flight.departure_time,
    arrival_time: flight.arrival_time,
    price: Number(flight.price),
    passengers: passengers.value
  };

  localStorage.setItem("flightCart", JSON.stringify([cartItem]));
  router.push("/cart");
}

const filteredFlights = computed(() =>
  flights.value.filter(f => f.seats_available >= passengers.value)
);

onMounted(async () => {
  const { from, to, date, passengers: p } = route.query;
  if (!from || !to || !date) return;

  passengers.value = Number(p) || 1;

  const res = await fetch(
    `/api/flights.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&date=${date}`
  );

  flights.value = await res.json();
});
</script>


<style>
:root {
  --blue: #1E90FF;
  --dark-blue: #0A2540;
  --orange: #FF6B35;
  --light-gray: #F2F2F2;
  --white: #FFFFFF;
}

body {
  margin: 0;
  font-family: "Segoe UI", sans-serif;
  background: var(--light-gray);
}

.page {
  max-width: 1100px;
  margin: auto;
  padding: 32px;
}

.title {
  font-size: 34px;
  color: var(--dark-blue);
  margin-bottom: 32px;
}

.back-btn {
  background: none;
  border: none;
  color: var(--dark-blue);
  font-size: 16px;
  cursor: pointer;
  margin-bottom: 16px;
}

.flight-card {
  background: var(--white);
  border-radius: 18px;
  padding: 24px;
  margin-bottom: 20px;
  display: flex;
  justify-content: space-between;
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
}

.flight-card h3 {
  margin: 0;
  color: var(--dark-blue);
}

.flight-card span {
  margin: 0 8px;
}

.time {
  color: #555;
  margin-top: 8px;
}

.flight-side {
  text-align: right;
}

.price {
  font-size: 26px;
  font-weight: 700;
  color: var(--blue);
}

.card-actions {
  display: flex;
  gap: 10px;
  margin-top: 16px;
  justify-content: flex-end;
}

.btn-primary {
  background: var(--orange);
  color: var(--white);
  border: none;
  padding: 12px 24px;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
}

.btn-outline {
  background: transparent;
  border: 2px solid var(--blue);
  color: var(--blue);
  padding: 10px 20px;
  border-radius: 10px;
  cursor: pointer;
}

.btn-secondary {
  background: var(--blue);
  color: var(--white);
  border: none;
  padding: 12px 24px;
  border-radius: 10px;
}

.flight-detail {
  background: var(--white);
  border-radius: 22px;
  padding: 36px;
  box-shadow: 0 16px 40px rgba(0, 0, 0, 0.1);
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.detail-header h2 {
  color: var(--dark-blue);
}

.badge {
  background: var(--blue);
  color: var(--white);
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 14px;
}

.detail-info {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin-top: 32px;
}

.detail-info label {
  font-size: 13px;
  color: #666;
}

.detail-info p {
  font-size: 16px;
  font-weight: 600;
  color: var(--dark-blue);
}

.price-box p {
  font-size: 22px;
  color: var(--orange);
}

.actions {
  display: flex;
  gap: 14px;
  margin-top: 32px;
}

.soldout {
  background: #9ca3af !important;
  cursor: not-allowed;
  box-shadow: none;
}


.message {
  margin-top: 16px;
  color: var(--blue);
  font-weight: 600;
}

.empty {
  text-align: center;
  font-size: 18px;
  color: var(--dark-blue);
}
.subtitle {
  font-size: 0.9rem;
  font-weight: 500;
  color: #6b7280;
  margin-left: 8px;
}

.seats {
  font-size: 0.85rem;
  color: #555;
  margin-top: 6px;
}

.soldout {
  background: #9ca3af !important;
  cursor: not-allowed;
}

body.dark {
  background: #0f172a;
}

body.dark .title {
  color: #e2e8f0;
}

body.dark .subtitle {
  color: #94a3b8;
}

body.dark .back-btn {
  color: #60a5fa;
}

body.dark .flight-card {
  background: #1e293b;
  box-shadow: 0 20px 50px rgba(0,0,0,0.6);
}

body.dark .flight-card h3 {
  color: #e2e8f0;
}

body.dark .time,
body.dark .seats {
  color: #94a3b8;
}

body.dark .price {
  color: #22d3ee;
}

body.dark .btn-outline {
  border-color: #3b82f6;
  color: #60a5fa;
}

body.dark .btn-outline:hover {
  background: rgba(59,130,246,0.15);
}

body.dark .btn-primary {
  background: linear-gradient(135deg, #3b82f6, #0f172a);
}

body.dark .btn-secondary {
  background: #3b82f6;
}

body.dark .flight-detail {
  background: #1e293b;
  box-shadow: 0 30px 60px rgba(0,0,0,0.6);
}

body.dark .detail-header h2 {
  color: #e2e8f0;
}

body.dark .detail-info label {
  color: #94a3b8;
}

body.dark .detail-info p {
  color: #e2e8f0;
}

body.dark .badge {
  background: #3b82f6;
}

body.dark .price-box p {
  color: #22d3ee;
}

body.dark .empty {
  color: #94a3b8;
}
</style>
