<template>
  <div class="admin-page">
    <h1 class="page-title">🎟️ Správa rezervácií</h1>

    <section class="card table-card">
      <div class="header-row">
        <h2 class="card-title">📋 Rezervácie ({{ reservations.length }})</h2>
        <div class="status-legend">
          <span class="legend-item reserved">● Reserved</span>
          <span class="legend-item hold">● Hold</span>
          <span class="legend-item cancelled">● Cancelled</span>
        </div>
      </div>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Používateľ</th>
              <th>Let</th>
              <th>Sedadlo</th>
              <th>Stav</th>
              <th>Dátum</th>
              <th>Akcie</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="r in reservations" :key="r.id" class="reservation-row">
              <td class="id">{{ r.id }}</td>
              <td class="user">
                <div class="user-info">
                  <div class="fullname">{{ r.fullname }}</div>
                  <div class="email">{{ r.email }}</div>
                </div>
              </td>
              <td class="flight">
                <div class="flight-number">{{ r.flight_number }}</div>
                <div class="route">{{ r.departure_city }} → {{ r.arrival_city }}</div>
              </td>
              <td class="seat">
                <span class="seat-badge">{{ r.seat_number }}</span>
              </td>
              <td>
                <span :class="['status-badge', statusClass(r.status)]">
                  {{ statusLabel(r.status) }}
                </span>
              </td>
              <td class="date">{{ formatDate(r.created_at) }}</td>
              <td class="actions-cell">
                <button
                  v-if="r.status === 'reserved' || r.status === 'hold'"
                  class="icon-btn cancel"
                  @click="cancel(r.id)"
                  title="Zrušiť rezerváciu"
                >
                  🚫
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="!reservations.length" class="empty-state">
        <div class="empty-icon">🎟️</div>
        <h3>Žiadne rezervácie</h3>
        <p>Rezervácie sa objavia tu po prvom nákupe.</p>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";

const reservations = ref([]);

async function loadReservations() {
  try {
    const res = await fetch("/api/admin_reservations.php");
    const data = await res.json();
    reservations.value = data.reservations || [];
  } catch (error) {
    console.error('Chyba pri načítaní rezervácií:', error);
  }
}

async function cancel(id) {
  if (!confirm("Naozaj zrušiť rezerváciu?")) return;
  try {
    await fetch(`/api/admin_cancel_reservation.php?id=${id}`, { method: "POST" });
    await loadReservations();
  } catch (error) {
    alert('Chyba pri zrušení: ' + error.message);
  }
}

