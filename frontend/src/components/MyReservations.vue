<template>
  <div class="reservations-page">
    <h1 class="title">Moje rezervácie</h1>

    <div v-if="loading" class="loading">
      Načítavam rezervácie…
    </div>

    <div v-else-if="activeReservations.length === 0" class="empty">
      Nemáte žiadne rezervácie.
    </div>

    <div v-else class="reservations-grid">
      <div
        v-for="r in activeReservations"
        :key="r.reservation_id"
        class="reservation-card"
      >
        <div class="card-header">
          <div>
            <div class="route">
              {{ r.departure_city }} → {{ r.arrival_city }}
            </div>
            <div class="flight-number">
              Let {{ r.flight_number }}
            </div>
          </div>
          <div class="airplane">✈️</div>
        </div>

        <div class="card-body">
          <div class="row">
            <span>Odlet</span>
            <strong>{{ formatDate(r.departure_time) }}</strong>
          </div>

          <div class="row">
            <span>Príchod</span>
            <strong>{{ formatDate(r.arrival_time) }}</strong>
          </div>
        </div>

        <div class="card-actions">
          <button
  v-if="r.order_id"
  class="checkin-btn"
  :disabled="r.checked_in === 1 || !canCheckIn(r.departure_time)"
  @click="goToCheckin(r.reservation_id)"
>
  <template v-if="r.checked_in === 1">
    Check-in dokončený
  </template>

  <template v-else-if="!canCheckIn(r.departure_time)">
    Check-in bude dostupný 3 dni pred odletom
  </template>

  <template v-else>
    Check-in
  </template>
</button>


          <span v-else class="info-text">
            Čaká sa na dokončenie rezervácie
          </span>
        </div>
      </div>
    </div>

    <p v-if="message" class="message">{{ message }}</p>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue"
import { useRouter } from "vue-router"

const router = useRouter()

const reservations = ref([])

const activeReservations = computed(() => {
  const now = new Date()

  return reservations.value.filter(r => {
    return new Date(r.arrival_time) > now
  })
})

function canCheckIn(departureTime) {
  const now = new Date()
  const departure = new Date(departureTime)

  const diffMs = departure - now
  const diffHours = diffMs / (1000 * 60 * 60)

  return diffHours <= 72 && diffHours > 0
}

const loading = ref(true)
const message = ref("")

onMounted(loadReservations)

async function loadReservations() {
  const user = JSON.parse(localStorage.getItem("user"))
  if (!user || !user.id) {
    router.push("/login")
    return
  }

  try {
    const res = await fetch("/api/my_reservations.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ user_id: user.id })
    })

    reservations.value = await res.json()
  } catch (e) {
    message.value = "Nepodarilo sa načítať rezervácie."
  } finally {
    loading.value = false
  }
}

function formatDate(date) {
  return new Date(date).toLocaleString("sk-SK", {
    dateStyle: "medium",
    timeStyle: "short"
  })
}

function goToCheckin(reservationId) {
  if (!reservationId) return
  router.push(`/check-in/${reservationId}`)
}
</script>


<style scoped>
.reservations-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 80px 20px 60px;
  background: transparent;
  font-family: "Space Grotesk", system-ui, sans-serif;
}

.title {
  font-size: 2.5rem;
  font-weight: 800;
  background: linear-gradient(135deg, #0A2540, #1E90FF);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 3rem;
  text-align: center;
  position: relative;
}

.title::after {
  content: '';
  position: absolute;
  bottom: -12px;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 4px;
  background: linear-gradient(90deg, #1E90FF, #00C9A7);
  border-radius: 2px;
}

.loading,
.empty {
  text-align: center;
  color: #6B7280;
  font-size: 1.3rem;
  padding: 160px 0;
  background: rgba(248,250,252,0.5);
  border-radius: 24px;
  border: 1px dashed #D1D5DB;
}

.empty::before {
  content: "✈️";
  display: block;
  font-size: 5rem;
  margin-bottom: 2rem;
  opacity: 0.3;
}

.reservations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 2rem;
}

.reservation-card {
  background: #FFFFFF;
  border-radius: 24px;
  box-shadow: 0 25px 50px rgba(0,0,0,0.12);
  border: 1px solid rgba(30,144,255,0.15);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: all 0.3s ease;
  position: relative;
}

.reservation-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 6px;
  background: linear-gradient(90deg, #1E90FF, #00C9A7, #0A2540);
  z-index: 10;
}

.reservation-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 35px 70px rgba(30,144,255,0.2);
}

