<template>
  <div class="min-h-screen pt-28 px-6 page">
    <div v-if="loading" class="loading">
      Načítavam sedadlá…
    </div>

    <div v-else-if="flight && currentLayout">
      <div class="flight-info">
        <div>
          <h2>Let {{ flight.flight_number }}</h2>
          <p class="route">{{ flight.departure_city }} → {{ flight.arrival_city }}</p>
          <p class="date">
            Odlet: {{ new Date(flight.departure_time).toLocaleString() }}
          </p>
          <p class="aircraft">
            🛩️ {{ currentLayout.name }} ({{ currentLayout.rows }} radov)
          </p>
        </div>

        <div class="seats-count">
          Vybrané<br />
          <b>{{ selectedSeats.length }} / {{ passengerCount }}</b>
        </div>
      </div>

      <div class="airplane-container">
        <div class="airplane-nose">✈️</div>

        <div class="airplane-body">
          <div class="seat-row header-row">
            <div class="row-label"></div>
            <div class="seat-group">
              <span
                v-for="c in currentLayout.leftSeats"
                :key="'h-l-' + c"
                class="seat-header"
              >
                {{ colsLabels[c - 1] }}
              </span>
            </div>
            <div class="aisle"></div>
            <div class="seat-group">
              <span
                v-for="c in currentLayout.rightSeats"
                :key="'h-r-' + c"
                class="seat-header"
              >
                {{ colsLabels[c - 1] }}
              </span>
            </div>
          </div>

          <div v-for="r in rowsArray" :key="r" class="seat-row">
            <div class="row-label">{{ r }}</div>
            <div class="seat-group">
              <div
                v-for="c in currentLayout.leftSeats"
                :key="'l-' + r + '-' + c"
                :class="seatClass(seatLabel(r, c))"
                @click="toggleSeat(seatLabel(r, c))"
              >
                {{ colsLabels[c - 1] }}{{ r }}
              </div>
            </div>
            <div class="aisle"></div>
            <div class="seat-group">
              <div
                v-for="c in currentLayout.rightSeats"
                :key="'r-' + r + '-' + c"
                :class="seatClass(seatLabel(r, c))"
                @click="toggleSeat(seatLabel(r, c))"
              >
                {{ colsLabels[c - 1] }}{{ r }}
              </div>
            </div>
          </div>
        </div>

        <div class="airplane-tail"></div>
      </div>

      <div class="seat-footer">
        <div class="legend">
          <span><i class="free"></i> Voľné</span>
          <span><i class="taken"></i> Obsadené</span>
          <span><i class="selected"></i> Vybrané</span>
        </div>

        <div class="actions">
          <span class="hint" v-if="selectedSeats.length < passengerCount">
            Vyberte ešte {{ passengerCount - selectedSeats.length }} sedadlo(á)
          </span>
          <span class="hint" v-else>
            Vybrané sedadlá:
            <b>{{ selectedSeats.join(", ") }}</b>
          </span>

          <button
            class="confirm"
            :disabled="selectedSeats.length !== passengerCount || !user || submitting"
            @click="confirmSeats"
          >
            Potvrdiť sedadlá
          </button>

          <button v-if="!user" class="login" @click="goLogin">
            Prihlásiť sa
          </button>
        </div>
      </div>
    </div>

    <div v-else-if="!loading" class="error-msg">
      {{ message || "Načítavam konfiguráciu lietadla..." }}
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from "vue"
import { useRoute, useRouter } from "vue-router"

const route = useRoute()
const router = useRouter()

const flight = ref(null)
const loading = ref(true)
const takenSeats = ref([])
const selectedSeats = ref([])
const message = ref("")
const user = ref(null)
const submitting = ref(false)
const passengerCount = ref(1)
const colsLabels = ref(["A","B","C","D","E","F"])

const currentLayout = computed(() => {
  if (!flight.value) return null
  
  const type = (flight.value.aircraft_type || 'B738').toUpperCase().trim()
  
  console.log('🛩️ DETECTED AIRCRAFT:', type, 'from:', flight.value.aircraft_type)
  
  if (type === 'E190' || type.includes('EMBRAER')) {
    const layout = {
      name: 'Embraer 190',
      rows: 22,
      leftSeats: [1,2],   
      rightSeats: [3,4],  
      colsLabels: ["A","B","C","D"]
    }
    colsLabels.value = layout.colsLabels
    return layout
  }
  
  const layout = {
    name: 'Boeing 737-800',
    rows: 30,
    leftSeats: [1,2,3],     
    rightSeats: [4,5,6],    
    colsLabels: ["A","B","C","D","E","F"]
  }
  colsLabels.value = layout.colsLabels
  return layout
})

const rowsArray = computed(() => {
  return currentLayout.value ? 
    Array.from({ length: currentLayout.value.rows }, (_, i) => i + 1) : []
})

