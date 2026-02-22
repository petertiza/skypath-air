<template>
  <div class="relative">
    <label v-if="label" class="auto-label">{{ label }}</label>

    <div class="auto-wrapper">
      <input
        v-model="search"
        @input="onInput"
        @focus="showList = true"
        @blur="onBlur"
        :placeholder="placeholder || 'Zadaj mesto'"
        class="auto-input"
        type="text"
      />
      <span class="auto-icon">✈️</span>
    </div>

    <div
      v-if="showList && !loading && grouped.length"
      class="auto-list"
    >
      <div
        v-for="group in grouped"
        :key="group.country"
        class="auto-group"
      >
        <div class="auto-group-title">
          {{ group.country }}
        </div>

        <div
          v-for="airport in group.airports"
          :key="airport.iata"
          class="auto-item"
          @mousedown.prevent="selectAirport(airport)"
        >
          {{ airport.city }} ({{ airport.iata }})
        </div>
      </div>
    </div>

    <div
      v-if="showList && !loading && search && !grouped.length"
      class="auto-empty"
    >
      Žiadne mesto sa nenašlo.
    </div>

    <div v-if="loading" class="auto-loading">
      Načítavam mestá…
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from "vue";

const props = defineProps({
  modelValue: String,
  placeholder: String,
  label: String
});
const emit = defineEmits(["update:modelValue"]);

const search = ref(props.modelValue || "");
const showList = ref(false);
const airports = ref([]);
const loading = ref(false);
let debounce = null;

watch(
  () => props.modelValue,
  (val) => {
    if (val !== search.value) search.value = val || "";
  }
);

function onInput() {
  emit("update:modelValue", search.value);
  if (debounce) clearTimeout(debounce);
  debounce = setTimeout(fetchAirports, 250);
}

async function fetchAirports() {
  const q = search.value.trim();
  if (!q) {
    airports.value = [];
    return;
  }

  loading.value = true;
  try {
    const res = await fetch(`/api/airports.php?q=${encodeURIComponent(q)}`);
    const data = await res.json();
    airports.value = Array.isArray(data) ? data : [];
  } catch {
    airports.value = [];
  } finally {
    loading.value = false;
  }
}

function selectAirport(a) {
  const value = `${a.city} (${a.iata})`;
  search.value = value;
  emit("update:modelValue", value);
  showList.value = false;
}

function onBlur() {
  setTimeout(() => (showList.value = false), 150);
}

const grouped = computed(() => {
  const groups = {};
  for (const a of airports.value) {
    if (!groups[a.country]) groups[a.country] = [];
    groups[a.country].push(a);
  }

  return Object.keys(groups).map((country) => ({
    country,
    airports: groups[country]
  }));
});
</script>


<style scoped>
.auto-label {
  display: block;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #6b7280;
  margin-bottom: 4px;
}

.auto-wrapper {
  position: relative;
}

.auto-input {
  width: 100%;
  padding: 10px 40px 10px 12px;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  font-size: 0.9rem;
  background: #ffffff;
  color: #111827;
  outline: none;
  transition: border-color 0.12s, box-shadow 0.12s, background 0.12s;
}

.auto-input:focus {
  border-color: #1E90FF;
  box-shadow: 0 0 0 1px rgba(30,144,255,0.25);
  background: #f9fafb;
}

.auto-icon {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 0.9rem;
  opacity: 0.6;
}

.auto-list {
  position: absolute;
  z-index: 50;
  margin-top: 4px;
  width: 100%;
  max-height: 220px;
  overflow-y: auto;
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 12px 30px rgba(15,23,42,0.18);
  padding: 4px 0;
}

.auto-item {
  padding: 8px 12px;
  font-size: 0.9rem;
  cursor: pointer;
  color: #111827;
}

.auto-item:hover {
  background: #eff6ff;
}

.auto-empty,
.auto-loading {
  position: absolute;
  margin-top: 4px;
  width: 100%;
  font-size: 0.85rem;
  padding: 6px 10px;
  border-radius: 8px;
  background: #f9fafb;
  color: #6b7280;
  box-shadow: 0 8px 20px rgba(15,23,42,0.12);
  z-index: 40;
}

body.dark .auto-label {
  color: #94a3b8;
}

body.dark .auto-input {
  background: #1e293b;
  color: #f1f5f9;
  border-color: #334155;
}

body.dark .auto-input:focus {
  background: #1e293b;
  border-color: #3b82f6;
  box-shadow: 0 0 0 1px rgba(59,130,246,0.4);
}

body.dark .auto-icon {
  opacity: 0.7;
}

body.dark .auto-list {
  background: #1e293b;
  box-shadow: 0 12px 30px rgba(0,0,0,0.6);
}

body.dark .auto-item {
  color: #e2e8f0;
}

body.dark .auto-item:hover {
  background: #334155;
}

body.dark .auto-empty,
body.dark .auto-loading {
  background: #1e293b;
  color: #94a3b8;
  box-shadow: 0 8px 20px rgba(0,0,0,0.6);
}
</style>
