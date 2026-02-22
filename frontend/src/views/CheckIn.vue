<template>
  <div class="checkin-page">
    <div class="checkin-card" v-if="!success">
      <h1>Online Check-in</h1>
      <p class="subtitle">
        Vyplňte údaje cestujúceho pre vystavenie palubného lístka
      </p>

      <div v-if="loading" class="loading">
        Načítavam rezerváciu…
      </div>

      <div v-else-if="error" class="error">
        {{ error }}
      </div>

      <form v-else @submit.prevent="submitCheckin">
        <div class="form-group">
          <label>Typ dokladu</label>
          <select v-model="form.document_type" required>
            <option value="">Vyberte</option>
            <option value="id_card">Občiansky preukaz</option>
            <option value="passport">Cestovný pas</option>
          </select>
        </div>

        <div class="form-group">
          <label>Číslo dokladu</label>
          <input
            v-model="form.document_number"
            type="text"
            required
            placeholder="Zadajte číslo dokladu"
          />
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Krajina vydania</label>
            <input
              v-model="form.document_country"
              type="text"
              placeholder="SK"
            />
          </div>

          <div class="form-group">
            <DatePicker
              v-model="form.document_expiry"
              label="Platnosť dokladu"
              placeholder="Vyber dátum"
              :disablePast="true"
            />
          </div>
        </div>

        <button type="submit" :disabled="submitting">
          {{ submitting ? "Spracúvam…" : "Dokončiť check-in" }}
        </button>
      </form>
    </div>

    <div class="checkin-success" v-else>
      <div class="icon">✅</div>
      <h2>Check-in úspešný!</h2>
      <p>
        Váš palubný lístok je pripravený na stiahnutie.
      </p>

      <div class="ticket-preview" v-if="pdfUrl">
        <img v-if="qrUrl" :src="qrUrl" alt="QR kód" class="qr-preview" />
        <p v-else class="no-qr">
          QR kód nie je dostupný, ale PDF palubného lístka je pripravené.
        </p>
      </div>

      <div class="action-buttons">
        <a v-if="pdfUrl" :href="pdfUrl" download class="download-btn">
          📄 Stiahnuť palubný lístok (PDF)
        </a>

        <button @click="goReservations" class="secondary-btn">
          Späť na rezervácie
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import DatePicker from "@/components/DatePicker.vue"; // uprav cestu podľa seba

const route = useRoute();
const router = useRouter();

const user = JSON.parse(localStorage.getItem("user") || "{}");

const loading = ref(true);
const submitting = ref(false);
const success = ref(false);
const error = ref("");

const pdfUrl = ref("");
const qrUrl = ref("");

const reservationId = route.params.orderId;

const form = ref({
  document_type: "",
  document_number: "",
  document_country: "SK",
  document_expiry: ""
});

onMounted(() => {
  if (!user.id) {
    router.push("/login");
    return;
  }

  if (!reservationId) {
    error.value = "Neplatná rezervácia";
    loading.value = false;
    return;
  }

  loading.value = false;
});

async function submitCheckin() {
  if (submitting.value) return;

  submitting.value = true;
  error.value = "";

  try {
    const res = await fetch("/api/check_in.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        reservation_id: Number(reservationId),
        user_id: user.id,
        ...form.value
      })
    });

    const data = await res.json();
    console.log("CHECK-IN RESPONSE:", data);
    console.log("PDF URL:", data.pdf_url);
    console.log("QR URL:", data.qr_url);

    if (!data.success) {
      console.log("CHECK-IN ERROR DATA:", data);
      error.value = data.message || "Check-in zlyhal";
      submitting.value = false;
      return;
    }

    pdfUrl.value = data.pdf_url || "";
    qrUrl.value = data.qr_url || "";
    success.value = true;
  } catch (e) {
    error.value = "Chyba pripojenia k serveru";
  }

  submitting.value = false;
}

function goReservations() {
  router.push("/reservations");
}
</script>


<style scoped>
.ticket-preview {
  margin: 24px 0;
  padding: 20px;
  background: linear-gradient(135deg, rgba(30, 144, 255, 0.1), rgba(0, 201, 167, 0.1));
  border-radius: 16px;
  border: 2px dashed rgba(30, 144, 255, 0.3);
}

.qr-preview {
  width: 200px;
  height: 200px;
  margin: 0 auto;
  display: block;
  border-radius: 12px;
  background: white;
  padding: 12px;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-top: 20px;
}

.download-btn {
  display: block;
  width: 100%;
  padding: 16px 24px;
  background: linear-gradient(135deg, #1E90FF, #00C9A7);
  color: white;
  text-decoration: none;
  border-radius: 50px;
  font-weight: 700;
  text-align: center;
  box-shadow: 0 12px 30px rgba(30, 144, 255, 0.4);
  transition: all 0.2s ease;
}

.download-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 20px 40px rgba(30, 144, 255, 0.5);
}

.secondary-btn {
  background: transparent;
  border: 2px solid var(--primary-blue);
  color: var(--primary-blue);
  padding: 14px 24px;
  border-radius: 50px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.secondary-btn:hover {
  background: var(--primary-blue);
  color: white;
}

:root {
  --primary-blue: #1E90FF;
  --dark-blue: #0A2540;
  --accent-orange: #FF6B35;
  --turquoise: #00C9A7;
  --light-gray: #F2F2F2;
  --white: #FFFFFF;
  --text-muted: #64748B;
}

.checkin-page {
  min-height: calc(100vh - 80px);
  padding: 80px 16px 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--primary-blue) 0%, var(--light-gray) 100%);
  font-family: -apple-system, BlinkMacSystemFont, sans-serif;
}

