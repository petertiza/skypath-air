<template>
  <div class="dashboard">
    <h1 class="page-title">Admin Dashboard</h1>

    <div class="export-section">
  <button @click="exportCSV" class="export-btn">
    📤 Export rezervácií (CSV)
  </button>
</div>

    <section class="stats-grid">
      <div class="stat-card flights">
        <div class="stat-icon">✈️</div>
        <div class="stat-content">
          <div class="stat-label">Lety</div>
          <div class="stat-value">{{ formatNumber(stats.flights) }}</div>
        </div>
      </div>

      <div class="stat-card reservations">
        <div class="stat-icon">🎟️</div>
        <div class="stat-content">
          <div class="stat-label">Rezervácie</div>
          <div class="stat-value">{{ formatNumber(stats.reservations) }}</div>
        </div>
      </div>

      <div class="stat-card occupancy">
        <div class="stat-icon">💺</div>
        <div class="stat-content">
          <div class="stat-label">Obsadenosť</div>
          <div class="stat-value">{{ stats.occupancy || 0 }}%</div>
        </div>
      </div>

      <div class="stat-card revenue">
        <div class="stat-icon">💰</div>
        <div class="stat-content">
          <div class="stat-label">Tržby</div>
          <div class="stat-value">{{ formatCurrency(stats.revenue) }}</div>
        </div>
      </div>
    </section>

    <section class="charts-grid">
  <div class="chart-card">
    <div class="chart-header">
      <h3>📈 Rezervácie v čase</h3>
      <span class="chart-period">Posledných 7 dní</span>
    </div>
    <div class="chart-wrapper">
      <canvas ref="resChart"></canvas>
    </div>
  </div>

  <div class="chart-card">
    <div class="chart-header">
      <h3>📊 Obsadenosť lietadiel</h3>
      <span class="chart-period">Aktuálne</span>
    </div>
    <div class="chart-wrapper">
      <canvas ref="airChart"></canvas>
    </div>
  </div>
</section>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from "vue";
import Chart from "chart.js/auto";

const stats = ref({
  flights: 0,
  reservations: 0,
  occupancy: 0,
  revenue: 0
});

async function exportCSV() {
  const user = JSON.parse(localStorage.getItem("user"));

  if (!user?.id) {
    alert("Nie ste prihlásený.");
    return;
  }

  const res = await fetch("/api/export_reservations.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ user_id: user.id })
  });

  if (!res.ok) {
    alert("Nemáte oprávnenie.");
    return;
  }

  const blob = await res.blob();
  const url = window.URL.createObjectURL(blob);

  const a = document.createElement("a");
  a.href = url;
  a.download = "reservations.csv";
  document.body.appendChild(a);
  a.click();
  a.remove();
}

const resChart = ref(null);
const airChart = ref(null);
let resChartInstance = null;
let airChartInstance = null;

const formatNumber = (num) => (num || 0).toLocaleString();
const formatCurrency = (amount) =>
  new Intl.NumberFormat("sk-SK", {
    style: "currency",
    currency: "EUR",
    minimumFractionDigits: 0
  }).format(amount || 0);

