<template>
  <div class="page">

    <div class="header">
      <button class="back-btn" @click="router.push('/cart')">
        ← Späť do košíka
      </button>

      <h1>Údaje hlavného cestujúceho</h1>
      <p>Vyplňte údaje osoby, ktorá vykonáva rezerváciu</p>
    </div>

    <div v-if="formError" class="form-error">
      ⚠️ {{ formError }}
    </div>

    <div class="layout">

      <div class="card">

        <h3>📧 Kontaktné údaje</h3>
        <div class="grid">
          <input
            v-model="form.email"
            placeholder="Email *"
            :class="{ error: errors.email }"
          />
          <input
            v-model="form.phone"
            placeholder="Telefón *"
            :class="{ error: errors.phone }"
          />
        </div>

        <h3>👤 Hlavný pasažier</h3>
        <div class="grid">
          <input
            v-model="form.firstname"
            placeholder="Meno *"
            :class="{ error: errors.firstname }"
          />
          <input
            v-model="form.lastname"
            placeholder="Priezvisko *"
            :class="{ error: errors.lastname }"
          />

          <DatePicker
            v-model="form.birthdate"
            label="Dátum narodenia"
            :disableFuture="true"
            :minYear="1950"
          />

          <div class="select-wrap">
            <select
              v-model="form.nationality"
              :class="{ error: errors.nationality }"
            >
              <option value="">Národnosť</option>
              <option
                v-for="n in nationalities"
                :key="n.code"
                :value="n.code"
              >
                {{ n.name }}
              </option>
            </select>
            <span class="arrow">▾</span>
          </div>
        </div>

        <h3>👥 Počet cestujúcich</h3>
        <div class="select-wrap full">
          <select v-model.number="passengerCount">
            <option v-for="n in 6" :key="n" :value="n">
              {{ n }} {{ n === 1 ? "osoba" : "osoby" }}
            </option>
          </select>
          <span class="arrow">▾</span>
        </div>

        <h3>🧳 Batožina</h3>
        <div
          v-for="flight in cart"
          :key="flight.id"
          class="baggage"
        >
          <div class="route">
            ✈️ {{ flight.departure_city }} → {{ flight.arrival_city }}
          </div>

          <label>
            <input type="radio" value="hand" v-model="flight.baggage_type" />
            Príručná (v cene)
          </label>

          <label>
            <input type="radio" value="checked" v-model="flight.baggage_type" />
            Podpalubná (+{{ BAGGAGE_FEE }} € / osoba)
          </label>
        </div>

        <label class="terms">
          <input type="checkbox" v-model="form.terms" />
          Súhlasím s podmienkami a ochranou osobných údajov
        </label>

      </div>

      <div class="summary">
        <h3>Zhrnutie</h3>

        <div class="row">
          <span>Cestujúci</span>
          <span>{{ passengerCount }} × {{ cart[0]?.price }} €</span>
        </div>

        <div
          class="row"
          v-if="cart[0]?.baggage_type === 'checked'"
        >
          <span>Podpalubná batožina</span>
          <span>{{ passengerCount }} × {{ BAGGAGE_FEE }} €</span>
        </div>

        <div class="row">
          <span>Letov</span>
          <span>{{ cart.length }}</span>
        </div>

        <div class="total">
          Celkom: {{ totalPrice }} €
        </div>

        <button class="btn-primary" @click="continueFlow">
          Pokračovať →
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import { useRouter } from "vue-router"
import DatePicker from "@/components/DatePicker.vue"

const router = useRouter()
const BAGGAGE_FEE = 25

const form = ref({
  email: "",
  phone: "",
  firstname: "",
  lastname: "",
  birthdate: "",
  nationality: "",
  terms: false
})

const errors = ref({})
const formError = ref("")

const passengerCount = ref(1)
const cart = ref([])

const nationalities = [
  { code: "SK", name: "Slovensko" },
  { code: "CZ", name: "Česko" },
  { code: "DE", name: "Nemecko" },
  { code: "AT", name: "Rakúsko" },
  { code: "PL", name: "Poľsko" },
  { code: "HU", name: "Maďarsko" },
  { code: "FR", name: "Francúzsko" },
  { code: "IT", name: "Taliansko" },
  { code: "ES", name: "Španielsko" },
  { code: "GB", name: "Veľká Británia" },
  { code: "US", name: "USA" },
  { code: "CA", name: "Kanada" }
]

