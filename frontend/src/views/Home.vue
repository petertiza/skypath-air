<template>
  <div class="home">

    <section class="hero">

      <div class="hero-lottie">
        <Vue3Lottie
          :animationData="flightAnimation"
          :loop="true"
          :autoplay="true"
        />
      </div>

      <div class="hero-content animate-hero">
        <h1>
          Cestujte pohodlne.<br />
          <span>Rezervujte lety jednoducho.</span>
        </h1>
        <p>
          Moderný rezervačný systém našej leteckej spoločnosti vám umožní
          nájsť ideálny let v priebehu niekoľkých sekúnd.
        </p>
      </div>

      <div class="search-card animate-card">
        <h2>Vyhľadávanie letov ✈️</h2>

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
          :disablePast="true"
        />

        <div class="field">
          <label>Počet osôb</label>
          <select v-model="passengers">
            <option v-for="n in 9" :key="n" :value="n">
              {{ n }} {{ n === 1 ? 'osoba' : 'osoby' }}
            </option>
          </select>
        </div>

        <button class="btn-primary" @click="searchFlights">
          Nájsť lety
        </button>

        <p v-if="error" class="error">{{ error }}</p>
      </div>
    </section>

    <section class="benefits">
      <h2 class="section-title">Prečo cestovať s nami?</h2>

      <div class="benefit-grid">
        <div class="benefit reveal">
          <h3>✈️ Moderná flotila</h3>
          <p>
            Naše lietadlá spĺňajú najnovšie bezpečnostné a komfortné štandardy.
          </p>
        </div>

        <div class="benefit reveal">
          <h3>💳 Jednoduchá rezervácia</h3>
          <p>
            Rezervujte let, vyberte sedadlo a zaplaťte online bez stresu.
          </p>
        </div>

        <div class="benefit reveal">
          <h3>🎁 Vernostný program</h3>
          <p>
            Zbierajte body a získajte zľavy pri ďalšom cestovaní.
          </p>
        </div>
      </div>
    </section>

    <section class="promo reveal">
      <div class="promo-content">
        <h2>Vernostný program</h2>
        <p>
          Za každé euro minuté na letenku získate body,
          ktoré môžete využiť ako zľavu.
        </p>
        <p class="highlight">
          Cestujte častejšie. Plaťte menej.
        </p>
      </div>
    </section>

  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { Vue3Lottie } from "vue3-lottie";

import Autocomplete from "../components/Autocomplete.vue";
import DatePicker from "../components/DatePicker.vue";
import flightAnimation from "@/assets/lottie/flight.json";

const from = ref("");
const to = ref("");
const date = ref("");
const passengers = ref(1);
const error = ref("");

const router = useRouter();

function searchFlights() {
  if (!from.value || !to.value || !date.value || !passengers.value) {
    error.value = "Vyplňte všetky údaje vrátane počtu osôb.";
    return;
  }

  error.value = "";

  router.push({
    path: "/flights",
    query: {
      from: from.value,
      to: to.value,
      date: date.value,
      passengers: passengers.value
    }
  });
}

onMounted(() => {
  const els = document.querySelectorAll(".reveal");

  const observer = new IntersectionObserver(
    entries => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          e.target.classList.add("active");
        }
      });
    },
    { threshold: 0.2 }
  );

  els.forEach(el => observer.observe(el));
});
</script>

<style>
:root {
  --blue: #1E90FF;
  --dark-blue: #0A2540;
  --orange: #FF6B35;
  --light-gray: #F2F6FB;
  --white: #FFFFFF;
}

.home {
  font-family: "Space Grotesk", system-ui, sans-serif;
}

.hero {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, var(--dark-blue), var(--blue));
  color: var(--white);
  padding: 90px 24px 120px;
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 56px;
  align-items: center;
}

.hero-lottie {
  position: absolute;
  inset: 0;
  z-index: 0;
  pointer-events: none;
  opacity: 0.38;

  transform: translateX(-12%);
}

.hero-lottie > * {
  width: 120%;
  height: 100%;
}

.hero-content,
.search-card {
  position: relative;
  z-index: 2;
}

.hero h1 {
  font-size: 44px;
  margin-bottom: 18px;
}

.hero h1 span {
  color: var(--orange);
}

.hero p {
  font-size: 18px;
  max-width: 520px;
  opacity: 0.95;
}

