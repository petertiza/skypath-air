<template>
  <div class="explore-wrapper">
    <div class="explore-container">
      <aside class="sidebar animate-sidebar">
        <h2>🌍 Vyhľadajte lety</h2>
        <p class="hint">
          Vyhľadávanie letov je rýchle a jednoduché. Stačí zadať mesto odletu, cieľ a dátum, a my vám ukážeme tie najlepšie možnosti pre vašu cestu.
        </p>

        <form @submit.prevent="searchFlights" class="flight-search">
          <div class="form-group">
            <label>Odlet</label>
            <Autocomplete
              v-model="searchForm.from"
              placeholder="Zadaj mesto odletu (napr. Bratislava)"
              :suggestions="airportSuggestions"
            />
          </div>

          <div class="form-group">
            <label>Prílet</label>
            <Autocomplete
              v-model="searchForm.to"
              placeholder="Zadajte cieľ (napr. London)"
              :suggestions="airportSuggestions"
            />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Dátum</label>
              <DatePicker
                v-model="searchForm.date"
                placeholder="Vyberte dátum"
                format="DD.MM.YYYY"
                :disablePast="true"
              />
            </div>
            <div class="form-group">
              <label>Počet cestujúcich</label>
              <select v-model="searchForm.passengers">
                <option>1</option>
                <option>2</option>
                <option>3</option>
                <option>4+</option>
              </select>
            </div>
          </div>

          <button 
            type="submit" 
            class="search-btn"
            :disabled="searching"
          >
            {{ searching ? 'Hľadám...' : 'Nájdite lety' }}
          </button>

          <div v-if="searchResults.length" class="search-results">
            <h3>{{ searchResults.length }} letov</h3>
            <div 
              v-for="flight in searchResults.slice(0, 3)" 
              :key="flight.id"
              class="result-mini"
              @click="goToFlights(flight)"
            >
              <div class="mini-route">
                {{ flight.departure_city.split(' ')[0] }} → {{ flight.arrival_city.split(' ')[0] }}
              </div>
              <div class="mini-time">
                {{ formatTime(flight.departure_time) }}
              </div>
              <div class="mini-price">{{ flight.price }}€</div>
            </div>
            <button 
              v-if="searchResults.length > 3" 
              @click="goToFlights(searchResults[0])"
              class="show-all-btn"
            >
              Zobraziť všetky ({{ searchResults.length }})
            </button>
          </div>
        </form>
      </aside>

      <div id="map" class="map animate-map"></div>
    </div>

    <section class="deals">
      <h2 class="deals-title">Najlacnejšie letenky dnes</h2>
      <div v-if="loadingDeals" class="deals-loading">Načítavam ponuky…</div>
      <div v-else class="deals-grid">
        <div
          v-for="f in cheapFlights"
          :key="f.id"
          class="deal-card animate-deal"
          @click="goToFlights(f)"
        >
          <div class="route">{{ f.departure_city }} → {{ f.arrival_city }}</div>
          <div class="date">{{ new Date(f.departure_time).toLocaleDateString('sk-SK') }}</div>
          <div class="price">{{ f.price }} €</div>
        </div>
      </div>
    </section>
    
    <section class="inspiration">
  <h2 class="deals-title">Objavte destinácie podľa typu</h2>

  <div class="category-grid">
    <div 
      v-for="cat in categories"
      :key="cat.name"
      class="category-card"
      :class="{ active: selectedCategory === cat.name }"
      @click="selectCategory(cat.name)"
    >
      <h3>{{ cat.name }}</h3>
      <p>{{ cat.description }}</p>
    </div>
  </div>

  <div v-if="filteredCities.length" class="city-grid">
    <div 
      v-for="city in filteredCities"
      :key="city.name"
      class="city-card"
      @click="selectCity(city)"
    >
      <h3>{{ city.name }}</h3>
      <p>{{ city.description }}</p>
    </div>
  </div>