onMounted(() => {
  const raw = JSON.parse(localStorage.getItem("flightCart") || "[]")

  if (!raw.length) {
    router.push("/cart")
    return
  }

  cart.value = raw.map(f => ({
    ...f,
    baggage_type: f.baggage_type || "hand"
  }))
})

const totalPrice = computed(() =>
  cart.value
    .reduce((sum, f) => {
      const base = Number(f.price) * passengerCount.value
      const baggage =
        f.baggage_type === "checked"
          ? BAGGAGE_FEE * passengerCount.value
          : 0
      return sum + base + baggage
    }, 0)
    .toFixed(2)
)

function continueFlow() {
  errors.value = {}
  formError.value = ""

  if (!form.value.email) errors.value.email = true
  if (!form.value.phone) errors.value.phone = true
  if (!form.value.firstname) errors.value.firstname = true
  if (!form.value.lastname) errors.value.lastname = true
  if (!form.value.nationality) errors.value.nationality = true

  if (!form.value.terms) {
    formError.value =
      "Musíte súhlasiť s podmienkami a ochranou osobných údajov."
    return
  }

  if (Object.keys(errors.value).length) {
    formError.value =
      "Vyplňte prosím všetky povinné údaje označené červenou."
    return
  }

  localStorage.setItem(
    "checkoutData",
    JSON.stringify({
      ...form.value,
      passenger_count: passengerCount.value,
      cart: cart.value,
      total: totalPrice.value
    })
  )

  if (passengerCount.value > 1) {
    router.push("/passengers")
  } else {
    router.push(`/seats/${cart.value[0].id}`)
  }
}
</script>

<style scoped>
.page {
  min-height: 100vh;
  background: transparent; 
  padding: 70px 20px 50px; 
  font-family: "Segoe UI", system-ui, sans-serif;
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  max-width: 100%;
  margin-bottom: 2.5rem;
  text-align: center;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: rgba(30,144,255,0.08);
  color: #1E90FF;
  border: none;
  padding: 12px 20px;
  border-radius: 14px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-bottom: 1rem;
}

.back-btn:hover {
  background: rgba(30,144,255,0.15);
  transform: translateX(-4px);
}

.header h1 {
  font-size: 2.4rem;
  font-weight: 800;
  background: linear-gradient(135deg, #0A2540, #1E90FF);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0 0 0.5rem 0;
}

.header p {
  color: #64748B;
  font-size: 1.1rem;
  font-weight: 500;
  margin: 0;
}

.layout {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2.5rem;
  align-items: start;
}

.card,
.summary {
  background: #FFFFFF;
  border-radius: 24px;
  padding: 2.5rem;
  box-shadow: 0 25px 50px rgba(0,0,0,0.1);
  border: 1px solid rgba(30,144,255,0.15);
  transition: all 0.3s ease;
}

.card:hover,
.summary:hover {
  box-shadow: 0 35px 70px rgba(30,144,255,0.15);
}

h3 {
  margin: 2rem 0 1.5rem 0;
  font-size: 1.3rem;
  font-weight: 700;
  color: #0A2540;
  display: flex;
  align-items: center;
  gap: 12px;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid rgba(30,144,255,0.2);
}

h3:first-child {
  margin-top: 0;
}

.grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.25rem;
}

input,
select {
  padding: 16px 20px;
  border-radius: 16px;
  border: 2px solid #E2E8F0;
  font-size: 1rem;
  background: #FAFCFF;
  transition: all 0.2s ease;
  font-family: inherit;
}

input:focus,
select:focus {
  outline: none;
  border-color: #1E90FF;
  box-shadow: 0 0 0 4px rgba(30,144,255,0.15);
  background: #FFFFFF;
}

.select-wrap {
  position: relative;
}

.select-wrap select {
  padding-right: 48px;
}

.arrow {
  position: absolute;
  right: 18px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: #94A3B8;
  font-size: 1.2rem;
}

.select-wrap.full {
  grid-column: 1 / -1;
}

.baggage {
  margin-bottom: 1.75rem;
  padding: 1.5rem;
  background: rgba(248,250,252,0.5);
  border-radius: 16px;
  border-left: 4px solid #1E90FF;
}

.route {
  font-weight: 600;
  color: #0A2540;
  margin-bottom: 1rem;
  font-size: 1.05rem;
}