.card-header {
  background: linear-gradient(135deg, #0A2540 0%, #1E40AF 50%, #1E90FF 100%);
  color: #FFFFFF;
  padding: 2rem 2rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  position: relative;
  overflow: hidden;
}

.card-header::after {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 60px;
  height: 60px;
  background: rgba(255,255,255,0.1);
  border-radius: 0 24px 0 24px;
  transform: skewX(-20deg);
}

.route {
  font-size: 1.4rem;
  font-weight: 700;
  margin-bottom: 4px;
  text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.flight-number {
  font-size: 1rem;
  opacity: 0.95;
  font-weight: 500;
  letter-spacing: 0.5px;
}

.airplane {
  font-size: 2.2rem;
  animation: float 3s ease-in-out infinite;
  filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
}

@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-8px); }
}

.card-body {
  padding: 2rem;
  flex: 1;
}

.row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  padding: 12px 0;
  border-bottom: 1px solid rgba(226,232,240,0.5);
}

.row:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.row span {
  color: #64748B;
  font-weight: 500;
  font-size: 0.95rem;
}

.row strong {
  color: #0A2540;
  font-weight: 700;
  font-size: 1.1rem;
}

.card-actions {
  margin-top: auto;
  padding: 1.5rem 2rem 2rem;
  border-top: 1px solid rgba(226,232,240,0.8);
  background: rgba(30,144,255,0.02);
}

.checkin-btn {
  width: 100%;
  background: linear-gradient(135deg, #00C9A7, #10B981);
  color: #FFFFFF;
  border: none;
  padding: 14px 24px;
  border-radius: 16px;
  cursor: pointer;
  font-weight: 700;
  font-size: 1rem;
  box-shadow: 0 10px 25px rgba(0,201,167,0.3);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.checkin-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 15px 35px rgba(0,201,167,0.4);
}

.checkin-btn:disabled {
  background: #D1D5DB !important;
  color: #9CA3AF !important;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.checkin-btn:disabled::after {
  content: "✓";
  margin-left: 8px;
}

.info-text {
  text-align: center;
  color: #6B7280;
  font-weight: 500;
  font-style: italic;
  padding: 16px;
  background: rgba(226,232,240,0.5);
  border-radius: 12px;
  border-left: 4px solid #F59E0B;
}

.message {
  margin-top: 2.5rem;
  text-align: center;
  font-weight: 600;
  font-size: 1.1rem;
  padding: 1rem 2rem;
  background: rgba(30,144,255,0.1);
  border-radius: 16px;
  border: 1px solid rgba(30,144,255,0.2);
  color: #1E90FF;
}

body.dark .reservation-card {
  background: #1e293b;
  border-color: #334155;
}

body.dark .card-body {
  background: #1e293b;
}

body.dark .row span {
  color: #94a3b8;
}

body.dark .row strong {
  color: #f1f5f9;
}

body.dark .loading,
body.dark .empty {
  background: rgba(30,41,59,0.5);
  border-color: #334155;
  color: #cbd5e1;
}

body.dark .info-text {
  background: rgba(30,41,59,0.5);
  color: #cbd5e1;
}

body.dark .message {
  background: rgba(30,144,255,0.15);
  border-color: rgba(30,144,255,0.4);
  color: #60a5fa;
}

body.dark .reservations-page {
  background: #0f172a;
}

body.dark .reservation-card {
  background: #1e293b;
  box-shadow: 0 30px 60px rgba(0,0,0,0.6);
}

body.dark .reservation-card::before {
  background: linear-gradient(90deg, #3b82f6, #22d3ee, #0f172a);
}

body.dark .card-header {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #3b82f6 100%);
}

body.dark .row {
  border-bottom: 1px solid #334155;
}

body.dark .row span {
  color: #94a3b8;
}

body.dark .row strong {
  color: #f1f5f9;
}

body.dark .card-actions {
  background: #0f172a;
  border-top: 1px solid #334155;
}

body.dark .checkin-btn:disabled {
  background: #334155 !important;
  color: #94a3b8 !important;
}

body.dark .info-text {
  background: #1e293b;
  border-left: 4px solid #fbbf24;
  color: #cbd5e1;
}

body.dark .loading,
body.dark .empty {
  background: #1e293b;
  border: 1px dashed #334155;
  color: #cbd5e1;
}

body.dark .message {
  background: rgba(59,130,246,0.15);
  border: 1px solid rgba(59,130,246,0.35);
  color: #60a5fa;
}


@media (max-width: 900px) {
  .reservations-grid {
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 1.5rem;
  }
  
  .title {
    font-size: 2rem;
  }
}

@media (max-width: 640px) {
  .reservations-page {
    padding: 60px 16px 40px;
  }
  
  .reservations-grid {
    grid-template-columns: 1fr;
    gap: 1.25rem;
  }
  
  .title {
    font-size: 1.8rem;
  }
  
  .card-header {
    padding: 1.5rem 1.5rem 1.25rem;
  }
  
  .card-body {
    padding: 1.5rem;
  }
  
  .route {
    font-size: 1.25rem;
  }
}
</style>