onMounted(async () => {
  try {
    const checkout = JSON.parse(localStorage.getItem("checkoutData") || "{}")
    passengerCount.value = checkout.passenger_count || 1

    if (!checkout.cart || !checkout.cart.length) {
      router.push("/cart")
      return
    }

    const u = localStorage.getItem("user")
    if (u) user.value = JSON.parse(u)

    const id = route.params.id
    if (!id) {
      router.push("/")
      return
    }

    const fRes = await fetch(`/api/get_flight.php?id=${id}`)
    const fData = await fRes.json()

    if (!fData.success) {
      message.value = fData.message
      loading.value = false
      return
    }

    flight.value = fData.flight
    console.log('✈️ FLIGHT LOADED:', flight.value)

    const sRes = await fetch(`/api/get_taken_seats.php?flight_id=${id}`)
    const seatsData = await sRes.json()
    takenSeats.value = Array.isArray(seatsData) ? seatsData : []

  } catch (error) {
    console.error('Chyba načítania:', error)
    message.value = "Chyba pri načítaní sedadiel."
  } finally {
    loading.value = false
  }
})

function seatLabel(row, colIndex) {
  return `${colsLabels.value[colIndex - 1]}${row}`
}

function seatClass(label) {
  if (selectedSeats.value.includes(label)) return "seat selected"
  if (takenSeats.value.includes(label)) return "seat taken"
  return "seat free"
}

function toggleSeat(label) {
  if (takenSeats.value.includes(label)) return

  const index = selectedSeats.value.indexOf(label)
  if (index > -1) {
    selectedSeats.value.splice(index, 1)
  } else if (selectedSeats.value.length < passengerCount.value) {
    selectedSeats.value.push(label)
  }
}

async function confirmSeats() {
  if (selectedSeats.value.length !== passengerCount.value || submitting.value) return

  const userObj = JSON.parse(localStorage.getItem("user"))
  if (!userObj) {
    router.push("/login")
    return
  }

  submitting.value = true

  try {
    let holdUntil = null
    for (const seat of selectedSeats.value) {
      const res = await fetch("/api/hold_seat.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          user_id: userObj.id,
          flight_id: flight.value.id,
          seat_number: seat
        })
      })

      const data = await res.json()
      if (data.hold_until) holdUntil = data.hold_until
    }

    localStorage.setItem("selectedSeats", JSON.stringify(selectedSeats.value))
    localStorage.setItem("seatFlightId", flight.value.id.toString())
    localStorage.setItem("seatHoldUntil", String(holdUntil))

    router.push("/payment")
  } catch (error) {
    console.error('Chyba potvrdenia:', error)
  } finally {
    submitting.value = false
  }
}

onBeforeUnmount(() => {
  if (router.currentRoute.value.path === "/payment") return

  const seats = JSON.parse(localStorage.getItem("selectedSeats") || "[]")
  const flightId = localStorage.getItem("seatFlightId")
  if (!seats.length || !flightId) return

  seats.forEach(seat => {
    fetch("/api/release_seat.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        flight_id: flightId,
        seat_number: seat
      })
    })
  })

  localStorage.removeItem("selectedSeats")
  localStorage.removeItem("seatFlightId")
  localStorage.removeItem("seatHoldUntil")
})

function goLogin() {
  router.push("/login")
}
</script>

<style scoped>
.page {
  background: transparent;
  font-family: "Space Grotesk", -apple-system, BlinkMacSystemFont, sans-serif;
}

.loading {
  text-align: center;
  font-size: 20px;
  color: #64748b;
  padding: 60px 20px;
}

.flight-info {
  max-width: 1000px;
  margin: 0 auto 32px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  padding: 32px 40px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  box-shadow: 0 25px 60px rgba(0,0,0,0.15);
  border: 1px solid rgba(255,255,255,0.2);
}

.flight-info h2 {
  color: #0a2540;
  font-size: 28px;
  font-weight: 700;
  margin: 0 0 12px 0;
}

.route {
  font-weight: 600;
  font-size: 18px;
  color: #1e40af;
  margin: 0 0 8px 0;
}

.date, .aircraft {
  font-size: 15px;
  color: #64748b;
  margin: 4px 0 0 0;
}

.seats-count {
  text-align: right;
  font-size: 15px;
  color: #374151;
  font-weight: 500;
}

.seats-count b {
  font-size: 24px;
  color: #f59e0b;
}

.airplane-container {
  max-width: 900px;
  margin: 0 auto 40px;
  position: relative;
}