</section>

  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from "vue";
import { useRouter } from "vue-router";
import maplibregl from "maplibre-gl";
import Autocomplete from "@/components/Autocomplete.vue";
import DatePicker from "@/components/DatePicker.vue";

const router = useRouter();
const map = ref(null);
const routesData = ref([]);
const fromCities = ref([]);
const cheapFlights = ref([]);
const loadingDeals = ref(true);
const searching = ref(false);
const searchResults = ref([]);

let markers = [];
let routeLayerId = "routes-layer";
let routeSourceId = "routes-source";

const airportSuggestions = ref([
  "Kosice (KSC)", "Bratislava (BTS)", "Budapest (BUD)", "Prague (PRG)",
  "Vienna (VIE)", "Warsaw (WAW)", "London (LGW)", "Amsterdam (AMS)",
  "Frankfurt (FRA)", "Paris (CDG)", "Berlin (BER)", "Milan (MXP)"
]);

const searchForm = ref({
  from: "",
  to: "",
  date: null,
  passengers: "1"
});

const categories = [
  {
    name: "Historické mestá",
    description: "Objavte mestá plné histórie a pamiatok."
  },
  {
    name: "Príroda",
    description: "Destinácie obklopené prírodou a krásnymi výhľadmi."
  },
  {
    name: "Moderné mestá",
    description: "Dynamické metropoly s modernou architektúrou."
  },
  {
    name: "Kultúrne mestá",
    description: "Mestá umenia, hudby a kultúrneho dedičstva."
  }
];

const allCities = [
  {
    name: "Kosice (KSC)",
    category: "Príroda",
    description: "Východ Slovenska, historické centrum a blízkosť prírody."
  },
  {
    name: "Bratislava (BTS)",
    category: "Historické mestá",
    description: "Hlavné mesto Slovenska s hradom a Dunajom."
  },
  {
    name: "Budapest (BUD)",
    category: "Historické mestá",
    description: "Termálne kúpele, parlament a historické mosty."
  },
  {
    name: "Prague (PRG)",
    category: "Historické mestá",
    description: "Karlov most, Pražský hrad a gotická architektúra."
  },
  {
    name: "Vienna (VIE)",
    category: "Kultúrne mestá",
    description: "Mesto hudby, cisárske paláce a galérie."
  },
  {
    name: "Warsaw (WAW)",
    category: "Moderné mestá",
    description: "Moderná architektúra a obnovené historické centrum."
  },
  {
    name: "London (LGW)",
    category: "Kultúrne mestá",
    description: "Big Ben, múzeá a svetová kultúrna metropola."
  },
  {
    name: "Amsterdam (AMS)",
    category: "Moderné mestá",
    description: "Kanály, bicykle a moderný životný štýl."
  },
  {
    name: "Frankfurt (FRA)",
    category: "Moderné mestá",
    description: "Finančné centrum s modernými mrakodrapmi."
  },
  {
    name: "Paris (CDG)",
    category: "Kultúrne mestá",
    description: "Eiffelova veža, Louvre a romantická atmosféra."
  },
  {
    name: "Berlin (BER)",
    category: "Moderné mestá",
    description: "Dynamická metropola s bohatou históriou."
  },
  {
    name: "Milan (MXP)",
    category: "Kultúrne mestá",
    description: "Móda, architektúra a blízkosť Álp."
  }
];

const selectedCategory = ref(null);
const filteredCities = ref([]);

function selectCategory(cat) {
  selectedCategory.value = cat;
  filteredCities.value = allCities.filter(c => c.category === cat);
}

function selectCity(city) {
  searchForm.value.to = city.name;

  window.scrollTo({
    top: 0,
    behavior: "smooth"
  });
}

watch(searchForm.value, () => {
  if (searchForm.value.from) {
    const match = fromCities.value.find(c => 
      searchForm.value.from.includes(c.iata) || 
      searchForm.value.from.includes(c.city)
    );
    if (match) {
      drawRoutes(match.iata);
    }
  }
}, { deep: true });

