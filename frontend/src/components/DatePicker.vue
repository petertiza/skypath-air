<template>
  <div ref="triggerRef" class="dp-wrapper">
    <label v-if="label" class="dp-label">{{ label }}</label>

    <button class="dp-input" type="button" @click="toggle">
      <span>{{ formattedDate || placeholder || "Vyber dátum" }}</span>
      <span class="dp-icon">📅</span>
    </button>

    <Teleport to="body">
      <div v-if="open" ref="popupRef" class="dp-popup" :style="popupStyle">
        <div class="dp-header">
          <button class="dp-nav" @click.stop="prev">‹</button>

          <button class="dp-title" @click.stop="toggleView">
            {{ headerTitle }}
          </button>

          <button class="dp-nav" @click.stop="next">›</button>
        </div>

        <div v-if="view === 'years'" class="dp-grid years">
          <button
            v-for="y in decadeYears"
            :key="y"
            class="dp-cell"
            @click="selectYear(y)"
          >
            {{ y }}
          </button>
        </div>

        <div v-else-if="view === 'months'" class="dp-grid months">
          <button
            v-for="(m,i) in months"
            :key="m"
            class="dp-cell"
            @click="selectMonth(i)"
          >
            {{ m.slice(0,3) }}
          </button>
        </div>

        <div v-else>
          <div class="dp-weekdays">
            <span v-for="d in weekdays" :key="d">{{ d }}</span>
          </div>

          <div class="dp-grid days">
            <span v-for="n in startOffset" :key="'e'+n" />
            <button
              v-for="d in daysInMonth"
              :key="d"
              :class="[
                'dp-cell',
                isSelected(d) && 'selected',
                isDisabled(d) && 'disabled'
              ]"
              @click="selectDay(d)"
            >
              {{ d }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from "vue";

const props = defineProps({
  modelValue: String,
  label: String,
  placeholder: String,
  disablePast: { type: Boolean, default: false },
  disableFuture: { type: Boolean, default: false },
  minYear: { type: Number, default: 1950 }
});
const emit = defineEmits(["update:modelValue"]);

const open = ref(false);
const view = ref("days");
const triggerRef = ref(null);
const popupRef = ref(null);
const popupStyle = ref({});

const today = new Date();
const currentYear = ref(today.getFullYear());
const currentMonth = ref(today.getMonth());

const months = [
  "január","február","marec","apríl","máj","jún",
  "júl","august","september","október","november","december"
];
const weekdays = ["Po","Ut","St","Št","Pi","So","Ne"];

const formattedDate = computed(() =>
  props.modelValue
    ? new Date(props.modelValue).toLocaleDateString("sk-SK")
    : ""
);

const daysInMonth = computed(
  () => new Date(currentYear.value, currentMonth.value + 1, 0).getDate()
);

const startOffset = computed(() => {
  const d = new Date(currentYear.value, currentMonth.value, 1).getDay();
  return d === 0 ? 6 : d - 1;
});

const decadeStart = computed(() =>
  Math.floor(currentYear.value / 10) * 10
);

const decadeYears = computed(() =>
  Array.from({ length: 10 }, (_, i) => decadeStart.value + i)
    .filter(y => y >= props.minYear && y <= today.getFullYear() + 5)
);

const headerTitle = computed(() => {
  if (view.value === "years")
    return `${decadeStart.value} – ${decadeStart.value + 9}`;
  if (view.value === "months")
    return currentYear.value;
  return `${months[currentMonth.value]} ${currentYear.value}`;
});

function toggle() {
  open.value = !open.value;
  view.value = "days";
  if (open.value) nextTick(position);
}

function position() {
  const r = triggerRef.value.getBoundingClientRect();
  const popupHeight = 320;
  const margin = 8;

  const spaceBelow = window.innerHeight - r.bottom;
  let top;

  if (spaceBelow > popupHeight + margin) {
    top = r.bottom + margin;
  } else {
    top = r.top - popupHeight - margin;
  }

  top = Math.max(top, 8);

  popupStyle.value = {
    position: "fixed",
    top: `${top}px`,
    left: `${r.left}px`,
    zIndex: 9999
  };
}

function toggleView() {
  view.value =
    view.value === "days" ? "months" :
    view.value === "months" ? "years" : "days";
}

function prev() {
  if (view.value === "years") currentYear.value -= 10;
  else if (view.value === "months") currentYear.value--;
  else currentMonth.value = currentMonth.value === 0 ? 11 : currentMonth.value - 1;
}

function next() {
  if (view.value === "years") currentYear.value += 10;
  else if (view.value === "months") currentYear.value++;
  else currentMonth.value = currentMonth.value === 11 ? 0 : currentMonth.value + 1;
}

function selectYear(y) {
  currentYear.value = y;
  view.value = "months";
}

function selectMonth(m) {
  currentMonth.value = m;
  view.value = "days";
}

function isDisabled(day) {
  const d = new Date(currentYear.value, currentMonth.value, day);
  d.setHours(0, 0, 0, 0);

  const todayDate = new Date();
  todayDate.setHours(0, 0, 0, 0);

  if (props.disablePast && d < todayDate) return true;
  if (props.disableFuture && d > todayDate) return true;

  return false;
}

function onScrollClose() {
  if (open.value) {
    open.value = false;
  }
}

function selectDay(d) {
  if (isDisabled(d)) return;

  emit(
    "update:modelValue",
    `${currentYear.value}-${String(currentMonth.value + 1).padStart(2,"0")}-${String(d).padStart(2,"0")}`
  );
  open.value = false;
}

function isSelected(d) {
  return props.modelValue ===
    `${currentYear.value}-${String(currentMonth.value + 1).padStart(2,"0")}-${String(d).padStart(2,"0")}`;
}

function onClickOutside(e) {
  if (
    triggerRef.value &&
    popupRef.value &&
    !triggerRef.value.contains(e.target) &&
    !popupRef.value.contains(e.target)
  ) {
    open.value = false;
  }
}

onMounted(() => {
  document.addEventListener("mousedown", onClickOutside);
  window.addEventListener("scroll", onScrollClose, true);
});

onBeforeUnmount(() => {
  document.removeEventListener("mousedown", onClickOutside);
  window.removeEventListener("scroll", onScrollClose, true);
});
</script>



<style scoped>
.dp-wrapper { width:100%; }

.dp-label {
  font-size:.75rem;
  text-transform:uppercase;
  letter-spacing:.08em;
  color:#6b7280;
  margin-bottom:6px;
}

.dp-input {
  width:100%;
  padding:14px 16px;
  border-radius:14px;
  border:1.5px solid #d1d5db;
  background:white;
  display:flex;
  justify-content:space-between;
  cursor:pointer;
  transition:.2s;
}

.dp-input:hover {
  border-color:#1E90FF;
  box-shadow:0 0 0 3px rgba(30,144,255,.15);
}

.dp-popup {
  width:320px;
  background:white;
  border-radius:20px;
  padding:16px;
  box-shadow:0 25px 60px rgba(0,0,0,.25);
  animation:fadeUp .18s ease;
}

@keyframes fadeUp {
  from { opacity:0; transform:translateY(8px); }
  to { opacity:1; transform:none; }
}

.dp-header {
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:10px;
}

.dp-title {
  font-weight:700;
  background:none;
  border:none;
  cursor:pointer;
}

.dp-nav {
  width:34px;
  height:34px;
  border-radius:50%;
  border:none;
  background:#eff6ff;
  color:#1E90FF;
  font-size:18px;
  cursor:pointer;
}

.dp-weekdays {
  display:grid;
  grid-template-columns:repeat(7,1fr);
  text-align:center;
  font-size:.7rem;
  color:#9ca3af;
  margin-bottom:6px;
}

.dp-grid {
  display:grid;
  gap:6px;
}

.dp-grid.days { grid-template-columns:repeat(7,1fr); }
.dp-grid.months,
.dp-grid.years { grid-template-columns:repeat(3,1fr); }

.dp-cell {
  padding:10px;
  border-radius:12px;
  background:#f3f4f6;
  cursor:pointer;
  transition:.15s;
}

.dp-cell:hover {
  background:#e0f2fe;
}

.dp-cell.selected {
  background:linear-gradient(135deg,#1E90FF,#00C9A7);
  color:white;
}
.dp-cell.disabled {
  background: #f3f4f6;
  color: #9ca3af;
  cursor: not-allowed;
  pointer-events: none;
}

body.dark .dp-label {
  color: #94a3b8;
}

body.dark .dp-input {
  background: #1e293b;
  border: 1.5px solid #334155;
  color: #e2e8f0;
}

body.dark .dp-input:hover {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,.25);
}

body.dark .dp-popup {
  background: #1e293b;
  border: 1px solid #334155;
  box-shadow: 0 25px 60px rgba(0,0,0,.7);
}

body.dark .dp-title {
  color: #e2e8f0;
}

body.dark .dp-nav {
  background: #334155;
  color: #60a5fa;
}

body.dark .dp-nav:hover {
  background: #3b82f6;
  color: white;
}

body.dark .dp-weekdays {
  color: #64748b;
}

body.dark .dp-cell {
  background: #334155;
  color: #e2e8f0;
}

body.dark .dp-cell:hover {
  background: #475569;
}

body.dark .dp-cell.disabled {
  background: #1e293b;
  color: #475569;
}

body.dark .dp-cell.selected {
  background: linear-gradient(135deg,#3b82f6,#22d3ee);
  color: white;
}

</style>