.baggage label {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 0.75rem;
  padding: 10px 12px;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-weight: 500;
}

.baggage label:hover {
  background: rgba(30,144,255,0.08);
}

.baggage input[type="radio"] {
  width: 20px;
  height: 20px;
  accent-color: #1E90FF;
}

.terms {
  margin-top: 2rem;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  font-size: 0.95rem;
  color: #64748B;
  padding: 1.25rem;
  background: rgba(248,250,252,0.8);
  border-radius: 16px;
  border: 1px solid #E2E8F0;
}

.terms input[type="checkbox"] {
  margin-top: 2px;
  accent-color: #00C9A7;
  width: 20px;
  height: 20px;
}

.summary {
  height: fit-content;
  position: sticky;
  top: 100px;
}

.summary h3 {
  margin-top: 0;
}

.summary .row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  padding: 1rem 0;
  font-size: 1rem;
  color: #374151;
  font-weight: 500;
  border-bottom: 1px solid rgba(226,232,240,0.5);
}

.summary .row:last-child {
  border-bottom: none;
}

.total {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 3px solid #1E90FF;
  font-size: 2.2rem;
  font-weight: 800;
  color: #0A2540;
  text-align: center;
  background: linear-gradient(135deg, #1E90FF, #00C9A7);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.btn-primary {
  margin-top: 2rem;
  width: 100%;
  padding: 20px 28px;
  font-size: 1.15rem;
  font-weight: 700;
  color: #FFFFFF;
  border: none;
  border-radius: 20px;
  cursor: pointer;
  background: linear-gradient(135deg, #1E90FF, #0A2540);
  box-shadow: 0 15px 35px rgba(30,144,255,0.3);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 25px 50px rgba(30,144,255,0.4);
}

.btn-primary:active {
  transform: translateY(-1px);
}

.form-error {
  max-width: 1100px;
  margin: 0 auto 2rem;
  padding: 1.25rem 1.75rem;
  border-radius: 16px;
  background: linear-gradient(135deg, #FEF2F2, #FECACA);
  color: #DC2626;
  font-weight: 600;
  box-shadow: 0 12px 30px rgba(239,68,68,0.25);
  animation: shake 0.4s ease;
  border: 1px solid #FECACA;
}

body.dark .header h1 {
  background: linear-gradient(135deg, #60a5fa, #22d3ee);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

body.dark .header p {
  color: #94a3b8;
}

body.dark .back-btn {
  background: rgba(59,130,246,0.15);
  color: #60a5fa;
}

body.dark .card,
body.dark .summary {
  background: #1e293b;
  border-color: #334155;
  box-shadow: 0 25px 50px rgba(0,0,0,0.6);
}

body.dark h3 {
  color: #e2e8f0;
  border-bottom: 2px solid rgba(59,130,246,0.3);
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

body.dark .arrow {
  color: #64748b;
}

body.dark .baggage {
  background: rgba(30,41,59,0.6);
  border-left: 4px solid #3b82f6;
}

body.dark .route {
  color: #e2e8f0;
}

body.dark .baggage label:hover {
  background: rgba(59,130,246,0.15);
}

body.dark .terms {
  background: rgba(30,41,59,0.8);
  border-color: #334155;
  color: #94a3b8;
}

body.dark .summary .row {
  color: #cbd5e1;
  border-bottom: 1px solid #334155;
}

body.dark .total {
  background: linear-gradient(135deg, #60a5fa, #22d3ee);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

body.dark .form-error {
  background: rgba(239,68,68,0.15);
  color: #f87171;
  border-color: rgba(239,68,68,0.3);
}

body.dark input.error,
body.dark select.error {
  background: rgba(239,68,68,0.15) !important;
  border-color: #ef4444 !important;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-6px); }
  50% { transform: translateX(6px); }
  75% { transform: translateX(-6px); }
}

input.error,
select.error {
  border-color: #EF4444 !important;
  box-shadow: 0 0 0 4px rgba(239,68,68,0.2) !important;
  background: #FFF1F2 !important;
}

@media (max-width: 900px) {
  .layout {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  
  .summary {
    position: static;
  }
}

@media (max-width: 640px) {
  .page {
    padding: 60px 16px 40px;
  }
  
  .grid {
    grid-template-columns: 1fr;
  }
  
  .header h1 {
    font-size: 2rem;
  }
  
  .card,
  .summary {
    padding: 2rem 1.5rem;
  }
}
</style>
