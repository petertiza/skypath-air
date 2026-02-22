<template>
  <div class="success-page">
    <div class="success-card">
      <div class="icon-wrapper">
        <div class="checkmark">✓</div>
      </div>

      <h1>Rezervácia úspešná</h1>
      <p class="subtitle">
        Ďakujeme za Vašu objednávku u <b>SkyPath AIR</b>
      </p>

      <div class="order-box">
        <div class="row">
          <span>Číslo rezervácie</span>
          <b>#{{ orderId }}</b>
        </div>

        <div class="row">
          <span>Stav</span>
          <span class="status">Zaplatené</span>
        </div>

        <div class="row">
          <span>Potvrdenie</span>
          <span>Email bol odoslaný ✉️</span>
        </div>
      </div>

      <div class="info-box">
        <p>
          ✈️ Informácie o lete a cestujúcich nájdete v sekcii
          <b>Moje rezervácie</b>.
        </p>
        <p>
          🧾 Palubná vstupenka bude dostupná po online check-ine.
        </p>
      </div>

      <div class="actions">
        <button class="primary" @click="goReservations">
          Moje rezervácie
        </button>

        <button class="secondary" @click="goHome">
          Späť na úvod
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from "vue"
import { useRoute, useRouter } from "vue-router"

const route = useRoute()
const router = useRouter()

const orderId = route.params.id

onMounted(() => {
  if (!orderId) {
    router.replace("/")
  }
})

function goReservations() {
  router.push("/reservations")
}

function goHome() {
  router.push("/")
}
</script>

<style scoped>
.success-page {
  min-height: 100vh;
  background: transparent; 
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px 24px;
  font-family: "Space Grotesk", -apple-system, BlinkMacSystemFont, sans-serif;
}

.success-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(30px);
  border-radius: 32px;
  padding: 60px 48px;
  max-width: 580px;
  width: 100%;
  text-align: center;
  box-shadow: 0 40px 100px rgba(0, 0, 0, 0.18);
  border: 1px solid rgba(255, 255, 255, 0.25);
  animation: slideUpFade 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  position: relative;
  overflow: hidden;
}

.success-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #10b981, #22c55e, #4ade80);
}

.icon-wrapper {
  width: 110px;
  height: 110px;
  margin: 0 auto 32px;
  background: linear-gradient(145deg, #22c55e, #16a34a);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 20px 50px rgba(34, 197, 94, 0.4);
  position: relative;
  animation: bounceIn 0.8s ease;
}

.icon-wrapper::before {
  content: '';
  position: absolute;
  inset: 4px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
}

.checkmark {
  font-size: 56px;
  color: white;
  font-weight: 900;
  text-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  z-index: 2;
}

h1 {
  font-size: 36px;
  font-weight: 800;
  background: linear-gradient(135deg, #0a2540, #1e40af);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin: 0 0 12px 0;
}

.subtitle {
  color: #64748b;
  font-size: 18px;
  margin-bottom: 36px;
  font-weight: 500;
  line-height: 1.6;
}

.order-box {
  background: rgba(248, 250, 252, 0.8);
  backdrop-filter: blur(15px);
  border-radius: 24px;
  padding: 28px 32px;
  margin-bottom: 32px;
  text-align: left;
  border: 1px solid rgba(226, 232, 240, 0.5);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}

.order-box .row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  font-size: 16px;
  padding: 12px 0;
}

.order-box .row:last-child {
  margin-bottom: 0;
}

.order-box span {
  color: #374151;
  font-weight: 500;
}

.order-box b {
  font-size: 18px;
  background: linear-gradient(135deg, #10b981, #22c55e);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-weight: 800;
}

.status {
  color: #10b981;
  font-weight: 700;
  font-size: 15px;
  padding: 6px 16px;
  background: rgba(16, 185, 129, 0.15);
  border-radius: 20px;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.info-box {
  font-size: 15px;
  color: #64748b;
  margin-bottom: 40px;
  line-height: 1.7;
  padding: 24px;
  background: rgba(248, 250, 252, 0.5);
  border-radius: 20px;
  border: 1px solid rgba(226, 232, 240, 0.4);
}

.info-box p {
  margin: 0 0 16px 0;
}

.info-box b {
  color: #0a2540;
}

.actions {
  display: flex;
  gap: 16px;
  flex-direction: column;
}

button {
  padding: 18px 28px;
  border-radius: 20px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  border: none;
  transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  position: relative;
  overflow: hidden;
}

.primary {
  background: linear-gradient(145deg, #3b82f6, #1d4ed8);
  color: white;
  box-shadow: 0 15px 40px rgba(59, 130, 246, 0.4);
}

.primary::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
  transition: left 0.6s;
}

.primary:hover::before {
  left: 100%;
}

.primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 25px 60px rgba(59, 130, 246, 0.5);
}

.secondary {
  background: rgba(248, 250, 252, 0.8);
  color: #374151;
  border: 2px solid rgba(226, 232, 240, 0.8);
  backdrop-filter: blur(15px);
}

.secondary:hover {
  background: rgba(255, 255, 255, 0.95);
  transform: translateY(-2px);
  box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
}

@keyframes slideUpFade {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes bounceIn {
  0% {
    transform: scale(0.3);
    opacity: 0;
  }
  50% {
    transform: scale(1.05);
  }
  70% {
    transform: scale(0.9);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}

@media (max-width: 640px) {
  .success-page {
    padding: 40px 20px;
  }
  
  .success-card {
    padding: 48px 32px;
    margin: 0 8px;
  }
  
  .actions {
    flex-direction: column;
  }
  
  h1 {
    font-size: 28px;
  }
}

@media (max-width: 480px) {
  .order-box .row {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
}
</style>
