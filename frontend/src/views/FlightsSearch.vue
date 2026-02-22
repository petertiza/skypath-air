<template>
  <div class="page">

    <section class="search-section">
      <div class="search-card">
        <h1>Nájdite svoj ideálny let ✈️</h1>

        <Autocomplete
          v-model="from"
          label="Mesto odletu"
          placeholder="Zadajte mesto"
        />

        <Autocomplete
          v-model="to"
          label="Mesto príletu"
          placeholder="Zadajte mesto"
        />

        <DatePicker
          v-model="date"
          label="Dátum odletu"
        />

        <button class="btn-primary" @click="search">
          Vyhľadať lety
        </button>

        <p v-if="error" class="error">
          ⚠️ {{ error }}
        </p>
      </div>
    </section>

    <section class="cheap-flights">
      <h2>Najlacnejšie letenky dnes</h2>

      <p v-if="loading" class="loading">
        Načítavam najlacnejšie lety…
      </p>

      <div v-else class="flight-grid">
        <div
          v-for="f in cheapFlights"
          :key="f.id"
          class="flight-card"
          @click="goToDetail(f.id)"
        >
          <div class="route">
            ✈️ {{ f.departure_city }} <span>→</span> {{ f.arrival_city }}
          </div>

          <div class="date">
            {{ new Date(f.departure_time).toLocaleString() }}
          </div>

          <div class="price">
            {{ f.price }} €
          </div>
        </div>
      </div>
    </section>

  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import Autocomplete from "../components/Autocomplete.vue";
import DatePicker from "../components/DatePicker.vue";

const router = useRouter();

const from = ref("");
const to = ref("");
const date = ref("");
const error = ref("");

const cheapFlights = ref([]);
const loading = ref(true);

function search() {
  if (!from.value || !to.value || !date.value) {
    error.value = "Najprv zvoľ mesto odletu, príletu a dátum.";
    return;
  }

  error.value = "";

  router.push({
    path: "/flights",
    query: {
      from: from.value,
      to: to.value,
      date: date.value
    }
  });
}

function goToDetail(id) {
  router.push(`/flight/${id}`);
}

onMounted(async () => {
  try {
    const res = await fetch("/api/flights.php");
    const all = await res.json();

    cheapFlights.value = all
      .sort((a, b) => a.price - b.price)
      .slice(0, 6);
  } catch (err) {
    console.error("Chyba pri načítaní letov:", err);
  }

  loading.value = false;
});
</script>

<style scoped>
:root {
  --blue: #1E90FF;
  --dark-blue: #0A2540;
  --orange: #FF6B35;
  --light-gray: #F2F2F2;
  --white: #FFFFFF;
}

.page {
  background: var(--bg);
  padding: 120px 24px 80px;
  min-height: 100vh;
  font-family: "Space Grotesk", sans-serif;
}

.search-section {
  display: flex;
  justify-content: center;
  margin-bottom: 80px;
}

.search-card {
  background: var(--white);
  padding: 36px;
  border-radius: 22px;
  width: 100%;
  max-width: 720px;
  box-shadow: 0 18px 45px rgba(0, 0, 0, 0.12);
}

.search-card h1 {
  text-align: center;
  color: var(--dark-blue);
  margin-bottom: 28px;
  font-size: 32px;
}

.btn-primary {
  margin-top: 20px;
  width: 100%;
  background: var(--orange);
  color: var(--white);
  border: none;
  padding: 14px;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}

.btn-primary:hover {
  opacity: 0.9;
}

.error {
  margin-top: 16px;
  background: #ffecec;
  border: 1px solid #ffbcbc;
  color: #a30000;
  padding: 12px;
  border-radius: 10px;
  font-size: 14px;
}

.cheap-flights {
  max-width: 1100px;
  margin: auto;
}

.cheap-flights h2 {
  color: var(--dark-blue);
  font-size: 30px;
  margin-bottom: 32px;
}

.loading {
  text-align: center;
  color: #666;
  font-size: 18px;
}

.flight-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 28px;
}

.flight-card {
  background: var(--white);
  border-radius: 20px;
  padding: 28px;
  cursor: pointer;
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.flight-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 20px 45px rgba(0, 0, 0, 0.18);
}

.route {
  font-size: 18px;
  font-weight: 600;
  color: var(--dark-blue);
}

.route span {
  margin: 0 6px;
}

.date {
  margin-top: 10px;
  color: #555;
  font-size: 14px;
}

.price {
  margin-top: 18px;
  font-size: 24px;
  font-weight: 700;
  color: var(--orange);
}

@media (max-width: 900px) {
  .flight-grid {
    grid-template-columns: 1fr;
  }
}
</style>
