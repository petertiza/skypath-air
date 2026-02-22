<template>
  <div class="admin-page">
    <section class="card form-card">
      <h2>
        {{ editingId ? "✏️ Upraviť lietadlo" : "➕ Pridať nové lietadlo" }}
      </h2>

      <div class="form-grid">
        <div class="form-group">
          <label>Kód</label>
          <input v-model="form.code" placeholder="B738" />
        </div>

        <div class="form-group">
          <label>Výrobca</label>
          <input v-model="form.manufacturer" placeholder="Boeing" />
        </div>

        <div class="form-group">
          <label>Model</label>
          <input v-model="form.model" placeholder="737-800" />
        </div>

        <div class="form-group">
          <label>Rady sedadiel</label>
          <input type="number" v-model.number="form.seat_rows" />
        </div>

        <div class="form-group">
          <label>Sedadlá v rade</label>
          <input type="number" v-model.number="form.seats_per_row" />
        </div>

        <div class="form-group checkbox-wrap">
          <label class="checkbox">
            <input type="checkbox" v-model="form.active" />
            Aktívne lietadlo
          </label>
        </div>
      </div>

      <div class="actions">
        <button class="btn-primary" @click="save">
          💾 Uložiť
        </button>
        <button
          v-if="editingId"
          class="btn-secondary"
          @click="reset"
        >
          Zrušiť
        </button>
      </div>
    </section>

    <section class="card table-card">
      <h2>📋 Zoznam lietadiel</h2>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Kód</th>
              <th>Model</th>
              <th>Konfigurácia</th>
              <th>Kapacita</th>
              <th>Stav</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in aircrafts" :key="a.id">
              <td class="mono">{{ a.code }}</td>
              <td>{{ a.manufacturer }} {{ a.model }}</td>
              <td>{{ a.seat_rows }} × {{ a.seats_per_row }}</td>
              <td>
                <strong>{{ a.capacity }}</strong>
              </td>
              <td>
                <span
                  class="status"
                  :class="a.active ? 'active' : 'inactive'"
                >
                  {{ a.active ? "Aktívne" : "Neaktívne" }}
                </span>
              </td>
              <td class="actions-cell">
                <button class="icon-btn" @click="edit(a)">✏️</button>
                <button class="icon-btn danger" @click="remove(a.id)">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-if="!aircrafts.length" class="empty">
        Žiadne lietadlá v databáze.
      </p>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";

const aircrafts = ref([]);
const editingId = ref(null);

const form = ref({
  code: "",
  manufacturer: "",
  model: "",
  seat_rows: 0,
  seats_per_row: 0,
  active: true
});

const load = async () => {
  const res = await fetch("/api/admin_aircrafts.php");
  const data = await res.json();
  aircrafts.value = data.aircrafts;
};

onMounted(load);

const reset = () => {
  editingId.value = null;
  form.value = {
    code: "",
    manufacturer: "",
    model: "",
    seat_rows: 0,
    seats_per_row: 0,
    active: true
  };
};

const edit = (a) => {
  editingId.value = a.id;
  form.value = { ...a };
};

const save = async () => {
  const url = editingId.value
    ? `/api/admin_update_aircraft.php?id=${editingId.value}`
    : "/api/admin_create_aircraft.php";

  await fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(form.value)
  });

  await load();
  reset();
};

const remove = async (id) => {
  if (!confirm("Naozaj zmazať lietadlo?")) return;

  const res = await fetch(
    `/api/admin_delete_aircraft.php?id=${id}`,
    { method: "POST" }
  );
  const data = await res.json();

  if (!data.success) alert(data.message);
  else load();
};
</script>

<style scoped>
.admin-page {
  padding: 24px;
  max-width: 1200px;
  margin: 0 auto;
  background: transparent;
  font-family: "Space Grotesk", system-ui, sans-serif;
  box-sizing: border-box;
}

.card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(18px);
  border-radius: 20px;
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.12);
  border: 1px solid rgba(255,255,255,0.25);
  transition: all 0.3s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 22px 60px rgba(0,0,0,0.14);
}

.form-card {
  max-width: 800px;
}

.table-card {
  max-width: 100%;
}

.card h2 {
  margin: 0 0 20px 0;
  color: #0a2540;
  font-size: 22px;
  font-weight: 700;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 6px;
}

.form-group input {
  width: 100%;
  padding: 10px 12px;
  border-radius: 12px;
  border: 2px solid rgba(226,232,240,0.8);
  font-size: 14px;
  background: rgba(255,255,255,0.9);
  backdrop-filter: blur(8px);
  transition: all 0.2s ease;
  box-shadow: 0 3px 10px rgba(0,0,0,0.06);
}

.form-group input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 6px 18px rgba(59,130,246,0.15);
  transform: translateY(-1px);
}

