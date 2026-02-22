<template>
  <div class="contact-page">
    <div class="contact-card">
      <h1>Kontaktujte nás</h1>
      <p class="subtitle">
        Máte otázku k letu, rezervácii alebo platbe? Napíšte nám.
      </p>

      <form @submit.prevent="send">
        <div class="field">
          <label>Meno</label>
          <input v-model="name" required />
        </div>

        <div class="field">
          <label>Email</label>
          <input type="email" v-model="email" required />
        </div>

        <div class="field">
          <label>Správa</label>
          <textarea rows="5" v-model="message" required></textarea>
        </div>

        <button :disabled="loading">
          {{ loading ? "Odosielam..." : "Odoslať správu" }}
        </button>

        <p v-if="success" class="success">
          ✔️ Správa bola úspešne odoslaná
        </p>

        <p v-if="error" class="error">
          ❌ {{ error }}
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";

const name = ref("");
const email = ref("");
const message = ref("");
const loading = ref(false);
const success = ref(false);
const error = ref("");

const SITE_KEY = "6LdbCVcsAAAAANvCY2szzg8nR5STQosBYIUPCqMc"; 

const send = async () => {
  loading.value = true;
  error.value = "";
  success.value = false;

  try {
    if (!window.grecaptcha) {
      throw new Error("Captcha sa nepodarilo načítať.");
    }

    const token = await window.grecaptcha.execute(SITE_KEY, {
      action: "contact"
    });

    const res = await fetch("/api/send_contact.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        name: name.value,
        email: email.value,
        message: message.value,
        recaptcha: token
      })
    });

    const data = await res.json();

    if (!data.success) {
      throw new Error(data.message || "Odoslanie zlyhalo.");
    }

    success.value = true;
    name.value = "";
    email.value = "";
    message.value = "";
  } catch (e) {
    error.value = e.message || "Nepodarilo sa odoslať správu.";
  }

  loading.value = false;
};
</script>

<style scoped>
.contact-page {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 60px 20px;
}

.contact-card {
  width: 520px;
  background: var(--bg-alt);
  padding: 40px;
  border-radius: 22px;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--border-soft);
}

h1 {
  color: var(--text-main);
  margin-bottom: 10px;
  font-size: 2rem;
  font-weight: 800;
}

.subtitle {
  color: var(--text-muted);
  margin-bottom: 30px;
  font-size: 1.1rem;
}

.field {
  display: flex;
  flex-direction: column;
  margin-bottom: 18px;
}

label {
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--text-muted);
  margin-bottom: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

input,
textarea {
  padding: 14px 16px;
  border-radius: 12px;
  border: 1.5px solid var(--border-soft);
  font-size: 0.95rem;
  font-family: inherit;
  transition: all 0.2s ease;
  background: var(--bg);
  color: var(--text-main);
}

input {
  height: 52px;
}

textarea {
  min-height: 120px;
  max-height: 120px;
  resize: none;
  overflow-y: auto;
}

input:focus,
textarea:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
}

button {
  width: 100%;
  padding: 16px;
  margin-top: 10px;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  border: none;
  border-radius: 14px;
  color: white;
  font-size: 1.05rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 10px 25px rgba(30,144,255,0.3);
}

button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 15px 35px rgba(30,144,255,0.4);
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.success {
  margin-top: 16px;
  color: var(--turquoise);
  text-align: center;
  font-weight: 600;
  background: rgba(0,201,167,0.1);
  padding: 12px;
  border-radius: 10px;
  border: 1px solid rgba(0,201,167,0.3);
}

.error {
  margin-top: 16px;
  color: #EF4444;
  text-align: center;
  font-weight: 600;
  background: rgba(239,68,68,0.1);
  padding: 12px;
  border-radius: 10px;
  border: 1px solid rgba(239,68,68,0.3);
}

@media (max-width: 640px) {
  .contact-page {
    padding: 40px 16px;
  }

  .contact-card {
    width: 100%;
    max-width: 400px;
    padding: 32px 24px;
  }
}
</style>