.search-card {
  background: rgba(255,255,255,0.96);
  backdrop-filter: blur(10px);
  border-radius: 22px;
  padding: 34px;
  box-shadow: 0 30px 70px rgba(0,0,0,0.25);
  color: var(--dark-blue);
}

.search-card h2 {
  margin-bottom: 22px;
  text-align: center;
}

.btn-primary {
  margin-top: 20px;
  width: 100%;
  background: linear-gradient(135deg, var(--orange), #ff8c5a);
  color: var(--white);
  border: none;
  padding: 14px;
  border-radius: 14px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: transform .15s, box-shadow .15s;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 30px rgba(255,107,53,.45);
}

.error {
  margin-top: 14px;
  background: #ffecec;
  border: 1px solid #ffbcbc;
  color: #a30000;
  padding: 10px;
  border-radius: 8px;
  font-size: 14px;
}

.field {
  display: flex;
  flex-direction: column;
  margin-bottom: 14px;
}

.field label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #555;
  margin-bottom: 6px;
}

.field select {
  padding: 12px 16px;     
  border-radius: 14px;    
  border: 2px solid #d1d5db;
  font-size: 16px;        
  height: 48px;         
  font-family: Space Grotesk, system-ui, sans-serif;
}

.benefits {
  padding: 90px 24px;
  text-align: center;
}

.section-title {
  color: var(--dark-blue);
  margin-bottom: 54px;
  font-size: 34px;
}

.benefit-grid {
  max-width: 1100px;
  margin: auto;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 36px;
}

.benefit {
  background: var(--white);
  padding: 34px;
  border-radius: 22px;
  box-shadow: 0 16px 40px rgba(0,0,0,0.1);
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.benefit:hover {
  transform: translateY(-12px) scale(1.02);
  box-shadow: 0 30px 70px rgba(0,0,0,0.2);
}

.promo {
  background: var(--white);
  padding: 90px 24px;
}

.promo-content {
  max-width: 900px;
  margin: auto;
  text-align: center;
}

.promo h2 {
  color: var(--dark-blue);
  font-size: 34px;
}

.highlight {
  margin-top: 20px;
  font-size: 22px;
  font-weight: 800;
  color: var(--orange);
}

.animate-hero {
  animation: fadeUp 0.9s ease forwards;
}

.animate-card {
  animation: scaleIn 0.7s ease forwards;
}

.reveal {
  opacity: 0;
  transform: translateY(30px);
  transition: 0.7s ease;
}

.reveal.active {
  opacity: 1;
  transform: translateY(0);
}

body.dark .hero {
  background: linear-gradient(135deg, #0f172a, #1e3a8a);
}

body.dark .hero h1 {
  color: #f1f5f9;
}

body.dark .hero h1 span {
  color: #22d3ee;
}

body.dark .hero p {
  color: #cbd5e1;
}

body.dark .search-card {
  background: #1e293b;
  color: #e2e8f0;
  box-shadow: 0 30px 70px rgba(0,0,0,0.6);
}

body.dark .search-card h2 {
  color: #e2e8f0;
}

body.dark .field label {
  color: #94a3b8;
}

body.dark .field select {
  background: #0f172a;
  border-color: #334155;
  color: #f1f5f9;
}

body.dark .field select:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59,130,246,0.3);
}

body.dark .btn-primary {
  background: linear-gradient(135deg, #3b82f6, #0f172a);
  box-shadow: 0 12px 30px rgba(59,130,246,0.35);
}

body.dark .error {
  background: rgba(239,68,68,0.15);
  border-color: rgba(239,68,68,0.3);
  color: #f87171;
}

body.dark .benefits {
  background: transparent;
}

body.dark .section-title {
  color: #e2e8f0;
}

body.dark .benefit {
  background: #1e293b;
  color: #e2e8f0;
  box-shadow: 0 25px 50px rgba(0,0,0,0.6);
}

body.dark .benefit p {
  color: #94a3b8;
}

body.dark .promo {
  background: #0f172a;
}

body.dark .promo h2 {
  color: #e2e8f0;
}

body.dark .highlight {
  color: #22d3ee;
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes scaleIn {
  from { opacity: 0; transform: scale(0.96); }
  to { opacity: 1; transform: scale(1); }
}

@media (max-width: 900px) {
  .hero {
    grid-template-columns: 1fr;
    text-align: center;
  }

  .hero-lottie {
    transform: translateX(-20%);
  }

  .benefit-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .hero h1 {
    font-size: 36px;
  }

  .search-card {
    padding: 28px 20px;
  }
}
</style>