.checkbox-wrap {
  display: flex;
  align-items: center;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(248,250,252,0.7);
  border: 2px solid rgba(226,232,240,0.8);
  cursor: pointer;
  transition: all 0.2s ease;
}

.checkbox-wrap:hover {
  background: rgba(248,250,252,0.9);
  border-color: #3b82f6;
}

.checkbox {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  color: #374151;
  font-weight: 500;
  cursor: pointer;
}

.checkbox input {
  width: auto;
  margin: 0;
}

.actions {
  display: flex;
  gap: 12px;
  justify-content: flex-start;
  flex-wrap: wrap;
}

.btn-primary {
  background: linear-gradient(145deg, #f59e0b, #d97706);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 14px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  box-shadow: 0 10px 25px rgba(245,158,11,0.4);
  transition: all 0.3s ease;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 16px 32px rgba(245,158,11,0.5);
}

.btn-secondary {
  background: rgba(248,250,252,0.9);
  color: #374151;
  border: 2px solid rgba(226,232,240,0.8);
  padding: 10px 20px;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  background: white;
  transform: translateY(-1px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

/* Tabuľka */
.table-container {
  overflow-x: auto;
  border-radius: 16px;
  background: rgba(255,255,255,0.7);
  margin-bottom: 16px;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

th {
  text-align: left;
  color: #6b7280;
  font-weight: 600;
  padding: 14px 12px;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid rgba(226,232,240,0.8);
  white-space: nowrap;
}

td {
  padding: 14px 12px;
  border-bottom: 1px solid rgba(226,232,240,0.5);
}

.mono {
  font-family: "JetBrains Mono", monospace;
  font-weight: 600;
  background: rgba(59,130,246,0.1);
  padding: 4px 8px;
  border-radius: 8px;
  color: #1e40af;
}

.status {
  padding: 6px 12px;
  border-radius: 18px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.status.active {
  background: rgba(16,185,129,0.2);
  color: #10b981;
  border: 1px solid rgba(16,185,129,0.3);
}

.status.inactive {
  background: rgba(239,68,68,0.2);
  color: #ef4444;
  border: 1px solid rgba(239,68,68,0.3);
}

.actions-cell {
  text-align: right;
  white-space: nowrap;
}

.icon-btn {
  background: rgba(226,232,240,0.7);
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  font-size: 16px;
  cursor: pointer;
  margin-left: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.icon-btn:hover {
  transform: scale(1.05);
  box-shadow: 0 6px 16px rgba(0,0,0,0.15);
}

.icon-btn.danger {
  background: rgba(239,68,68,0.15);
  color: #ef4444;
}

.icon-btn.danger:hover {
  background: rgba(239,68,68,0.25);
  box-shadow: 0 6px 16px rgba(239,68,68,0.3);
}

.empty {
  margin-top: 24px;
  color: #9ca3af;
  font-size: 14px;
  font-style: italic;
  text-align: center;
  padding: 28px 20px;
  background: rgba(248,250,252,0.5);
  border-radius: 16px;
  border: 2px dashed rgba(156,163,175,0.3);
}

body.dark .card {
  background: #1e293b;
  border-color: #334155;
  box-shadow: 0 20px 50px rgba(0,0,0,0.6);
}

body.dark .card h2 {
  color: #e2e8f0;
}

body.dark .form-group label {
  color: #cbd5e1;
}

body.dark .form-group input {
  background: #0f172a;
  border-color: #334155;
  color: #f1f5f9;
}

body.dark .form-group input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.3);
}

body.dark .checkbox-wrap {
  background: #0f172a;
  border-color: #334155;
}

body.dark .checkbox {
  color: #cbd5e1;
}

body.dark th {
  color: #94a3b8;
  border-bottom: 2px solid #334155;
}

body.dark td {
  border-bottom: 1px solid #334155;
}

body.dark .mono {
  background: rgba(59,130,246,0.2);
  color: #60a5fa;
}

body.dark .table-container {
  background: #0f172a;
}

body.dark .empty {
  background: rgba(30,41,59,0.6);
  border-color: #334155;
  color: #94a3b8;
}

body.dark .icon-btn {
  background: #334155;
  color: #e2e8f0;
}

body.dark .icon-btn.danger {
  background: rgba(239,68,68,0.2);
  color: #f87171;
}

@media (max-width: 1024px) {
  .admin-page {
    max-width: 100%;
    padding: 20px;
  }

  .card {
    padding: 20px;
    margin-bottom: 20px;
  }
}

@media (max-width: 768px) {
  .admin-page {
    padding: 16px;
  }

  .card {
    padding: 18px;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .actions {
    flex-direction: column;
  }

  .btn-primary,
  .btn-secondary {
    width: 100%;
  }

  table {
    font-size: 13px;
  }

  th,
  td {
    padding: 10px 8px;
  }
}
</style>
