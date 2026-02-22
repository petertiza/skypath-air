<template>
  <div class="payment-page">
    <router-link to="/checkout" class="back-link">
      ← Späť na údaje
    </router-link>

    <div class="layout">
      <div class="payment-card">
        <h1>Platba</h1>

        <div class="loyalty-section" v-if="!expired && !processing && !success && userPoints > 0">
          <h3>💎 Loyalty Program</h3>
          <div class="points-info">
            Máte <strong>{{ userPoints }}</strong> bodov 
            <span>(1 bod = 0.10€ zľava)</span>
          </div>
          
          <div class="discount-control">
            <label class="discount-checkbox">
              <input type="checkbox" v-model="usePoints" @change="calculateDiscount" />
              <span>Použiť body na zľavu</span>
              <strong v-if="discountAmount > 0">(-{{ discountAmount.toFixed(2) }}€)</strong>
            </label>
            
            <div v-if="usePoints" class="slider-container">
              <input 
                v-model.number="pointsToUse" 
                type="range" 
                :max="Math.min(userPoints, maxPoints)"
                @input="calculateDiscount"
                class="points-slider"
              />
              <div class="slider-display">
                {{ pointsToUse }} bodov = -{{ discountAmount.toFixed(2) }}€
              </div>
            </div>
          </div>
          
          <div v-if="discountAmount > 0" class="discount-applied">
            ✅ Zľava z body: <strong>-{{ discountAmount.toFixed(2) }}€</strong>
          </div>
        </div>

        <div v-if="expired" class="expired-banner">
          ⏱️ Čas na dokončenie platby vypršal. Sedadlá boli uvoľnené.
        </div>

        <div v-if="processing" class="processing-box">
          <div class="spinner"></div>
          <h3>Spracúvame platbu…</h3>
          <p>Prosím, neobnovujte stránku</p>
        </div>

        <div v-if="success" class="success-box">
          <div class="checkmark">✔</div>
          <h3>Platba prebehla úspešne</h3>
          <p v-if="pointsEarned > 0" class="points-message">
            ✨ Získali ste <strong>{{ pointsEarned }}</strong> bodov!
          </p>
          <p>Presmerovávame vás…</p>
        </div>

        <template v-if="!expired && !processing && !success">
          <div class="summary">
            <h3>Zhrnutie objednávky</h3>

            <div v-for="f in cart" :key="f.id" class="summary-row">
              <div>
                ✈️ {{ f.departure_city }} → {{ f.arrival_city }}<br />
                <small>
                  Sedadlá: <b>{{ selectedSeats.join(", ") }}</b><br />
                  Cestujúci: <b>{{ passengerCount }}</b>
                </small>
              </div>
              <div class="price">
                {{ flightTotal(f) }} €
              </div>
            </div>

            <div class="summary-total">
              Pôvodne: <span>{{ originalTotalPrice }} €</span>
            </div>
            <div v-if="discountAmount > 0" class="summary-discount">
              Zľava: <span class="discount">-{{ discountAmount.toFixed(2) }} €</span>
            </div>
            <div class="summary-final">
              <strong>K úhrade: {{ finalPrice.toFixed(2) }} €</strong>
            </div>
          </div>

          <form @submit.prevent="pay">
            <h3>Platobné údaje</h3>

            <div class="card-input">
              <label>Číslo karty</label>
              <input
                v-model="cardNumber"
                placeholder="4111 1111 1111 1111"
                maxlength="19"
                inputmode="numeric"
                @input="formatCard"
                :class="{ error: errors.cardNumber }"
              />
              <p v-if="errors.cardNumber" class="error-msg">
                {{ errors.cardNumber }}
              </p>
            </div>

            <div class="row">
              <div class="card-input">
                <label>Platnosť</label>
                <input
                  v-model="expiry"
                  placeholder="MM / YY"
                  maxlength="7"
                  @input="formatExpiry"
                  :class="{ error: errors.expiry }"
                />
                <p v-if="errors.expiry" class="error-msg">
                  {{ errors.expiry }}
                </p>
              </div>

              <div class="card-input">
                <label>CVC</label>
                <input
                  v-model="cvc"
                  placeholder="CVC"
                  maxlength="4"
                  inputmode="numeric"
                  :class="{ error: errors.cvc }"
                />
                <p v-if="errors.cvc" class="error-msg">
                  {{ errors.cvc }}
                </p>
              </div>
            </div>

            <button type="submit" :disabled="processing">
              Zaplatiť <strong>{{ finalPrice.toFixed(2) }} €</strong>
            </button>
          </form>
        </template>
      </div>

      <div class="timer-box" v-if="!expired && !processing && !success">
        <h4>Rezervácia sedadiel</h4>
        <p class="time">{{ minutes }}:{{ seconds }}</p>
        <small>
          Dokončite platbu do 15 minút
        </small>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from "vue"