function formatDate(dt) {
  try {
    return new Date(dt).toLocaleString("sk-SK", {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch {
    return 'Neplatný dátum';
  }
}

function statusClass(status) {
  return {
    reserved: 'reserved',
    hold: 'hold',
    cancelled: 'cancelled'
  }[status] || 'unknown';
}

function statusLabel(status) {
  return {
    reserved: 'Reserved',
    hold: 'Hold',
    cancelled: 'Cancelled'
  }[status] || 'Unknown';
}

onMounted(loadReservations);
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

.page-title {
  font-size: 26px;
  font-weight: 700;
  margin-bottom: 24px;
  color: #0a2540;
  background: linear-gradient(135deg, #ef4444, #dc2626);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
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

.table-card {
  max-width: 100%;
}

.header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 20px;
  gap: 16px;
}

.card-title {
  font-size: 20px;
  font-weight: 700;
  margin: 0;
  color: #0a2540;
}

.status-legend {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 500;
}

.legend-item.reserved { color: #10b981; }
.legend-item.hold { color: #f59e0b; }
.legend-item.cancelled { color: #ef4444; }

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

.reservation-row:hover {
  background: rgba(59,130,246,0.05);
}

.id {
  font-family: "JetBrains Mono", monospace;
  font-weight: 600;
  color: #6b7280;
  font-size: 13px;
}

.user {
  min-width: 160px;
}

.user-info {
  display: flex;
  flex-direction: column;
}

.fullname {
  font-weight: 600;
  color: #1f2937;
  font-size: 14px;
}

.email {
  color: #6b7280;
  font-size: 12px;
  margin-top: 2px;
}

.flight {
  min-width: 220px;
}

.flight-number {
  font-family: "JetBrains Mono", monospace;
  font-weight: 700;
  color: #3b82f6;
  font-size: 14px;
}

.route {
  color: #6b7280;
  font-size: 12px;
  margin-top: 4px;
}

.seat {
  text-align: center;
}

.seat-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 28px;
  background: linear-gradient(135deg, #6b7280, #9ca3af);
  color: white;
  border-radius: 8px;
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge {
  padding: 6px 12px;
  border-radius: 18px;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  border: 2px solid;
}

.status-badge.reserved {
  background: rgba(16,185,129,0.2);
  color: #10b981;
  border-color: rgba(16,185,129,0.3);
}

.status-badge.hold {
  background: rgba(245,158,11,0.2);
  color: #f59e0b;
  border-color: rgba(245,158,11,0.3);
}

.status-badge.cancelled {
  background: rgba(239,68,68,0.2);
  color: #ef4444;
  border-color: rgba(239,68,68,0.3);
}

.date {
  color: #4b5563;
  font-weight: 500;
  font-size: 13px;
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
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.icon-btn.cancel {
  background: rgba(239,68,68,0.15);
  color: #ef4444;
}

.icon-btn.cancel:hover {
  background: rgba(239,68,68,0.25);
  transform: scale(1.05);
  box-shadow: 0 6px 16px rgba(239,68,68,0.3);
}

.empty-state {
  text-align: center;
  padding: 40px 24px;
  background: rgba(248,250,252,0.5);
  border-radius: 18px;
  border: 2px dashed rgba(156,163,175,0.3);
}

.empty-icon {
  font-size: 56px;
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

body.dark .page-title {
  background: linear-gradient(135deg, #f87171, #ef4444);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

body.dark .card {
  background: #1e293b;
  border-color: #334155;
  box-shadow: 0 20px 50px rgba(0,0,0,0.6);
}

body.dark .card-title {
  color: #e2e8f0;
}

body.dark th {
  color: #94a3b8;
  border-bottom: 2px solid #334155;
}

body.dark td {
  border-bottom: 1px solid #334155;
}

body.dark .reservation-row:hover {
  background: rgba(59,130,246,0.12);
}

body.dark .fullname {
  color: #f1f5f9;
}

body.dark .email {
  color: #94a3b8;
}

body.dark .flight-number {
  color: #60a5fa;
}

body.dark .route {
  color: #94a3b8;
}

body.dark .id {
  color: #94a3b8;
}

body.dark .date {
  color: #cbd5e1;
}

body.dark .seat-badge {
  background: linear-gradient(135deg, #334155, #475569);
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

body.dark .legend-item.reserved { color: #34d399; }
body.dark .legend-item.hold { color: #fbbf24; }
body.dark .legend-item.cancelled { color: #f87171; }

body.dark .icon-btn.cancel {
  background: rgba(239,68,68,0.2);
  color: #f87171;
}

body.dark .table-container {
  background: #0f172a;
}

body.dark th {
  background: #1e293b;
}

body.dark td {
  background: transparent;
}

body.dark .reservation-row:hover {
  background: rgba(59,130,246,0.08);
}

body.dark .id {
  color: #94a3b8;
}

body.dark .flight-number {
  color: #60a5fa;
}

body.dark .seat-badge {
  background: linear-gradient(135deg, #334155, #1e293b);
  color: #e2e8f0;
}

body.dark .status-badge.reserved {
  background: rgba(34,197,94,0.15);
  border-color: rgba(34,197,94,0.35);
  color: #4ade80;
}

body.dark .status-badge.hold {
  background: rgba(245,158,11,0.15);
  border-color: rgba(245,158,11,0.35);
  color: #fbbf24;
}

body.dark .status-badge.cancelled {
  background: rgba(239,68,68,0.15);
  border-color: rgba(239,68,68,0.35);
  color: #f87171;
}

body.dark .empty-state {
  background: #1e293b;
  border: 2px dashed #334155;
}

body.dark .icon-btn.cancel:hover {
  background: rgba(239,68,68,0.25);
  box-shadow: 0 6px 16px rgba(239,68,68,0.4);
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

  .header-row {
    flex-direction: column;
    gap: 12px;
  }

  .status-legend {
    justify-content: center;
  }

  table {
    font-size: 13px;
  }

  th,
  td {
    padding: 10px 8px;
  }

  .seat-badge {
    width: 40px;
    height: 24px;
    font-size: 11px;
  }

  .icon-btn {
    width: 32px;
    height: 32px;
    font-size: 14px;
  }
}
</style>