onMounted(async () => {
  map.value = new maplibregl.Map({
    container: "map",
    style: "https://basemaps.cartocdn.com/gl/positron-gl-style/style.json",
    center: [19.5, 48.7],
    zoom: 4
  });

  map.value.addControl(new maplibregl.NavigationControl(), "top-right");

  await loadRoutes();
  extractFromCities();
  drawFromMarkers();
  await loadCheapFlights();
});

onBeforeUnmount(() => {
  if (map.value) map.value.remove();
});

async function searchFlights() {
  if (!searchForm.value.from || !searchForm.value.to || !searchForm.value.date) {
    alert("Vyplňte všetky polia");
    return;
  }

  searching.value = true;
  searchResults.value = [];

  try {
    const params = new URLSearchParams({
      from: searchForm.value.from,
      to: searchForm.value.to,
      date: searchForm.value.date
    });

    const res = await fetch(`/api/flights.php?${params}`);
    const flights = await res.json();
    
    searchResults.value = flights;
  } catch (e) {
    console.error("Search error:", e);
    searchResults.value = [];
  } finally {
    searching.value = false;
  }
}

function goToFlights(flight) {
  const from = flight.departure_city || '';
  const to = flight.arrival_city || '';
  const date = new Date(flight.departure_time).toISOString().split('T')[0];
  
  if (!from || !to || !date) {
    console.error("Chýbajú údaje letu:", flight);
    alert("Neplatné údaje letu");
    return;
  }
  
  const params = new URLSearchParams({
    from,
    to,
    date,
    passengers: "1"
  });
  
  router.push(`/flights?${params}`);
}

function formatTime(timeStr) {
  return new Date(timeStr).toLocaleTimeString('sk-SK', { 
    hour: '2-digit', 
    minute: '2-digit' 
  });
}

async function loadRoutes() {
  try {
    const res = await fetch("/api/routes_map.php");
    routesData.value = await res.json();
  } catch (e) {
    console.error("Routes error:", e);
  }
}

async function loadCheapFlights() {
  try {
    const res = await fetch("/api/flights.php?all=1");
    const all = await res.json();

    if (Array.isArray(all)) {
      const now = new Date();

      cheapFlights.value = all
        .filter(f => new Date(f.departure_time) > now)

        .sort((a, b) => Number(a.price) - Number(b.price))

        .slice(0, 6);
    }
  } catch (e) {
    console.error("Deals error:", e);
  } finally {
    loadingDeals.value = false;
  }
}


function extractFromCities() {
  const mapByIata = {};
  routesData.value.forEach(r => {
    mapByIata[r.from.iata] = r.from;
  });
  fromCities.value = Object.values(mapByIata);
}

function createPlaneMarker(lng, lat, popupHtml, onClick) {
  const el = document.createElement("div");
  el.textContent = "✈️";
  el.style.fontSize = "22px";
  el.style.cursor = "pointer";

  if (onClick) el.addEventListener("click", onClick);

  const marker = new maplibregl.Marker(el)
    .setLngLat([lng, lat])
    .setPopup(new maplibregl.Popup({ offset: 25 }).setHTML(popupHtml))
    .addTo(map.value);

  markers.push(marker);
}

function drawFromMarkers() {
  clearMap();
  fromCities.value.forEach(city => {
    createPlaneMarker(
      city.lng,
      city.lat,
      `<b>${city.city} (${city.iata})</b>`,
      () => {
        searchForm.value.from = `${city.city} (${city.iata})`;
      }
    );
  });
}