.checkin-card,
.checkin-success {
  width: 100%;
  max-width: 520px;
  background: var(--white);
  border-radius: 20px;
  padding: 32px 28px;
  box-shadow:
    0 25px 50px rgba(30, 144, 255, 0.25),
    0 0 0 1px rgba(30, 144, 255, 0.1);
  position: relative;
  overflow: hidden;
  animation: cardSlideIn 0.5s ease-out;
}

.checkin-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--primary-blue), var(--accent-orange), var(--turquoise));
}

h1 {
  font-size: 28px;
  font-weight: 700;
  color: var(--dark-blue) !important;
  margin: 0 0 8px 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.subtitle {
  color: var(--text-muted);
  font-size: 15px;
  margin-bottom: 24px;
  line-height: 1.5;
}

.loading {
  padding: 16px;
  border-radius: 16px;
  background: rgba(30, 144, 255, 0.1);
  border: 1px solid rgba(30, 144, 255, 0.2);
  color: var(--dark-blue);
  font-size: 15px;
  display: flex;
  align-items: center;
  gap: 12px;
  animation: pulse 1.5s ease-in-out infinite;
}

.loading::before {
  content: "";
  width: 20px;
  height: 20px;
  border: 2px solid var(--primary-blue);
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.error {
  background: #FEF2F2;
  border: 1px solid #FECACA;
  color: #DC2626;
  padding: 14px 16px;
  border-radius: 12px;
  font-size: 14px;
  margin-bottom: 20px;
}

form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

label {
  font-size: 14px;
  font-weight: 600;
  color: var(--dark-blue);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

select,
input[type="text"],
.dp-wrapper .dp-input {
  border-radius: 14px;
  border: 2px solid var(--light-gray);
  padding: 12px 16px;
  font-size: 16px;
  background: var(--white);
  transition: all 0.2s ease;
  font-family: inherit;
  width: 100%;
  box-sizing: border-box;
}

select:focus,
input[type="text"]:focus,
.dp-wrapper .dp-input:focus {
  border-color: var(--primary-blue);
  box-shadow: 0 0 0 3px rgba(30, 144, 255, 0.1);
  transform: translateY(-1px);
}

button[type="submit"],
.checkin-success button {
  width: 100% !important;
  border: none !important;
  border-radius: 50px !important;
  padding: 16px 24px !important;
  font-size: 16px !important;
  font-weight: 700 !important;
  color: #FFFFFF !important;
  background: linear-gradient(135deg, #1E90FF, #FF6B35) !important;
  box-shadow:
    0 12px 30px rgba(30, 144, 255, 0.4),
    0 4px 12px rgba(255, 107, 53, 0.3) !important;
  cursor: pointer !important;
  transition: all 0.2s ease !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
}

button[type="submit"]:hover:not(:disabled),
.checkin-success button:hover {
  transform: translateY(-2px) !important;
  box-shadow:
    0 20px 40px rgba(30, 144, 255, 0.5),
    0 8px 20px rgba(255, 107, 53, 0.4) !important;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.checkin-success {
  text-align: center;
}

.checkin-success .icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 40px;
  background: linear-gradient(135deg, var(--turquoise), #22C55E);
  color: white;
  border-radius: 50%;
  box-shadow:
    0 20px 40px rgba(0, 201, 167, 0.4),
    0 0 0 8px rgba(255, 255, 255, 0.3);
  animation: bounceIn 0.6s ease-out;
}

.checkin-success h2 {
  font-size: 28px;
  font-weight: 800;
  color: var(--dark-blue) !important;
  margin: 0 0 12px 0;
}

.checkin-success p {
  color: var(--text-muted);
  font-size: 16px;
  margin-bottom: 28px;
  line-height: 1.6;
}


body.dark .checkin-card,
body.dark .checkin-success {
  background: #1e293b;
  box-shadow: 0 30px 70px rgba(0,0,0,0.6);
}

body.dark h1,
body.dark .checkin-success h2 {
  color: #e2e8f0 !important;
}

body.dark .subtitle,
body.dark .checkin-success p {
  color: #94a3b8;
}

body.dark label {
  color: #cbd5e1;
}

body.dark select,
body.dark input[type="text"],
body.dark .dp-wrapper .dp-input {
  background: #0f172a;
  border-color: #334155;
  color: #f1f5f9;
}

body.dark select:focus,
body.dark input[type="text"]:focus,
body.dark .dp-wrapper .dp-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.3);
}

body.dark .loading {
  background: rgba(59,130,246,0.1);
  border-color: rgba(59,130,246,0.3);
  color: #e2e8f0;
}

body.dark .error {
  background: rgba(239,68,68,0.15);
  border-color: rgba(239,68,68,0.3);
  color: #f87171;
}

body.dark .ticket-preview {
  background: rgba(30,41,59,0.8);
  border-color: #3b82f6;
}

body.dark .qr-preview {
  background: #0f172a;
}

body.dark .secondary-btn {
  border-color: #3b82f6;
  color: #60a5fa;
}

body.dark .secondary-btn:hover {
  background: #3b82f6;
  color: white;
}

@keyframes cardSlideIn {
  from { opacity: 0; transform: translateY(30px) scale(0.95); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes bounceIn {
  0% { transform: scale(0.3); opacity: 0; }
  50% { transform: scale(1.05); }
  70% { transform: scale(0.9); }
  100% { opacity: 1; transform: scale(1); }
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@media (max-width: 640px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  h1 {
    font-size: 24px;
  }
}
</style>
