<template>
  <div class="flight-detail-page">
    <div class="back-section">
      <button @click="goBack" class="back-btn">
        ← Späť na vyhľadávanie
      </button>
    </div>

    <div class="flight-card">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Načítavam detail letu...</p>
      </div>

      <div v-else-if="flight" class="flight-content">
        <div class="flight-header">
          <div class="flight-number">
            <span class="flight-icon">✈️</span>
            <h1>{{ flight.flight_number }}</h1>
          </div>
          
          <div class="route-visual">
            <div class="airport departure">
              <div class="airport-code">{{ flight.departure_city.slice(0,3) }}</div>
              <div class="airport-name">{{ flight.departure_city }}</div>
            </div>
            
            <div class="flight-path">
              <div class="duration">
                {{ flight_duration }}h
              </div>
              <div class="arrow">➜</div>
            </div>
            
            <div class="airport arrival">
              <div class="airport-code">{{ flight.arrival_city.slice(0,3) }}</div>
              <div class="airport-name">{{ flight.arrival_city }}</div>
            </div>
          </div>
        </div>

        <div class="flight-times">
          <div class="time-item">
            <span class="time-label">Odlet</span>
            <span class="time">{{ formatTime(flight.departure_time) }}</span>
          </div>
          <div class="time-item">
            <span class="time-label">Prílet</span>
            <span class="time">{{ formatTime(flight.arrival_time) }}</span>
          </div>
        </div>

        <div class="price-section">
          <div class="price-info">
            <span class="old-price">{{ Math.round(flight.price * 1.2) }} €</span>
            <span class="new-price">{{ flight.price }} €</span>
            <span class="save">UŠETRITE {{ Math.round(20) }}%</span>
          </div>
          
          <button @click="addToCart" class="reserve-btn">
            <span>Rezervovať</span>
            <span class="btn-points">+{{ pointsReward }} bodov</span>
          </button>
        </div>

        <div class="details-grid">
          <div class="detail-item">
            <span class="detail-icon">🪑</span>
            <span>{{ flight.seats_available }} voľných miest</span>
          </div>
          <div class="detail-item">
            <span class="detail-icon">⚡</span>
            <span>Priamy let</span>
          </div>
          <div class="detail-item">
            <span class="detail-icon">📦</span>
            <span>1x batožina zdarma</span>
          </div>
        </div>

        <div class="features-section">
          <h3>Čo je zahrnuté</h3>
          <div class="features-grid">
            <span>✈️ Lietadlo: Boeing 737</span>
            <span>📶 WiFi na palube</span>
            <span>🍽️ Ľahké občerstvenie</span>
            <span>🛡️ Plná poistka</span>
          </div>
        </div>
      </div>

      <div v-else class="error-state" v-html="message"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import { useRoute, useRouter } from "vue-router"

const route = useRoute()
const router = useRouter()

const flight = ref(null)
const message = ref("")
const loading = ref(true)

const pointsReward = 25

onMounted(async () => {
  const id = route.params.id
  try {
    const res = await fetch(`/api/get_flight.php?id=${id}`)
    const data = await res.json()
    
    if (data.success) {
      flight.value = data.flight
    } else {
      message.value = data.message
    }
  } catch (err) {
    message.value = "Chyba pri načítaní letu: " + err.message
  }
  loading.value = false
})

const flight_duration = computed(() => {
  if (!flight.value) return ""
  const dep = new Date(flight.value.departure_time)
  const arr = new Date(flight.value.arrival_time)
  const diff = (arr - dep) / (1000 * 60 * 60)
  return diff.toFixed(1)
})