function drawRoutes(iata = null) {
  clearMap();

  const filtered = iata 
    ? routesData.value.filter(r => r.from.iata === iata)
    : routesData.value.slice(0, 8); 

  if (!filtered.length) return;

  const geojson = { type: "FeatureCollection", features: [] };

  filtered.forEach(r => {
    geojson.features.push({
      type: "Feature",
      geometry: {
        type: "LineString",
        coordinates: [[r.from.lng, r.from.lat], [r.to.lng, r.to.lat]]
      }
    });

    createPlaneMarker(
      r.to.lng,
      r.to.lat,
      `<b>${r.to.city} (${r.to.iata})</b>`,
      () => {
        searchForm.value.from = `${r.from.city} (${r.from.iata})`;
        searchForm.value.to = `${r.to.city} (${r.to.iata})`;
        searchFlights();
      }
    );
  });

  if (!map.value.getSource(routeSourceId)) {
    map.value.addSource(routeSourceId, { type: "geojson", data: geojson });
  } else {
    map.value.getSource(routeSourceId).setData(geojson);
  }

  if (!map.value.getLayer(routeLayerId)) {
    map.value.addLayer({
      id: routeLayerId,
      type: "line",
      source: routeSourceId,
      paint: { "line-color": "#FF6B35", "line-width": 3 }
    });
  }

  if (geojson.features.length) {
    const bounds = geojson.features.flatMap(f => f.geometry.coordinates);
    map.value.fitBounds(bounds, { padding: 80 });
  }
}

function clearMap() {
  markers.forEach(m => m.remove());
  markers = [];
  if (map.value?.getLayer(routeLayerId)) map.value.removeLayer(routeLayerId);
  if (map.value?.getSource(routeSourceId)) map.value.removeSource(routeSourceId);
}
</script>

<style scoped>
.explore-wrapper {
  width: 100%;
  min-height: 100vh;
  background: transparent;
  margin: 0;
  padding: 0;
  overflow-x: hidden;
}

.explore-container {
  display: flex;
  height: calc(100vh - 100px);
  padding: 20px 0 0 30px;
  margin: 0;
  background: transparent;
}

.sidebar {
  width: 340px;
  background: rgba(255,255,255,0.97);
  backdrop-filter: blur(15px);
  padding: 30px;
  border-radius: 20px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.18);
  z-index: 10;
  flex-shrink: 0;
  overflow-y: auto;
  max-height: calc(100vh - 140px);
}

.sidebar h2 {
  color: #0a2540;
  margin-bottom: 12px;
  font-size: 1.4rem;
}

.hint {
  font-size: 0.88rem;
  color: #555;
  margin-bottom: 24px;
  line-height: 1.4;
}

.flight-search {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 12px;
  font-weight: 600;
  color: #0a2540;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

select {
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  background: white;
  font-size: 15px;
  cursor: pointer;
}

.search-btn {
  width: 100%;
  padding: 14px;
  background: #1E90FF !important; 
  color: white !important;
  border: none;
  border-radius: 50px;
  font-weight: 700;
  font-size: 15px;
  cursor: pointer;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 4px 15px rgba(30, 144, 255, 0.3);
}

.search-btn:hover:not(:disabled) {
  background: #FF6B35 !important;  
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(255, 107, 53, 0.4);
}

.search-btn:disabled {
  background: #94a3b8 !important;
  cursor: not-allowed;
}

.search-results {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.search-results h3 {
  font-size: 14px;
  color: #0a2540;
  margin: 0 0 12px 0;
  font-weight: 600;
}

.result-mini {
  padding: 12px;
  background: #f8fafc;
  border-radius: 12px;
  cursor: pointer;
  margin-bottom: 8px;
  transition: all 0.2s ease;
  border: 2px solid transparent;
}

.result-mini:hover {
  transform: translateY(-1px);
  border-color: #1E90FF;
  box-shadow: 0 5px 15px rgba(30, 144, 255, 0.2);
}

.mini-route {
  font-weight: 600;
  color: #0a2540;
  font-size: 13px;
}

.mini-time {
  font-size: 12px;
  color: #64748b;
  margin: 2px 0;
}

.mini-price {
  font-weight: 700;
  color: #FF6B35;
  font-size: 14px;
}

.show-all-btn {
  width: 100%;
  padding: 10px;
  background: #f1f5f9;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  color: #0a2540;
  font-weight: 600;
  cursor: pointer;
  margin-top: 8px;
  transition: all 0.2s ease;
}

.show-all-btn:hover {
  background: #e2e8f0;
}

.map {
  flex: 1;
  height: 100%;
  border-radius: 20px;
  overflow: hidden;
  margin-left: 20px;
  min-width: 0;
}

.deals {
  padding: 40px 40px 60px;
  margin-top: -10px;
}

.deals-title {
  margin-bottom: 32px;
  color: #0a2540;
  font-size: 28px;
  text-align: center;
}

.deals-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 24px;
  max-width: 1400px;
  margin: 0 auto;
}

.deal-card {
  background: #f8fafc;
  padding: 26px;
  border-radius: 18px;
  cursor: pointer;
  box-shadow: 0 10px 25px rgba(0,0,0,0.12);
  transition: transform 0.25s, box-shadow 0.25s;
}

.deal-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 18px 40px rgba(0,0,0,0.22);
}