onMounted(async () => {
  try {
    const res = await fetch("/api/admin_dashboard.php");
    const data = await res.json();

    stats.value = {
      flights: data.stats?.flights || 0,
      reservations: data.stats?.reservations || 0,
      occupancy: Math.round(data.stats?.occupancy || 0),
      revenue: parseFloat(data.stats?.revenue || 0)
    };

    await nextTick();

    if (resChart.value && data.reservations_chart?.length) {
      if (resChartInstance) resChartInstance.destroy();

      resChartInstance = new Chart(resChart.value, {
        type: "line",
        data: {
          labels: data.reservations_chart.map((r) => r.d).reverse(),
          datasets: [
            {
              label: "Rezervácie",
              data: data.reservations_chart.map((r) => r.c).reverse(),
              borderColor: "#10b981",
              backgroundColor: "rgba(16, 185, 129, 0.1)",
              borderWidth: 3,
              fill: true,
              tension: 0.4,
              pointBackgroundColor: "#10b981",
              pointBorderColor: "#ffffff",
              pointBorderWidth: 2,
              pointRadius: 6
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: {
              beginAtZero: true,
              grid: { color: "rgba(0,0,0,0.05)" }
            },
            x: { grid: { display: false } }
          }
        }
      });
    }

    if (airChart.value && data.aircraft_chart?.length) {
      if (airChartInstance) airChartInstance.destroy();

      airChartInstance = new Chart(airChart.value, {
        type: "doughnut",
        data: {
          labels: data.aircraft_chart.map((a) => a.code),
          datasets: [
            {
              data: data.aircraft_chart.map(
                (a) => a.reservations_count || 0
              ),
              backgroundColor: [
                "#10b981",
                "#f59e0b",
                "#ef4444",
                "#3b82f6",
                "#8b5cf6"
              ],
              borderWidth: 0,
              borderRadius: 8
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: "bottom",
              labels: { padding: 20, usePointStyle: true }
            }
          }
        }
      });
    }
  } catch (error) {
    console.error("Dashboard error:", error);
  }
});
</script>


<style scoped>
.dashboard {
  padding: 24px;
  max-width: 1200px;
  margin: 0 auto;
  background: transparent;
  font-family: "Space Grotesk", system-ui, sans-serif;
  box-sizing: border-box;
}

.page-title {
  font-size: 28px;
  font-weight: 800;
  margin-bottom: 24px;
  background: linear-gradient(135deg, #3b82f6, #10b981);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 20px;
  display: flex;
  gap: 16px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.12);
  border: 1px solid rgba(255,255,255,0.25);
  transition: all 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 22px 50px rgba(0,0,0,0.14);
}

.stat-icon {
  font-size: 32px;
  width: 60px;
  height: 60px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-card.flights .stat-icon { 
  background: rgba(59,130,246,0.1); 
}
.stat-card.reservations .stat-icon { 
  background: rgba(16,185,129,0.1); 
}
.stat-card.occupancy .stat-icon { 
  background: rgba(245,158,11,0.1); 
}
.stat-card.revenue .stat-icon { 
  background: rgba(239,68,68,0.1); 
}

.stat-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-label {
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  font-size: 28px;
  font-weight: 800;
  color: #0a2540;
  line-height: 1.1;
}

.charts-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  margin-bottom: 24px;
}

.chart-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.12);
  border: 1px solid rgba(255,255,255,0.25);
  height: 340px;
  position: relative;
  box-sizing: border-box;
}

.chart-wrapper {
  width: 100%;
  height: 260px;
  overflow: hidden;
}

.chart-card canvas {
  width: 100% !important;
  height: 260px !important;
  max-height: 260px;
  display: block;
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.chart-header h3 {
  font-size: 18px;
  font-weight: 700;
  color: #0a2540;
  margin: 0;
}

.chart-period {
  font-size: 13px;
  color: #6b7280;
  font-weight: 500;
}

.export-section {
  margin-bottom: 20px;
}

.export-btn {
  padding: 12px 20px;
  background: #FF6B35;
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: 0.2s ease;
}

.export-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(255,107,53,0.4);
}

body.dark .page-title {
  background: linear-gradient(135deg, #60a5fa, #22d3ee);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

body.dark .stat-card {
  background: #1e293b;
  border: 1px solid #334155;
  box-shadow: 0 25px 60px rgba(0,0,0,0.6);
}

body.dark .stat-card:hover {
  box-shadow: 0 30px 70px rgba(0,0,0,0.7);
}

body.dark .stat-label {
  color: #94a3b8;
}

body.dark .stat-value {
  color: #e2e8f0;
}

body.dark .chart-card {
  background: #1e293b;
  border: 1px solid #334155;
  box-shadow: 0 25px 60px rgba(0,0,0,0.6);
}

body.dark .chart-header h3 {
  color: #e2e8f0;
}

body.dark .chart-period {
  color: #94a3b8;
}


@media (max-width: 1200px) {
  .dashboard {
    max-width: 100%;
  }

  .charts-grid {
    grid-template-columns: 1fr;
  }

  .chart-card {
    height: 320px;
  }

  .chart-wrapper {
    height: 240px;
  }

  .chart-card canvas {
    height: 240px !important;
  }
}

@media (max-width: 768px) {
  .dashboard {
    padding: 16px;
  }

  .stats-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .stat-value {
    font-size: 22px;
  }

  .chart-card {
    height: 300px;
  }

  .chart-wrapper {
    height: 220px;
  }

  .chart-card canvas {
    height: 220px !important;
  }
}
</style>