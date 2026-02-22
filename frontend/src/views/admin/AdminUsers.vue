<template>
  <div class="admin-page">
    <h1 class="page-title">👤 Správa používateľov</h1>

    <section class="card form-card">
      <h2 class="card-title">➕ Pridať používateľa</h2>

      <div class="form-grid">
        <div class="form-group">
          <label>Meno a priezvisko</label>
          <input v-model="newUser.fullname" placeholder="Peter Novák" />
        </div>

        <div class="form-group">
          <label>Email</label>
          <input v-model="newUser.email" type="email" placeholder="user@example.com" />
        </div>

        <div class="form-group">
          <label>Heslo</label>
          <input v-model="newUser.password" type="password" placeholder="Minimálne 6 znakov" />
        </div>

        <div class="form-group">
          <label>Rola</label>
          <select v-model="newUser.role">
            <option value="user">👤 Používateľ</option>
            <option value="admin">👑 Admin</option>
          </select>
        </div>
      </div>

      <div class="actions-row">
        <button class="btn-primary" @click="createUser">
          <span>Vytvoriť používateľa</span>
        </button>
      </div>
    </section>

    <section class="card table-card">
      <h2 class="card-title">📋 Používatelia ({{ users.length }})</h2>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Meno</th>
              <th>Email</th>
              <th>Rola</th>
              <th>Stav</th>
              <th>Bodov</th>
              <th>Akcie</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="u in users" :key="u.id">
              <td class="id">{{ u.id }}</td>
              <td class="name">{{ u.fullname }}</td>
              <td class="email">{{ u.email }}</td>

              <td>
                <select v-model="u.role" class="role-select" @change="save(u)">
                  <option value="user">👤 Používateľ</option>
                  <option value="admin">👑 Admin</option>
                </select>
              </td>

              <td>
                <label class="status-toggle">
                  <input 
                    type="checkbox" 
                    :checked="u.is_active" 
                    @change="toggle(u)"
                  />
                  <span class="slider"></span>
                </label>
              </td>

              <td class="points">{{ u.points || 0 }} <span class="points-label">pts</span></td>

              <td class="actions-cell">
                <button class="icon-btn save" @click="save(u)" title="Uložiť zmeny">💾</button>
                <button class="icon-btn delete" @click="remove(u.id)" title="Zmazať">🗑️</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="!users.length" class="empty-state">
        <div class="empty-icon">👤</div>
        <h3>Žiadni používatelia</h3>
        <p>Vytvor prvý účet vyššie.</p>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";

const users = ref([]);
const newUser = ref({
  fullname: "",
  email: "",
  password: "",
  role: "user"
});

async function loadUsers() {
  try {
    const res = await fetch("/api/admin_users.php");
    const data = await res.json();
    users.value = data.users || [];
  } catch (error) {
    console.error('Chyba pri načítaní používateľov:', error);
  }
}

async function save(u) {
  try {
    await fetch("/api/admin_update_user.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        id: u.id,
        role: u.role,
        is_active: u.is_active
      })
    });
  } catch (error) {
    alert('Chyba pri ukladaní: ' + error.message);
  }
}

async function toggle(u) {
  u.is_active = u.is_active ? 0 : 1;
  await save(u);
}

async function remove(id) {
  if (!confirm("Naozaj zmazať používateľa?")) return;
  try {
    await fetch(`/api/admin_delete_user.php?id=${id}`, { method: "POST" });
    users.value = users.value.filter(u => u.id !== id);
  } catch (error) {
    alert('Chyba pri mazaní: ' + error.message);
  }
}

async function createUser() {
  if (!newUser.value.email || !newUser.value.password) {
    alert("Vyplň email a heslo");
    return;
  }

  try {
    const res = await fetch("/api/admin_create_user.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(newUser.value)
    });

    const data = await res.json();
    alert(data.message || 'Používateľ vytvorený!');

    if (data.success) {
      newUser.value = { fullname: "", email: "", password: "", role: "user" };
      loadUsers();
    }
  } catch (error) {
    alert('Chyba pri vytváraní: ' + error.message);
  }
}

onMounted(loadUsers);
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
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
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

