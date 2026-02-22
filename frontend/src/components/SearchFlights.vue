<template>
  <div class="p-8 bg-gray-50 min-h-screen">
    <h1 class="text-2xl font-bold mb-4 text-[#0A2540]">Vyhľadávanie letov</h1>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <input v-model="from" placeholder="Mesto odletu" class="border p-2 rounded" />
      <input v-model="to" placeholder="Mesto príletu" class="border p-2 rounded" />
      <input v-model="date" type="date" class="border p-2 rounded" />
      <button @click="search" class="bg-[#1E90FF] text-white p-2 rounded hover:bg-[#0A2540]">
        Vyhľadať
      </button>
    </div>

    <div v-if="flights.length">
      <div
        v-for="f in flights"
        :key="f.id"
        class="bg-white p-4 rounded shadow mb-4 flex justify-between items-center"
      >
        <div>
          <p class="font-semibold text-lg">
            ✈️ {{ f.departure_city }} → {{ f.arrival_city }}
          </p>
          <p>{{ new Date(f.departure_time).toLocaleString() }}</p>
          <p class="text-gray-500">{{ f.price }} €</p>
        </div>
        <button
          @click="reserve(f.id)"
          class="bg-[#FF6B35] text-white px-4 py-2 rounded hover:bg-[#00C9A7]"
        >
          Rezervovať
        </button>
      </div>
    </div>

    <p v-else class="text-gray-500">Žiadne lety podľa filtra.</p>
  </div>
</template>

<script setup>
import { ref } from "vue";

const from = ref("");
const to = ref("");
const date = ref("");
const flights = ref([]);

async function search() {
  const res = await fetch("/api/search_flights.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      from: from.value,
      to: to.value,
      date: date.value,
    }),
  });

  const data = await res.json();
  flights.value = data.flights || [];
}

function reserve(id) {
  const user = JSON.parse(localStorage.getItem("user"));
  if (!user) {
    alert("Pre rezerváciu sa musíte prihlásiť.");
    window.location.href = "/#/login";
    return;
  }
  window.location.href = `/#/flights?selected=${id}`;
}

</script>
