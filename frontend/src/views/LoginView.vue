<template>
  <div class="login-container">
    <div class="login-card">
      <div class="logo-wrapper">
  <img src="@/assets/logo-dark.png" class="logo logo-dark" />
  <img src="@/assets/logo-light.png" class="logo logo-light" />
</div>
        <p class="subtitle">Vitajte späť! Prihláste sa do svojho účtu</p>

      <button @click="googleLogin" class="google-btn" :disabled="loading">
        <span class="google-icon">G</span>
        Prihlásiť cez Google
      </button>

      <div class="divider">
        <span>alebo</span>
      </div>

      <form @submit.prevent="loginUser" class="login-form">
        <div class="input-group">
          <input v-model="email" placeholder="✉️ Email" type="email" class="input-field" :class="{ error: errors.email }" />
        </div>
        <div class="input-group">
          <input v-model="password" placeholder="🔒 Heslo" type="password" class="input-field" :class="{ error: errors.password }" />
        </div>
        <button type="submit" class="login-btn" :disabled="loading">
          <span v-if="loading">Načítavam...</span>
          <span v-else>Prihlásiť sa</span>
        </button>
      </form>

      <div v-if="message" class="message" :class="{ 'error': !success, 'success': success }">
        {{ message }}
      </div>

      <div class="register-section">
        <p>Nemáte účet?</p>
        <router-link to="/register" class="register-link">
          <span class="highlight">Zaregistrujte sa</span>
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from "vue"
import { useRouter } from "vue-router"

const router = useRouter()

const email = ref("")
const password = ref("")
const message = ref("")
const loading = ref(false)
const success = ref(false)
const errors = reactive({ email: false, password: false })

const GOOGLE_CLIENT_ID = "428426628317-ine634s7qe4pt7mvq9qk0dttot7uunir.apps.googleusercontent.com"

async function loginUser() {
  try {
    loading.value = true
    message.value = ""
    
    const res = await fetch("/api/login.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email: email.value.trim(), password: password.value })
    })

    const data = await res.json()
    message.value = data.message
    success.value = data.success || false

    if (data.success) {
      localStorage.setItem("user", JSON.stringify(data.user))

      window.dispatchEvent(new Event("user-changed"))

      setTimeout(() => router.push("/"), 800)
    }
  } catch (error) {
    message.value = "Chyba pri prihlasovaní."
    success.value = false
  } finally {
    loading.value = false
  }
}

async function googleLogin() {
  try {
    loading.value = true
    message.value = "Presmerovávam na Google..."
    
    const authUrl = `https://accounts.google.com/o/oauth2/v2/auth?` +
      `client_id=${GOOGLE_CLIENT_ID}&` +
      `redirect_uri=http://localhost:5173/auth/google/callback&` +
      `response_type=code&` +
      `scope=openid email profile&` +
      `access_type=offline&` +
      `origin=http://localhost:5173`
    
    window.location.href = authUrl
  } catch (error) {
    loading.value = false
    message.value = "Chyba Google loginu"
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  font-family: "Space Grotesk", system-ui, sans-serif;
}

.login-card {
  background: white;
  border-radius: 24px;
  padding: 2.5rem;
  max-width: 420px;
  width: 100%;
  box-shadow: 0 10px 40px rgba(0,0,0,0.1);
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

.google-btn {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #4285F4, #34A853);
  color: white;
  border: none;
  border-radius: 16px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 1.5rem;
  transition: all 0.3s ease;
  box-shadow: 0 8px 25px rgba(66,133,244,0.3);
}

.google-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 12px 35px rgba(66,133,244,0.4);
}

.google-btn:disabled { 
  opacity: 0.7; 
  cursor: not-allowed; 
}

.google-icon {
  font-size: 1.4rem;
  font-weight: bold;
  width: 20px;
  height: 20px;
  border-radius: 3px;
  background: white;
  color: #4285F4;
  display: flex;
  align-items: center;
  justify-content: center;
}

.divider {
  text-align: center;
  margin: 1.5rem 0;
  position: relative;
  color: #94A3B8;
}

.divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: #E2E8F0;
}

.divider span {
  background: white;
  padding: 0 1rem;
}

.input-group { 
  margin-bottom: 1.25rem; 
}

.input-field {
  width: 100%;
  padding: 16px 20px;
  border-radius: 16px;
  border: 2px solid #E2E8F0;
  font-size: 1rem;
  background: #FAFCFF;
  transition: all 0.2s ease;
}

.input-field:focus {
  outline: none;
  border-color: #1E90FF;
  box-shadow: 0 0 0 4px rgba(30,144,255,0.15);
}

.login-btn {
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
  box-shadow: 0 10px 30px rgba(0,201,167,0.3);
}

.login-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 15px 40px rgba(0,201,167,0.4);
}

.login-btn:disabled { 
  opacity: 0.7; 
  cursor: not-allowed; 
}

.message {
  margin-top: 1.5rem;
  padding: 1rem;
  border-radius: 12px;
  text-align: center;
  font-weight: 600;
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

.register-section {
  text-align: center;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #E2E8F0;
}

.register-section p { 
  color: #64748B; 
  margin-bottom: 0.75rem; 
  font-size: 0.95rem; 
}

.register-link {
  color: #1E90FF;
  text-decoration: none;
  font-weight: 600;
}

.register-link:hover { 
  text-decoration: underline; 
  color: #0A2540; 
}

.highlight { 
  font-weight: 700; 
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


body.dark .login-container {
  background: transparent;
}

body.dark .login-card {
  background: #1e293b;
  border-color: #334155;
  box-shadow: 0 25px 60px rgba(0,0,0,0.6);
}

body.dark .subtitle {
  color: #94a3b8;
}

body.dark .divider {
  color: #94a3b8;
}

body.dark .divider::before {
  background: #334155;
}

body.dark .divider span {
  background: #1e293b;
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
}

body.dark .login-btn {
  background: linear-gradient(135deg, #3b82f6, #0f172a);
  box-shadow: 0 15px 40px rgba(59,130,246,0.35);
}

body.dark .google-btn {
  background: linear-gradient(135deg, #3b82f6, #1e40af);
  box-shadow: 0 12px 35px rgba(59,130,246,0.4);
}

body.dark .google-icon {
  background: #0f172a;
  color: #60a5fa;
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

body.dark .register-section {
  border-top: 1px solid #334155;
}

body.dark .register-section p {
  color: #94a3b8;
}

body.dark .register-link {
  color: #60a5fa;
}

body.dark .register-link:hover {
  color: #93c5fd;
}

@media (max-width: 480px) {
  .login-card { 
    padding: 2rem 1.5rem; 
    margin: 1rem; 
  }
  .logo {
    width: 120px;
    height: 52px;
  }
}
</style>