.airplane-nose {
  position: absolute;
  top: -60px;
  left: 50%;
  transform: translateX(-50%);
  width: 110px;
  height: 110px;
  border-radius: 50%;
  background: linear-gradient(145deg, #ffffff, #f8fafc);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 36px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.2);
  border: 3px solid rgba(255,255,255,0.3);
  z-index: 10;
}

.airplane-body {
  background: rgba(255, 255, 255, 0.97);
  backdrop-filter: blur(25px);
  border-radius: 32px;
  padding: 36px 28px;
  max-height: 550px;
  overflow-y: auto;
  box-shadow: 0 30px 80px rgba(0,0,0,0.18);
  border: 1px solid rgba(255,255,255,0.25);
}

.airplane-tail {
  width: 60px;
  height: 60px;
  margin: 0 auto;
  border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
  background: linear-gradient(145deg, #ffffff, #f8fafc);
  box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}

.seat-row {
  display: grid;
  grid-template-columns: 40px auto 44px auto;
  gap: 12px;
  align-items: center;
  margin-bottom: 8px;
}

.row-label {
  text-align: center;
  font-size: 13px;
  font-weight: 700;
  color: #6b7280;
  width: 40px;
}

.seat-header {
  width: 44px;
  height: 32px;
  border-radius: 10px;
  background: rgba(59,130,246,0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
  color: #1e40af;
}

.seat-group {
  display: flex;
  gap: 10px;
  justify-content: center;
}

.aisle {
  background: linear-gradient(90deg, #e5e7eb, #f3f4f6);
  border-radius: 16px;
  height: 44px;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
}

.seat {
  width: 44px;
  height: 40px;
  border-radius: 14px;
  font-size: 13px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  position: relative;
  overflow: hidden;
}

.seat::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  border-radius: 14px;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.seat:hover::before {
  opacity: 0.1;
}

.seat.free {
  background: linear-gradient(145deg, #ecfdf5, #d1fae5);
  border: 2px solid #10b981;
  color: #047857;
}

.seat.free:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(16,185,129,0.3);
}

.seat.taken {
  background: linear-gradient(145deg, #fee2e2, #fecaca);
  border: 2px solid #ef4444;
  color: #dc2626;
  cursor: not-allowed;
  box-shadow: 0 4px 12px rgba(239,68,68,0.3);
}

.seat.selected {
  background: linear-gradient(145deg, #3b82f6, #f59e0b);
  border: 2px solid #ffffff;
  color: white;
  box-shadow: 0 8px 25px rgba(59,130,246,0.4);
  transform: scale(1.05);
}

.seat-footer {
  max-width: 1000px;
  margin: 40px auto 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.12);
  border: 1px solid rgba(255,255,255,0.3);
}

.legend span {
  margin-right: 24px;
  font-size: 15px;
  display: flex;
  align-items: center;
}

.legend i {
  width: 18px;
  height: 18px;
  display: inline-block;
  margin-right: 8px;
  border-radius: 6px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.legend .free { background: #10b981; }
.legend .taken { background: #ef4444; }
.legend .selected { 
  background: linear-gradient(145deg, #3b82f6, #f59e0b);
  box-shadow: 0 4px 12px rgba(59,130,246,0.4);
}

.hint {
  font-size: 15px;
  color: #374151;
  font-weight: 500;
}

.hint b {
  color: #f59e0b;
  font-weight: 700;
}

.actions {
  display: flex;
  gap: 16px;
  align-items: center;
}

.confirm {
  background: linear-gradient(145deg, #f59e0b, #d97706);
  color: white;
  border: none;
  padding: 16px 28px;
  border-radius: 16px;
  font-weight: 700;
  font-size: 16px;
  cursor: pointer;
  box-shadow: 0 10px 30px rgba(245,158,11,0.4);
  transition: all 0.3s ease;
}

.confirm:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 15px 40px rgba(245,158,11,0.5);
}

.confirm:disabled {
  opacity: 0.4;
  cursor: not-allowed;
  transform: none;
}

.login {
  background: linear-gradient(145deg, #3b82f6, #2563eb);
  color: white;
  border: none;
  padding: 14px 24px;
  border-radius: 14px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 8px 25px rgba(59,130,246,0.3);
  transition: all 0.2s ease;
}

.login:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 35px rgba(59,130,246,0.4);
}

.error-msg {
  margin-top: 40px;
  text-align: center;
  color: #dc2626;
  font-size: 16px;
  padding: 40px;
  background: rgba(239,68,68,0.1);
  border-radius: 16px;
  border: 1px solid rgba(220,38,38,0.2);
}

.airplane-body::-webkit-scrollbar {
  width: 8px;
}

.airplane-body::-webkit-scrollbar-track {
  background: rgba(0,0,0,0.05);
  border-radius: 10px;
}

.airplane-body::-webkit-scrollbar-thumb {
  background: rgba(0,0,0,0.15);
  border-radius: 10px;
}

.airplane-body::-webkit-scrollbar-thumb:hover {
  background: rgba(0,0,0,0.25);
}
</style>
