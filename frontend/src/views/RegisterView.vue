<template>
  <div class="register-container">
    <div class="register-card">
      <div class="logo-wrapper">
  <img src="@/assets/logo-dark.png" class="logo logo-dark" />
  <img src="@/assets/logo-light.png" class="logo logo-light" />
</div>
        <p class="subtitle">Vytvorte si účet a začnite objavovať lety</p>

      <div v-if="step === 'form'" class="form-section">
        <form @submit.prevent="register" class="register-form">
          <div class="input-group">
            <label class="input-label">Meno a priezvisko</label>
            <input 
              v-model="fullname" 
              placeholder="Ján Novák" 
              class="input-field"
              :class="{ error: errors.fullname }"
            />
          </div>

          <div class="input-group">
            <label class="input-label">Email</label>
            <input 
              v-model="email" 
              placeholder="jan.novak@email.com" 
              type="email" 
              class="input-field"
              :class="{ error: errors.email }"
            />
          </div>

          <div class="input-group">
            <label class="input-label">Heslo</label>
            <input 
              v-model="password" 
              placeholder="Minimálne 8 znakov" 
              type="password" 
              class="input-field"
              :class="{ error: errors.password }"
            />
          </div>

          <button type="submit" class="register-btn" :disabled="loading">
            <span v-if="loading">Vytváram účet...</span>
            <span v-else>Vytvoriť účet</span>
          </button>
        </form>

        <div v-if="message" class="message" :class="{ 'error': !success, 'success': success }">
          {{ message }}
        </div>

        <div class="login-link">
          <p>Už máte účet? 
            <router-link to="/login" class="login-text">Prihlásiť sa</router-link>
          </p>
        </div>
      </div>

      <div v-else class="verify-section">
        <div class="checkmark-circle">
          <span class="checkmark">✓</span>
        </div>
        
        <h2 class="verify-title">Overenie účtu</h2>
        <p class="verify-subtitle">
          Zadajte 6-miestny kód, ktorý vám prišiel na email
        </p>

        <form @submit.prevent="verify" class="verify-form">
          <div class="code-input-group">
            <input
              v-model="code"
              maxlength="6"
              placeholder="123456"
              class="code-input"
              type="text"
              inputmode="numeric"
            />
          </div>

          <button type="submit" class="verify-btn" :disabled="loading">
            <span v-if="loading">Overujem...</span>
            <span v-else>Overiť účet</span>
          </button>

          <button @click="resend" class="resend-btn" :disabled="loading">
            📩 Znova poslať kód
          </button>
        </form>

        <div v-if="message" class="message" :class="{ 'error': !success, 'success': success }">
          {{ message }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from "vue"
import { useRouter } from "vue-router"

const router = useRouter()

const fullname = ref("")
const email = ref("")
const password = ref("")
const code = ref("")
const message = ref("")
const step = ref("form")
const loading = ref(false)
const success = ref(false)
const errors = reactive({ fullname: false, email: false, password: false })

async function register() {
  try {
    loading.value = true
    errors.fullname = false
    errors.email = false
    errors.password = false
    message.value = ""
    success.value = false

    const res = await fetch("/api/register.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        fullname: fullname.value.trim(),
        email: email.value.trim(),
        password: password.value
      })
    })

    const data = await res.json()
    message.value = data.message
    success.value = data.success || false

    if (data.success && data.step === "verify") {
      step.value = "verify"
    }
  } catch (error) {
    console.error("Register error:", error)
    message.value = "Chyba pri registrácii. Skúste to znovu."
    success.value = false
  } finally {
    loading.value = false
  }
}

async function verify() {
  try {
    loading.value = true
    message.value = ""
    
    const res = await fetch("/api/verify_email.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email: email.value, code: code.value })
    })

    const data = await res.json()
    message.value = data.message
    success.value = data.success || false

    if (data.success) {
      setTimeout(() => {
        router.push("/login")
      }, 1500)
    }
  } catch (error) {
    console.error("Verify error:", error)
    message.value = "Chyba pri overení."
    success.value = false
  } finally {
    loading.value = false
  }
}

async function resend() {
  try {
    loading.value = true
    const res = await fetch("/api/resend_verification.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email: email.value })
    })

    const data = await res.json()
    message.value = data.message
    success.value = data.success || false
  } catch (error) {
    message.value = "Chyba pri odoslaní kódu."
    success.value = false
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.register-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  font-family: "Space Grotesk", system-ui, sans-serif;
}

.register-card {
  background: white;
  border-radius: 24px;
  padding: 2.5rem;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
  border: 1px solid #E5E7EB;
}

.logo-section {
  text-align: center;
  margin-bottom: 2rem;
}

.logo {
  width: 140px;
  height: 60px;
  object-fit: contain;
  margin-bottom: 1rem;
  filter: drop-shadow(0 4px 12px rgba(0,0,0,0.1));
}

.subtitle {
  color: #64748B;
  font-size: 1.1rem;
  margin: 0;
}

.input-group {
  margin-bottom: 1.5rem;
}

.input-label {
  display: block;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
}

.input-field {
  width: 100%;
  padding: 16px 20px;
  border-radius: 16px;
  border: 2px solid #E2E8F0;
  font-size: 1rem;
  background: #FAFCFF;
  transition: all 0.2s ease;
  font-family: inherit;
  box-sizing: border-box;
}

.input-field:focus {
  outline: none;
  border-color: #1E90FF;
  box-shadow: 0 0 0 4px rgba(30, 144, 255, 0.15);
  background: white;
}