import { useRouter } from "vue-router"

const router = useRouter()

const cart = ref([])
const selectedSeats = ref([])
const passengerCount = ref(1)

const expired = ref(false)
const processing = ref(false)
const success = ref(false)
const pointsEarned = ref(0)

const timeLeftSeconds = ref(15 * 60) 
let interval = null

const cardNumber = ref("")
const expiry = ref("")
const cvc = ref("")
const errors = ref({})

const userPoints = ref(0)
const usePoints = ref(false)
const pointsToUse = ref(0)
const discountAmount = ref(0)

const BAGGAGE_FEE = 25

onMounted(async () => {
  const checkout = JSON.parse(localStorage.getItem("checkoutData") || "{}")
  const seats = JSON.parse(localStorage.getItem("selectedSeats") || "[]")
  const holdUntilRaw = localStorage.getItem("seatHoldUntil")

  if (!checkout.cart || !checkout.cart.length || !seats.length) {
    router.push("/cart")
    return
  }

  cart.value = checkout.cart
  passengerCount.value = checkout.passenger_count || 1
  selectedSeats.value = seats

  const user = JSON.parse(localStorage.getItem("user") || "{}")
  if (user.id) {
    try {
      const res = await fetch("/api/get_points.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ user_id: user.id })
      })
      const data = await res.json()
      userPoints.value = data.points || 0
    } catch (e) {
      console.error("Chyba načítania bodov:", e)
    }
  }

  interval = setInterval(() => {
    timeLeftSeconds.value--
    if (timeLeftSeconds.value <= 0) {
      clearInterval(interval)
      expired.value = true
      router.push("/cart") 
    }
  }, 1000)
})

onBeforeUnmount(() => {
  if (interval) clearInterval(interval)
})

const minutes = computed(() =>
  String(Math.floor(timeLeftSeconds.value / 60)).padStart(2, "0")
)
const seconds = computed(() =>
  String(timeLeftSeconds.value % 60).padStart(2, "0")
)

const originalTotalPrice = computed(() =>
  cart.value.reduce((sum, f) => {
    const base = Number(f.price) + (f.baggage_type === "checked" ? BAGGAGE_FEE : 0)
    return sum + base * passengerCount.value
  }, 0).toFixed(2)
)

const flightTotal = (f) => {
  const base = Number(f.price) + (f.baggage_type === "checked" ? BAGGAGE_FEE : 0)
  return (base * passengerCount.value).toFixed(2)
}

const maxPoints = computed(() => Math.floor(Number(originalTotalPrice.value) / 0.1))
const finalPrice = computed(() => Number(originalTotalPrice.value) - discountAmount.value)

function calculateDiscount() {
  if (!usePoints.value || pointsToUse.value <= 0) {
    discountAmount.value = 0
    return
  }
  discountAmount.value = Math.min(pointsToUse.value * 0.1, Number(originalTotalPrice.value))
}

watch([usePoints, pointsToUse], calculateDiscount)

function formatCard() {
  cardNumber.value = cardNumber.value
    .replace(/\D/g, "")
    .replace(/(.{4})/g, "$1 ")
    .trim()
}