const formatTime = (datetime) => {
  return new Date(datetime).toLocaleString('sk-SK', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const addToCart = () => {
  const user = JSON.parse(localStorage.getItem("user"))
  if (!user) {
    router.push("/login")
    return
  }
  
  const flightData = { ...flight.value, quantity: 1 }
  let cart = JSON.parse(localStorage.getItem('flightCart') || '[]')
  cart.push(flightData)
  localStorage.setItem('flightCart', JSON.stringify(cart))
  
  router.push('/cart')
}

function goBack() {
  router.go(-1)
}
</script>

<style scoped>
.flight-detail-page {
  min-height: 100vh;
  padding: 1rem;
  font-family: -apple-system, BlinkMacSystemFont, 'Space Grotesk', Roboto, sans-serif;
}

.back-section {
  max-width: 1200px;
  margin: 1rem auto;
}

.back-btn {
  background: rgba(255, 255, 255, 0.8);
  border: 1px solid #e2e8f0;
  padding: 0.75rem 1.5rem;
  border-radius: 12px;
  color: #64748b;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.back-btn:hover {
  background: #ffffff;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.flight-card {
  max-width: 800px;
  margin: 0 auto;
  background: #FFFFFF;
  border-radius: 24px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.loading-state, .error-state {
  padding: 4rem 2rem;
  text-align: center;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #f3f4f6;
  border-top: 3px solid #1E90FF;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-state {
  color: #ef4444;
}

.flight-content {
  padding: 2.5rem;
}

.flight-header {
  margin-bottom: 2rem;
}

.flight-number {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.flight-icon {
  font-size: 2rem;
}

.flight-number h1 {
  font-size: 2rem;
  font-weight: 800;
  color: #0A2540;
  margin: 0;
}

.route-visual {
  display: flex;
  align-items: center;
  gap: 2rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
  border-radius: 16px;
  border: 1px solid #00C9A7;
}

.airport {
  text-align: center;
  flex: 1;
}

.airport-code {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1E90FF;
  background: rgba(30, 144, 255, 0.1);
  padding: 0.5rem 1rem;
  border-radius: 8px;
  margin-bottom: 0.25rem;
}

.airport-name {
  font-size: 0.9rem;
  color: #64748b;
  font-weight: 500;
}

.flight-path {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  min-width: 80px;
}

.duration {
  background: #00C9A7;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.85rem;
}

.arrow {
  font-size: 1.5rem;
  color: #94a3b8;
}

.flight-times {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin-bottom: 2.5rem;
  padding: 1.5rem;
  background: #f8fafc;
  border-radius: 16px;
  border-left: 4px solid #1E90FF;
}

.time-item {
  text-align: center;
}

.time-label {
  display: block;
  font-size: 0.85rem;
  color: #64748b;
  margin-bottom: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.time {
  font-size: 1.6rem;
  font-weight: 700;
  color: #0A2540;
}

.price-section {
  text-align: center;
  margin-bottom: 2.5rem;
  padding: 2rem 1.5rem;
  background: linear-gradient(135deg, #fef7ff, #fae8ff);
  border-radius: 24px;
  border: 2px solid #FF6B35;
}

.price-info {
  margin-bottom: 1.5rem;
}

.old-price {
  display: block;
  font-size: 1.1rem;
  color: #94a3b8;
  text-decoration: line-through;
  margin-bottom: 0.25rem;
}

.new-price {
  font-size: 3.5rem;
  font-weight: 800;
  color: #0A2540;
  margin-bottom: 0.25rem;
}

.save {
  background: #FF6B35;
  color: white;
  padding: 0.25rem 1rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
}

.reserve-btn {
  background: linear-gradient(135deg, #FF6B35, #00C9A7);
  color: white;
  border: none;
  padding: 1.25rem 3rem;
  border-radius: 16px;
  font-size: 1.25rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 15px 35px rgba(255, 107, 53, 0.4);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.reserve-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 20px 45px rgba(255, 107, 53, 0.5);
}

.btn-points {
  display: block;
  font-size: 0.9rem;
  opacity: 0.95;
  margin-top: 0.25rem;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.5);
  border-radius: 12px;
  border-left: 4px solid #1E90FF;
}

.detail-icon {
  font-size: 1.25rem;
}

.features-section {
  padding-top: 2rem;
  border-top: 1px solid #e2e8f0;
}

.features-section h3 {
  font-size: 1.3rem;
  color: #0A2540;
  margin-bottom: 1.5rem;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.features-grid span {
  padding: 1rem 1.25rem;
  background: #f0fdfa;
  border-radius: 12px;
  border-left: 4px solid #00C9A7;
  font-weight: 500;
  color: #065f46;
}

body.dark .flight-card {
  background: #1E293B !important;
  border: 1px solid #334155 !important;
}

body.dark .flight-number h1,
body.dark .time,
body.dark .new-price,
body.dark .features-section h3 {
  color: var(--text-main) !important;
}

body.dark .airport-name,
body.dark .time-label {
  color: var(--text-muted) !important;
}

body.dark .flight-times {
  background: #111827 !important;
  border-left: 4px solid var(--primary) !important;
}

body.dark .route-visual {
  background: linear-gradient(135deg, #1E293B, #0F172A) !important;
  border: 1px solid var(--turquoise) !important;
}

body.dark .price-section {
  background: linear-gradient(135deg, #1E293B, #0F172A) !important;
  border: 2px solid var(--accent) !important;
}

body.dark .detail-item {
  background: #0F172A !important;
  border-left: 4px solid var(--primary) !important;
}

body.dark .features-grid span {
  background: #0F172A !important;
  color: var(--text-main) !important;
  border-left: 4px solid var(--turquoise) !important;
}

@media (max-width: 768px) {
  .flight-detail-page {
    padding: 0.5rem;
  }
  
  .flight-card {
    margin: 0.5rem;
    border-radius: 20px;
  }
  
  .flight-content {
    padding: 1.5rem;
  }
  
  .new-price {
    font-size: 2.5rem;
  }
}
</style>