.route {
  font-weight: 700;
  color: #0a2540;
  font-size: 1.1rem;
}

.date {
  font-size: 0.84rem;
  color: #6b7280;
  margin: 6px 0;
}

.price {
  font-size: 1.5rem;
  font-weight: 800;
  color: #ff6b35;
  margin-top: 8px;
}

.animate-sidebar, .animate-map, .animate-deal {
  animation: slideInLeft 0.6s ease both;
}

.category-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 40px;
}

.category-card {
  background: white;
  padding: 24px;
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.3s ease;
  text-align: center;
  border: 1px solid rgba(30,144,255,0.15);
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

.category-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 20px 40px rgba(30,144,255,0.2);
}

.category-card.active {
  background: linear-gradient(135deg, #1E90FF, #00C9A7);
  color: white;
  border: none;
}

.city-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
}

.city-card {
  background: white;
  padding: 24px;
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.3s ease;
  border: 1px solid rgba(30,144,255,0.15);
  box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

.city-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 20px 45px rgba(30,144,255,0.25);
}

.inspiration {
  padding: 60px 40px;
  background: transparent;
}

body.dark .category-card,
body.dark .city-card {
  background: #1e293b;
  border-color: #334155;
  box-shadow: 0 15px 35px rgba(0,0,0,0.6);
}

body.dark .sidebar {
  background: #1e293b;
  color: #e2e8f0;
  box-shadow: 0 20px 50px rgba(0,0,0,0.6);
}

body.dark .sidebar h2,
body.dark .form-group label,
body.dark .mini-route,
body.dark .route,
body.dark .deals-title {
  color: #e2e8f0;
}

body.dark .hint,
body.dark .mini-time,
body.dark .date {
  color: #94a3b8;
}

body.dark select {
  background: #0f172a;
  border-color: #334155;
  color: #f1f5f9;
}

body.dark .result-mini {
  background: #0f172a;
  border-color: transparent;
}

body.dark .result-mini:hover {
  border-color: #3b82f6;
  box-shadow: 0 10px 25px rgba(59,130,246,0.3);
}

body.dark .show-all-btn {
  background: #1e293b;
  border-color: #334155;
  color: #e2e8f0;
}

body.dark .deal-card {
  background: #1e293b;
  box-shadow: 0 15px 35px rgba(0,0,0,0.5);
}

body.dark .price {
  color: #22d3ee;
}


@keyframes slideInLeft {
  from { opacity: 0; transform: translateX(-30px); }
  to { opacity: 1; transform: translateX(0); }
}

@media (max-width: 1200px) {
  .sidebar {
    width: 300px;
    padding: 25px;
  }
  .map {
    margin-left: 15px;
  }
}
</style>
