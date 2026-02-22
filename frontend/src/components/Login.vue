<template>
  <div class="p-4 max-w-md mx-auto">
    <h1 class="text-2xl font-bold mb-4">Prihlásenie</h1>

    <form @submit.prevent="loginUser">
      <input v-model="email" placeholder="Email" type="email" class="border p-2 w-full mb-2" />
      <input v-model="password" placeholder="Heslo" type="password" class="border p-2 w-full mb-2" />
      <button class="bg-green-600 text-white px-4 py-2 rounded">Prihlásiť</button>
    </form>

    <p v-if="message" class="mt-3">{{ message }}</p>
  </div>
</template>

<script setup>
import { ref } from "vue";

const email = ref("");
const password = ref("");
const message = ref("");

async function loginUser() {
  try {
    const res = await fetch("/api/login.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email: email.value, password: password.value })
    });

    const data = await res.json();
    message.value = data.message;

    if (data.success) {
      localStorage.setItem("user", JSON.stringify(data.user));
    }
  } catch (error) {
    console.error(error);
    message.value = "Chyba pri prihlasovaní.";
  }
}
</script>
