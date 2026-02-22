<template>
  <div class="admin-page">
    <section class="form-card">
      <h2>
        {{ editingId ? "✏️ Upraviť let" : "➕ Pridať nový let" }}
      </h2>

      <div class="form-grid">
        <div class="form-group">
          <label>Číslo letu</label>
          <input v-model="form.flight_number" placeholder="FP123" />
        </div>

        <div class="form-group">
          <label>Typ lietadla</label>
          <input v-model="form.aircraft_type" placeholder="B738/E190" />
        </div>

        <div class="form-group">
          <label>Mesto odletu</label>
          <input v-model="form.departure_city" placeholder="Košice (KSC)" />
        </div>

        <div class="form-group">
          <label>Mesto príletu</label>
          <input v-model="form.arrival_city" placeholder="London (LHR)" />
        </div>

        <div class="form-group">
          <label>Odlet</label>
          <input v-model="form.departure_time" type="datetime-local" />
        </div>

        <div class="form-group">
          <label>Prílet</label>
          <input v-model="form.arrival_time" type="datetime-local" />
        </div>

        <div class="form-group">
          <label>Cena (€)</label>
          <input v-model.number="form.price" type="number" step="0.01" />
        </div>

        <div class="form-group">
          <label>Voľné miesta</label>
          <input v-model.number="form.seats_available" type="number" />
        </div>
      </div>

      <div class="actions">
        <button class="btn-primary" @click="saveFlight">
          {{ editingId ? "💾 Uložiť zmeny" : "➕ Pridať let" }}
        </button>
        <button v-if="editingId" class="btn-secondary" @click="cancelEdit">
          Zrušiť úpravu
        </button>
      </div>
    </section>

    <section class="table-card">
      <h2>📋 Všetky lety ({{ flights.length }})</h2>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Číslo</th>
              <th>Trasa</th>
              <th>Odlet</th>
              <th>Cena</th>
              <th>Miesta</th>
              <th>Akcie</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="f in flights" :key="f.id">
              <td class="id">{{ f.id }}</td>
              <td class="mono">{{ f.flight_number }}</td>
              <td>{{ f.departure_city }} → {{ f.arrival_city }}</td>
              <td>{{ formatDate(f.departure_time) }}</td>
              <td class="price">{{ parseFloat(f.price).toFixed(2) }} €</td>
              <td class="seats">{{ f.used_seats }} / {{ f.total_capacity }}</td>
              <td class="actions-cell">
                <button class="icon-btn edit" @click="editFlight(f)">✏️</button>
                <button class="icon-btn delete" @click="deleteFlight(f.id)">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="!flights.length" class="empty-state">
        <div class="empty-icon">✈️</div>
        <h3>Žiadne lety</h3>
        <p>Pridaj prvý let pomocou formulára vyššie.</p>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const flights = ref([])
const editingId = ref(null)
const form = ref({
  flight_number: '',
  aircraft_type: '',
  departure_city: '',
  arrival_city: '',
  departure_time: '',
  arrival_time: '',
  price: '',
  seats_available: ''
})

const loadFlights = async () => {
  try {
    console.log('Načítavam lety z /api/flights_admin.php...')
    const res = await fetch('/api/flights_admin.php')
    const data = await res.json()
    console.log('API odpoveď:', data)
    flights.value = data.flights || data || []
  } catch (error) {
    console.error('Chyba pri načítaní letov:', error)
    flights.value = []
  }
}

onMounted(() => {
  loadFlights()
})

const resetForm = () => {
  editingId.value = null
  form.value = {
    flight_number: '',
    aircraft_type: '',
    departure_city: '',
    arrival_city: '',
    departure_time: '',
    arrival_time: '',
    price: '',
    seats_available: ''
  }
}

const saveFlight = async () => {
  if (!form.value.flight_number || !form.value.departure_city || !form.value.arrival_city) {
    alert('Vyplň aspoň číslo letu a mestá.')
    return
  }

  try {
    const url = editingId.value 
      ? `/api/admin_update_flight.php?id=${editingId.value}`
      : '/api/admin_create_flight.php'

    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form.value)
    })
    const data = await res.json()
    
    alert(data.message || (editingId.value ? 'Let aktualizovaný!' : 'Let pridaný!'))
    await loadFlights()
    resetForm()
  } catch (error) {
    alert('Chyba pri ukladaní: ' + error.message)
  }
}

const editFlight = (f) => {
  editingId.value = f.id
  form.value = {
    flight_number: f.flight_number,
    aircraft_type: f.aircraft_type,
    departure_city: f.departure_city,
    arrival_city: f.arrival_city,
    departure_time: f.departure_time?.slice(0,16) || '',
    arrival_time: f.arrival_time?.slice(0,16) || '',
    price: f.price || '',
    seats_available: f.seats_available || ''
  }
}

