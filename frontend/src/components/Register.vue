<template>
  <div class="p-4 max-w-md mx-auto">
    <h1 class="text-2xl font-bold mb-4">Registrácia</h1>

    <form @submit.prevent="registerUser">
      <input
        v-model="fullname"
        placeholder="Meno a priezvisko"
        class="border p-2 w-full mb-2"
      />
      <input
        v-model="email"
        placeholder="Email"
        type="email"
        class="border p-2 w-full mb-2"
      />
      <input
        v-model="password"
        placeholder="Heslo"
        type="password"
        class="border p-2 w-full mb-2"
      />
      <button class="bg-blue-500 text-white px-4 py-2 rounded">
        Registrovať
      </button>
    </form>

    <p v-if="message" class="mt-3">{{ message }}</p>
  </div>
</template>

<script setup>
import { ref } from "vue";

const fullname = ref("");
const email = ref("");
const password = ref("");
const message = ref("");

async function registerUser() {
  try {
    alert("Odosielam registráciu...");

    const res = await fetch("/api/register.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        fullname: fullname.value,
        email: email.value,
        password: password.value,
      }),
    });

    const data = await res.json();
    message.value = data.message || "Žiadna odpoveď od servera";
  } catch (error) {
    console.error(error);
    message.value = "Chyba pri odosielaní požiadavky.";
  }
}
</script>