function formatExpiry() {
  const v = expiry.value.replace(/\D/g, "").slice(0, 4)
  expiry.value = v.length >= 3 ? `${v.slice(0, 2)} / ${v.slice(2)}` : v
}

function luhnCheck(num) {
  const arr = num.split("").reverse().map(Number)
  let sum = 0
  for (let i = 0; i < arr.length; i++) {
    let n = arr[i]
    if (i % 2) {
      n *= 2
      if (n > 9) n -= 9
    }
    sum += n
  }
  return sum % 10 === 0
}

function validate() {
  errors.value = {}

  const clean = cardNumber.value.replace(/\s+/g, "")
  if (!clean || clean.length < 13 || !luhnCheck(clean)) {
    errors.value.cardNumber = "Neplatné číslo karty"
  }

  if (!/^(0[1-9]|1[0-2]) \/ \d{2}$/.test(expiry.value)) {
    errors.value.expiry = "Neplatná platnosť"
  }

  if (!/^\d{3,4}$/.test(cvc.value)) {
    errors.value.cvc = "Neplatný CVC"
  }

  return Object.keys(errors.value).length === 0
}

async function pay() {
  if (processing.value || !validate()) return

  const user = JSON.parse(localStorage.getItem("user"))
  const checkout = JSON.parse(localStorage.getItem("checkoutData"))
  const passengers = JSON.parse(localStorage.getItem("passengers") || "[]")

  if (!user || !checkout) {
    alert("Relácia vypršala, prosím začnite odznova.")
    router.push("/cart")
    return
  }

  processing.value = true

  const res = await fetch("/api/confirm_payment.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      user_id: user.id,
      flight_id: cart.value[0].id,
      seats: selectedSeats.value,
      passengers,
      passenger_count: passengerCount.value,
      total_price: originalTotalPrice.value,
      loyalty_points_used: usePoints.value ? pointsToUse.value : 0,
      loyalty_discount: discountAmount.value,
      email: checkout.email,
      phone: checkout.phone,
      firstname: checkout.firstname,
      lastname: checkout.lastname,
      birthdate: checkout.birthdate,
      nationality: checkout.nationality
    })
  })

  const data = await res.json()
  processing.value = false

  if (!data.success) {
    console.error("PAYMENT ERROR:", data)
    alert(data.debug || data.message || "Platba zlyhala")
    return
  }

  pointsEarned.value = data.points_earned || 0
  success.value = true

  setTimeout(() => {

    localStorage.removeItem("flightCart")
    window.dispatchEvent(new Event("cart-updated"))

    localStorage.removeItem("checkoutData")
    localStorage.removeItem("selectedSeats")
    localStorage.removeItem("seatHoldUntil")
    localStorage.removeItem("seatFlightId")
    localStorage.removeItem("passengers")

    router.push(`/reservation-success/${data.order_id}`)

  }, 2000)
}
</script>

<style scoped>
.loyalty-section {
  background: linear-gradient(135deg, #E3F2FD, #F0F8FF);
  border: 2px solid #1E90FF;
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 24px;
}

.loyalty-section h3 {
  color: #1E90FF;
  margin: 0 0 12px 0;
  font-size: 18px;
}

.points-info {
  background: rgba(30, 144, 255, 0.1);
  padding: 12px 16px;
  border-radius: 12px;
  margin-bottom: 16px;
  font-weight: 500;
}

.points-info strong {
  color: #1E90FF;
  font-size: 20px;
}

.discount-control {
  margin-bottom: 16px;
}

.discount-checkbox {
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 600;
  color: #0A2540;
  cursor: pointer;
  padding: 12px;
  border-radius: 10px;
  transition: background 0.2s;
}

.discount-checkbox:hover {
  background: rgba(30, 144, 255, 0.05);
}

.slider-container {
  margin-top: 12px;
  padding: 16px;
  background: white;
  border-radius: 12px;
  border: 1px solid #E2E8F0;
}

.points-slider {
  width: 100%;
  height: 6px;
  border-radius: 3px;
  background: #E2E8F0;
  outline: none;
}

.points-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #1E90FF;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(30, 144, 255, 0.4);
}