const cancelEdit = () => resetForm()

const deleteFlight = async (id) => {
  if (!confirm('Naozaj zmazať tento let?')) return
  try {
    const res = await fetch(`/api/admin_delete_flight.php?id=${id}`, { method: 'POST' })
    const data = await res.json()
    alert(data.message || 'Let zmazaný!')
    await loadFlights()
  } catch (error) {
    alert('Chyba pri mazaní: ' + error.message)
  }
}

const formatDate = (dt) => {
  try {
    return new Date(dt).toLocaleString('sk-SK', { 
      day: '2-digit', 
      month: '2-digit', 
      hour: '2-digit', 
      minute: '2-digit' 
    })
  } catch {
    return 'Neplatný dátum'
  }
}
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

.form-card, .table-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(18px);
  border-radius: 20px;
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.12);
  border: 1px solid rgba(255,255,255,0.25);
}

.form-card {
  max-width: 900px;
}

.table-card {
  max-width: 100%;
}

h1, h2 {
  color: #0a2540;
  font-weight: 700;
  margin: 0 0 20px 0;
}

h2 {
  font-size: 22px;
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

.actions {
  display: flex;
  gap: 12px;
  justify-content: flex-start;
  flex-wrap: wrap;
}

.btn-primary {
  background: linear-gradient(145deg, #3b82f6, #1d4ed8);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 14px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  box-shadow: 0 10px 25px rgba(59,130,246,0.35);
  transition: all 0.3s ease;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 16px 32px rgba(59,130,246,0.45);
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
  box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.table-container {
  border-radius: 16px;
  background: rgba(255,255,255,0.7);
  margin-bottom: 16px;
  overflow-x: auto;
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

.id {
  font-family: monospace;
  font-weight: 600;
  color: #6b7280;
}

.mono {
  font-family: "JetBrains Mono", monospace;
  font-weight: 600;
  background: rgba(59,130,246,0.1);
  padding: 4px 8px;
  border-radius: 6px;
  color: #1e40af;
}

.price {
  font-weight: 700;
  color: #f59e0b;
}

.seats {
  font-weight: 600;
  color: #10b981;
}

.actions-cell {
  text-align: right;
  white-space: nowrap;
}

.icon-btn {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: none;
  font-size: 16px;
  cursor: pointer;
  margin-left: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.icon-btn.edit {
  background: rgba(59,130,246,0.15);
  color: #3b82f6;
}

.icon-btn.edit:hover {
  background: rgba(59,130,246,0.25);
  transform: scale(1.05);
  box-shadow: 0 6px 16px rgba(59,130,246,0.3);
}

.icon-btn.delete {
  background: rgba(239,68,68,0.15);
  color: #ef4444;
}

.icon-btn.delete:hover {
  background: rgba(239,68,68,0.25);
  transform: scale(1.05);
  box-shadow: 0 6px 16px rgba(239,68,68,0.3);
}

.empty-state {
  text-align: center;
  padding: 36px 24px;
  background: rgba(248,250,252,0.5);
  border-radius: 18px;
  border: 2px dashed rgba(156,163,175,0.3);
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.empty-state h3 {
  color: #6b7280;
  font-size: 20px;
  margin: 0 0 8px 0;
}

.empty-state p {
  color: #9ca3af;
  font-size: 14px;
  margin: 0;
}

body.dark .form-card,
body.dark .table-card {
  background: #1e293b;
  border-color: #334155;
  box-shadow: 0 20px 50px rgba(0,0,0,0.6);
}

body.dark h2 {
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

body.dark .table-container {
  background: #0f172a;
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

body.dark .price {
  color: #fbbf24;
}

body.dark .seats {
  color: #34d399;
}

body.dark .id {
  color: #94a3b8;
}

body.dark .empty-state {
  background: rgba(30,41,59,0.6);
  border-color: #334155;
}

body.dark .empty-state h3 {
  color: #cbd5e1;
}

body.dark .empty-state p {
  color: #94a3b8;
}

body.dark .icon-btn.edit {
  background: rgba(59,130,246,0.2);
  color: #60a5fa;
}

body.dark .icon-btn.delete {
  background: rgba(239,68,68,0.2);
  color: #f87171;
}

@media (max-width: 1024px) {
  .admin-page {
    max-width: 100%;
    padding: 20px;
  }

  .form-card, .table-card {
    padding: 20px;
    margin-bottom: 20px;
  }
}

@media (max-width: 768px) {
  .admin-page {
    padding: 16px;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .actions {
    flex-direction: column;
  }

  .btn-primary, .btn-secondary {
    width: 100%;
  }

  table {
    font-size: 13px;
  }

  th, td {
    padding: 10px 8px;
  }
}
</style>