.input-field.error {
  border-color: #EF4444 !important;
  background: #FFF1F2 !important;
  box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2) !important;
}

.register-form, .verify-form {
  margin-bottom: 1.5rem;
}

.register-btn, .verify-btn {
  width: 100%;
  padding: 18px;
  background: linear-gradient(135deg, #00C9A7, #10B981);
  color: white;
  border: none;
  border-radius: 20px;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 10px 30px rgba(0, 201, 167, 0.3);
  margin-bottom: 1rem;
}

.register-btn:hover:not(:disabled), .verify-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 15px 40px rgba(0, 201, 167, 0.4);
}

.register-btn:disabled, .verify-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.resend-btn {
  width: 100%;
  padding: 14px;
  background: #F8FAFC;
  color: #1E90FF;
  border: 2px solid #E2E8F0;
  border-radius: 16px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.resend-btn:hover:not(:disabled) {
  background: #E2E8F0;
  border-color: #1E90FF;
  transform: translateY(-1px);
}

.resend-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.message {
  padding: 1rem 1.25rem;
  border-radius: 12px;
  text-align: center;
  font-weight: 600;
  font-size: 0.95rem;
  margin-top: 1rem;
}

.message.success {
  background: #F0FDF4;
  color: #00C9A7;
  border: 1px solid #BBF7D0;
}

.message.error {
  background: #FEF2F2;
  color: #EF4444;
  border: 1px solid #FECACA;
}

.login-link {
  text-align: center;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #E2E8F0;
}

.login-link p {
  color: #64748B;
  margin: 0;
}

.login-text {
  color: #1E90FF;
  text-decoration: none;
  font-weight: 600;
}

.login-text:hover {
  text-decoration: underline;
  color: #0A2540;
}

.verify-section {
  text-align: center;
  padding-top: 1rem;
}

.checkmark-circle {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #00C9A7, #10B981);
  border-radius: 50%;
  margin: 0 auto 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 10px 30px rgba(0, 201, 167, 0.3);
}

.checkmark {
  font-size: 2.5rem;
  color: white;
  font-weight: bold;
}

.verify-title {
  font-size: 1.8rem;
  font-weight: 800;
  color: #0A2540;
  margin-bottom: 1rem;
}

.verify-subtitle {
  color: #64748B;
  font-size: 1.1rem;
  margin-bottom: 2.5rem;
  line-height: 1.6;
}

.code-input-group {
  margin-bottom: 2rem;
}

.code-input {
  width: 200px;
  margin: 0 auto;
  padding: 20px;
  font-size: 1.8rem;
  text-align: center;
  border-radius: 16px;
  border: 3px solid #E2E8F0;
  font-weight: 600;
  letter-spacing: 0.5rem;
  background: #FAFCFF;
  transition: all 0.2s ease;
}

.code-input:focus {
  outline: none;
  border-color: #1E90FF;
  box-shadow: 0 0 0 4px rgba(30, 144, 255, 0.15);
  background: white;
}

body.dark .register-card {
  background: #1e293b;
  border-color: #334155;
  box-shadow: 0 25px 60px rgba(0,0,0,0.6);
}

body.dark .subtitle {
  color: #94a3b8;
}

body.dark .input-label {
  color: #cbd5e1;
}

body.dark .input-field {
  background: #0f172a;
  border-color: #334155;
  color: #f1f5f9;
}

body.dark .input-field:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59,130,246,0.3);
  background: #0f172a;
}

body.dark .register-btn,
body.dark .verify-btn {
  background: linear-gradient(135deg, #3b82f6, #0f172a);
  box-shadow: 0 15px 40px rgba(59,130,246,0.35);
}

body.dark .resend-btn {
  background: #0f172a;
  border-color: #334155;
  color: #60a5fa;
}

body.dark .resend-btn:hover:not(:disabled) {
  background: #1e293b;
}

body.dark .message.success {
  background: rgba(34,197,94,0.15);
  border-color: rgba(34,197,94,0.3);
  color: #4ade80;
}

body.dark .message.error {
  background: rgba(239,68,68,0.15);
  border-color: rgba(239,68,68,0.3);
  color: #f87171;
}

body.dark .login-link {
  border-top: 1px solid #334155;
}

body.dark .login-link p {
  color: #94a3b8;
}

body.dark .login-text {
  color: #60a5fa;
}

body.dark .login-text:hover {
  color: #93c5fd;
}

body.dark .verify-title {
  color: #e2e8f0;
}

body.dark .verify-subtitle {
  color: #94a3b8;
}

body.dark .code-input {
  background: #0f172a;
  border-color: #334155;
  color: #f1f5f9;
}

body.dark .code-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59,130,246,0.3);
}

.logo-wrapper {
  position: relative;
  width: 140px;
  height: 60px;
  margin: 0 auto 1rem;
}

.logo {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: contain;
  transition: opacity 0.25s ease;
}

.logo-light {
  opacity: 0;
}

.logo-dark {
  opacity: 1;
}

body.dark .logo-dark {
  opacity: 0;
}

body.dark .logo-light {
  opacity: 1;
}

@media (max-width: 480px) {
  .register-container {
    padding: 10px;
  }
  
  .register-card {
    padding: 2rem 1.5rem;
    margin: 1rem;
  }
  
  .logo {
    width: 120px;
    height: 52px;
  }
  
  .code-input {
    width: 180px;
    font-size: 1.6rem;
    letter-spacing: 0.3rem;
  }
}
</style>