.slider-display {
  text-align: center;
  margin-top: 8px;
  font-weight: 700;
  color: #1E90FF;
  font-size: 16px;
}

.discount-applied {
  background: linear-gradient(90deg, #10B981, #059669);
  color: white;
  padding: 12px 16px;
  border-radius: 12px;
  text-align: center;
  font-weight: 600;
}

.summary-discount {
  color: #10B981;
  font-size: 18px;
  font-weight: 700;
}

.summary-final {
  background: linear-gradient(135deg, #1E90FF, #FF6B35);
  color: white;
  padding: 20px;
  border-radius: 16px;
  text-align: center;
  font-size: 24px;
  margin-top: 12px;
}

.points-message {
  background: linear-gradient(135deg, #F0F9FF, #E0F2FE);
  border: 2px solid #1E90FF;
  border-radius: 12px;
  padding: 12px;
  margin: 12px 0;
  color: #1E90FF;
  font-weight: 600;
}

.payment-page {
  min-height: 100vh;
  background: transparent;
  padding: 140px 24px 60px;
  font-family: "Space Grotesk", -apple-system, BlinkMacSystemFont, sans-serif;
}

.layout {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 2.2fr 1fr;
  gap: 40px;
  align-items: start;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #3b82f6;
  font-weight: 600;
  font-size: 15px;
  padding: 12px 20px;
  background: rgba(59,130,246,0.1);
  border-radius: 16px;
  text-decoration: none;
  transition: all 0.2s ease;
  margin-bottom: 24px;
}

.back-link:hover {
  background: rgba(59,130,246,0.2);
  transform: translateX(-4px);
}

.payment-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(25px);
  border-radius: 28px;
  padding: 44px;
  box-shadow: 0 35px 80px rgba(0,0,0,0.15);
  border: 1px solid rgba(255,255,255,0.3);
}

.payment-card h1 {
  color: #0a2540;
  font-size: 32px;
  font-weight: 800;
  margin: 0 0 32px 0;
  background: linear-gradient(135deg, #0a2540, #1e40af);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.expired-banner, .processing-box, .success-box {
  text-align: center;
  padding: 48px 32px;
  border-radius: 24px;
  margin-bottom: 32px;
  backdrop-filter: blur(20px);
}

.expired-banner {
  background: linear-gradient(135deg, rgba(239,68,68,0.15), rgba(248,113,113,0.15));
  border: 2px solid rgba(239,68,68,0.3);
  color: #dc2626;
}

.processing-box {
  background: linear-gradient(135deg, rgba(59,130,246,0.15), rgba(147,197,253,0.15));
  border: 2px solid rgba(59,130,246,0.3);
  color: #1e40af;
}

.success-box {
  background: linear-gradient(135deg, rgba(34,197,94,0.2), rgba(74,222,128,0.2));
  border: 2px solid rgba(34,197,94,0.4);
  color: #166534;
}

.spinner {
  width: 56px;
  height: 56px;
  border: 4px solid rgba(59,130,246,0.2);
  border-top-color: #3b82f6;
  border-radius: 50%;
  margin: 0 auto 24px;
  animation: spin 1s linear infinite;
}

.checkmark {
  font-size: 64px;
  margin-bottom: 20px;
}

.summary {
  margin-bottom: 40px;
  padding: 28px;
  background: rgba(248,250,252,0.7);
  border-radius: 20px;
  border: 1px solid rgba(226,232,240,0.5);
}

.summary h3 {
  color: #0a2540;
  font-size: 20px;
  margin: 0 0 24px 0;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
  padding: 16px 0;
  border-bottom: 1px solid rgba(0,0,0,0.05);
}

.summary-row:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.summary-row small {
  color: #64748b;
  font-size: 14px;
}

.summary-total {
  font-size: 28px;
  font-weight: 800;
  margin-top: 24px;
  padding: 20px 0;
  border-top: 3px solid #f8fafc;
  color: #0a2540;
}

.summary-total span {
  color: #f59e0b;
}

form h3 {
  color: #0a2540;
  font-size: 20px;
  margin: 0 0 28px 0;
}

.card-input {
  margin-bottom: 24px;
}

.card-input label {
  display: block;
  font-weight: 600;
  color: #374151;
  margin-bottom: 8px;
  font-size: 14px;
}

.card-input input {
  width: 100%;
  padding: 16px 20px;
  border-radius: 16px;
  border: 2px solid rgba(226,232,240,0.8);
  font-size: 16px;
  font-weight: 500;
  background: rgba(255,255,255,0.9);
  backdrop-filter: blur(10px);
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.card-input input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 8px 25px rgba(59,130,246,0.15);
  transform: translateY(-1px);
}

input.error {
  border-color: #ef4444 !important;
  background: rgba(254,242,242,0.9);
  box-shadow: 0 4px 12px rgba(239,68,68,0.2);
}

.error-msg {
  color: #dc2626;
  font-size: 13px;
  margin-top: 6px;
  font-weight: 500;
}

.row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

button {
  margin-top: 32px;
  width: 100%;
  padding: 20px;
  border-radius: 20px;
  font-size: 18px;
  font-weight: 800;
  color: white;
  border: none;
  cursor: pointer;
  background: linear-gradient(145deg, #f59e0b, #d97706);
  box-shadow: 0 15px 40px rgba(245,158,11,0.4);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

button::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
  transition: left 0.5s;
}

button:hover::before {
  left: 100%;
}

button:hover {
  transform: translateY(-3px);
  box-shadow: 0 25px 60px rgba(245,158,11,0.5);
}

button:active {
  transform: translateY(-1px);
}

.timer-box {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(25px);
  border-radius: 28px;
  padding: 36px 28px;
  text-align: center;
  box-shadow: 0 25px 60px rgba(0,0,0,0.15);
  border: 1px solid rgba(255,255,255,0.3);
  height: fit-content;
}

.timer-box h4 {
  color: #0a2540;
  font-size: 16px;
  margin: 0 0 16px 0;
  font-weight: 700;
}

.time {
  font-size: 48px;
  font-weight: 900;
  background: linear-gradient(145deg, #ef4444, #dc2626);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0 0 12px 0;
  font-family: "Space Grotesk", monospace;
}

.timer-box small {
  color: #64748b;
  font-size: 13px;
  line-height: 1.5;
}

body.dark .payment-card,
body.dark .timer-box {
  background: #1e293b;
  border-color: #334155;
  box-shadow: 0 25px 60px rgba(0,0,0,0.6);
}

body.dark .payment-card h1 {
  color: #e2e8f0;
  -webkit-text-fill-color: unset;
  background: none;
}

body.dark .summary {
  background: #0f172a;
  border-color: #334155;
}

body.dark .summary h3,
body.dark form h3 {
  color: #e2e8f0;
}

body.dark .summary-row {
  border-bottom: 1px solid #334155;
}

body.dark .summary-total {
  color: #e2e8f0;
}

body.dark .card-input label {
  color: #cbd5e1;
}

body.dark .card-input input {
  background: #0f172a;
  border-color: #334155;
  color: #f1f5f9;
}

body.dark .card-input input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.3);
}

body.dark .time {
  -webkit-text-fill-color: unset;
  background: none;
  color: #f87171;
}

body.dark .back-link {
  background: rgba(59,130,246,0.15);
  color: #60a5fa;
}

body.dark .loyalty-section {
  background: #0f172a;
  border-color: #3b82f6;
}

body.dark .slider-container {
  background: #1e293b;
  border-color: #334155;
}


@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 1024px) {
  .layout {
    grid-template-columns: 1fr;
    gap: 32px;
  }
  
  .timer-box {
    order: -1;
  }
}

@media (max-width: 768px) {
  .payment-page {
    padding: 120px 20px 40px;
  }
  
  .payment-card {
    padding: 32px 24px;
  }
  
  .row {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}
</style>
