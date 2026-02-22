<template>
  <div class="page">

    <div class="header">
      <button class="back" @click="router.back()">← Späť</button>
      <h1>Údaje cestujúcich</h1>
      <p>Zadajte údaje pre ostatných pasažierov</p>
    </div>

    <div class="cards">
      <div
        v-for="(p, index) in passengers"
        :key="index"
        class="card"
      >
        <h3>👤 Cestujúci {{ index + 2 }}</h3>

        <div class="grid">
          <input
            v-model="p.firstname"
            placeholder="Meno *"
          />

          <input
            v-model="p.lastname"
            placeholder="Priezvisko *"
          />

          <DatePicker
            v-model="p.birthdate"
            label="Dátum narodenia *"
            :disableFuture="true"
            :minYear="1950"
            placeholder="Vyber dátum"
          />

          <select v-model="p.nationality">
            <option value="">Národnosť *</option>
            <option
              v-for="n in nationalities"
              :key="n.code"
              :value="n.code"
            >
              {{ n.name }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <div class="footer">
      <button
        class="btn"
        :disabled="!isComplete"
        @click="continueFlow"
      >
        Pokračovať k výberu sedadiel →
      </button>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { useRouter } from "vue-router";
import DatePicker from "@/components/DatePicker.vue";

const router = useRouter();

const passengers = ref([]);

const nationalities = [
  { code: "SK", name: "Slovensko" },
  { code: "CZ", name: "Česko" },
  { code: "AT", name: "Rakúsko" },
  { code: "DE", name: "Nemecko" },
  { code: "PL", name: "Poľsko" },
  { code: "HU", name: "Maďarsko" },
  { code: "FR", name: "Francúzsko" },
  { code: "IT", name: "Taliansko" },
  { code: "ES", name: "Španielsko" },
  { code: "GB", name: "Veľká Británia" },
  { code: "US", name: "USA" },
  { code: "CA", name: "Kanada" },
  { code: "AU", name: "Austrália" },
  { code: "JP", name: "Japonsko" },
  { code: "CN", name: "Čína" },
  { code: "BR", name: "Brazília" },
  { code: "IN", name: "India" }
];

onMounted(() => {
  const checkout = JSON.parse(localStorage.getItem("checkoutData") || "{}");

  const count = checkout.passenger_count || 1;

  if (count <= 1) {
    router.push(`/seats/${checkout.cart?.[0]?.id}`);
    return;
  }

  passengers.value = Array.from({ length: count - 1 }, () => ({
    firstname: "",
    lastname: "",
    birthdate: "",
    nationality: ""
  }));
});

const isComplete = computed(() =>
  passengers.value.every(p =>
    p.firstname &&
    p.lastname &&
    p.birthdate &&
    p.nationality
  )
);

function getAge(date) {
  const b = new Date(date);
  const t = new Date();
  let age = t.getFullYear() - b.getFullYear();
  const m = t.getMonth() - b.getMonth();
  if (m < 0 || (m === 0 && t.getDate() < b.getDate())) age--;
  return age;
}

function continueFlow() {
  if (!isComplete.value) {
    alert("Vyplňte všetky údaje všetkých cestujúcich.");
    return;
  }

  const checkout = JSON.parse(localStorage.getItem("checkoutData"));

  checkout.passengers = passengers.value.map(p => ({
    ...p,
    age: getAge(p.birthdate)
  }));

  localStorage.setItem("checkoutData", JSON.stringify(checkout));

  router.push(`/seats/${checkout.cart[0].id}`);
}
</script>

<style scoped>
.page {
  min-height: 100vh;
  background: #f2f6fb;
  padding: 120px 20px 60px;
  font-family: "Segoe UI", system-ui, sans-serif;
}

.header {
  max-width: 900px;
  margin: auto;
  margin-bottom: 40px;
}

.header h1 {
  font-size: 32px;
  color: #0a2540;
}

.header p {
  color: #555;
}

.back {
  background: none;
  border: none;
  color: #0a2540;
  font-weight: 600;
  cursor: pointer;
  margin-bottom: 10px;
}

.cards {
  max-width: 900px;
  margin: auto;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.card {
  background: white;
  padding: 28px;
  border-radius: 22px;
  box-shadow: 0 18px 45px rgba(0,0,0,0.12);
}

.card h3 {
  margin-bottom: 20px;
  color: #0a2540;
}

.grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 18px;
}

input, select {
  padding: 14px 16px;
  border-radius: 14px;
  border: 1.5px solid #d1d5db;
  font-size: 0.95rem;
}

input:focus, select:focus {
  outline: none;
  border-color: #0078d7;
  box-shadow: 0 0 0 3px rgba(0,120,215,0.15);
}

.footer {
  max-width: 900px;
  margin: 50px auto 0;
  text-align: right;
}

.btn {
  padding: 18px 34px;
  font-size: 1.1rem;
  font-weight: 800;
  border-radius: 16px;
  border: none;
  color: white;
  cursor: pointer;
  background: linear-gradient(135deg,#0078d7,#005bb5);
  box-shadow: 0 14px 32px rgba(0,120,215,0.35);
  transition: 0.2s;
}

.btn:hover:not(:disabled) {
  transform: translateY(-1px);
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}


body.dark .page {
  background: transparent;
}

body.dark .header h1 {
  color: #e2e8f0;
}

body.dark .header p {
  color: #94a3b8;
}

body.dark .back {
  color: #60a5fa;
}

body.dark .cards {
  color: #e2e8f0;
}

body.dark .card {
  background: #1e293b;
  box-shadow: 0 25px 50px rgba(0,0,0,0.6);
}

body.dark .card h3 {
  color: #e2e8f0;
}

body.dark input,
body.dark select {
  background: #0f172a;
  border-color: #334155;
  color: #f1f5f9;
}

body.dark input:focus,
body.dark select:focus {
  background: #0f172a;
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59,130,246,0.3);
}

body.dark .footer {
  color: #e2e8f0;
}

body.dark .btn {
  background: linear-gradient(135deg, #1e40af, #0f172a);
  box-shadow: 0 15px 35px rgba(59,130,246,0.35);
}

body.dark .btn:disabled {
  opacity: 0.4;
}

@media (max-width: 640px) {
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