.form-card {
  max-width: 700px;
}

.table-card {
  max-width: 100%;
}

.card-title {
  font-size: 20px;
  font-weight: 700;
  margin-bottom: 20px;
  color: #0a2540;
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

.form-group input,
.form-group select {
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

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 6px 18px rgba(59,130,246,0.15);
  transform: translateY(-1px);
}

.actions-row {
  display: flex;
  gap: 12px;
  justify-content: flex-start;
  flex-wrap: wrap;
}

.btn-primary {
  background: linear-gradient(145deg, #10b981, #059669);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 14px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  box-shadow: 0 10px 25px rgba(16,185,129,0.4);
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 16px 32px rgba(16,185,129,0.5);
}

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

.id {
  font-family: "JetBrains Mono", monospace;
  font-weight: 600;
  color: #6b7280;
  font-size: 13px;
}

.name {
  font-weight: 600;
  color: #1f2937;
}

.email {
  color: #3b82f6;
  font-weight: 500;
}

.role-select {
  padding: 6px 10px;
  border-radius: 10px;
  border: 2px solid rgba(226,232,240,0.8);
  background: white;
  font-weight: 500;
  min-width: 130px;
  font-size: 13px;
}

.role-select:focus {
  outline: none;
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
}

.status-toggle {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 26px;
}

.status-toggle input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #d1d5db;
  transition: 0.3s;
  border-radius: 26px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 20px;
  width: 20px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

input:checked + .slider {
  background-color: #10b981;
}

input:checked + .slider:before {
  transform: translateX(22px);
}

.points {
  font-weight: 700;
  color: #f59e0b;
  font-size: 15px;
}

.points-label {
  font-size: 11px;
  color: #6b7280;
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

.icon-btn.save {
  background: rgba(16,185,129,0.15);
  color: #10b981;
}

.icon-btn.save:hover {
  background: rgba(16,185,129,0.25);
  transform: scale(1.05);
  box-shadow: 0 6px 16px rgba(16,185,129,0.3);
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
  background: linear-gradient(135deg, #60a5fa, #3b82f6);
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

body.dark .name {
  color: #f1f5f9;
}

body.dark .email {
  color: #60a5fa;
}

body.dark .id {
  color: #94a3b8;
}

body.dark .points {
  color: #fbbf24;
}

body.dark .points-label {
  color: #94a3b8;
}

body.dark .form-group label {
  color: #cbd5e1;
}

body.dark .form-group input,
body.dark .form-group select,
body.dark .role-select {
  background: #0f172a;
  border-color: #334155;
  color: #f1f5f9;
}

body.dark .form-group input:focus,
body.dark .form-group select:focus,
body.dark .role-select:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.3);
}

body.dark .role-select {
  background: #0f172a;
}

body.dark .slider {
  background-color: #475569;
}

body.dark input:checked + .slider {
  background-color: #10b981;
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

body.dark .table-container {
  background: #0f172a;
}

body.dark th {
  background: #1e293b;
}

body.dark td {
  background: transparent;
}

body.dark tr:hover {
  background: rgba(59,130,246,0.08);
}

body.dark .form-group input,
body.dark .form-group select {
  background: #0f172a;
  border-color: #334155;
  color: #f1f5f9;
  box-shadow: none;
}

body.dark .role-select {
  background: #0f172a;
  border-color: #334155;
  color: #f1f5f9;
}

body.dark .slider {
  background-color: #334155;
}

body.dark input:checked + .slider {
  background-color: #10b981;
}

body.dark .points {
  color: #fbbf24;
}

body.dark .empty-state {
  background: #1e293b;
  border: 2px dashed #334155;
}

body.dark .icon-btn.save {
  background: rgba(34,197,94,0.2);
  color: #4ade80;
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

  .card {
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

  .actions-row {
    flex-direction: column;
  }

  .btn-primary {
    width: 100%;
    justify-content: center;
  }

  table {
    font-size: 13px;
  }

  th,
  td {
    padding: 10px 8px;
  }

  .icon-btn {
    width: 32px;
    height: 32px;
    font-size: 14px;
  }
}
</style>
