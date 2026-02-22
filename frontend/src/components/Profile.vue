<template>
  <div class="profile-container">
    <div class="profile-header" v-if="user.id">
      <div class="avatar-container">
        <div class="avatar-wrapper">
          <img
            v-if="user.profile_picture"
            :src="user.profile_picture"
            class="avatar-img"
            alt="Profilový obrázok"
          />
          <div v-else class="avatar" :style="avatarStyle">
            {{ userInitials }}
          </div>

          <label class="avatar-overlay" for="profile-upload">
            📸 Zmeniť fotku
          </label>
        </div>

        <input
          id="profile-upload"
          type="file"
          accept="image/*"
          @change="uploadProfilePicture"
          class="hidden"
        />
      </div>

      <div class="user-info">
        <h1>{{ user.fullname }}</h1>
        <p class="email">{{ user.email }}</p>
        <div class="level-badge" :style="{ background: currentLevel.color }">
          {{ currentLevel.name }}
        </div>
      </div>
    </div>

    <div v-if="!user.id" class="not-logged">
      <div class="guest-avatar">👤</div>
      <h2>Nie ste prihlásený</h2>
      <p>Prihláste sa pre prístup k profilu</p>
      <button class="login-btn" @click="goToLogin">Prihlásiť sa</button>
    </div>

    <div v-else class="profile-content">
      <div class="card stats-card">
        <h3>📊 Vernostný program</h3>

        <div class="progress-section">
          <div class="progress-header">
            <span>Body</span>
            <span>
              {{ points }}
              <template v-if="nextLevel"> / {{ nextLevel.min }}</template>
            </span>
          </div>

          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: progress + '%' }"></div>
          </div>

          <p class="progress-text">
            {{ progress }}% na {{ nextLevel?.name || "MAX" }}
          </p>
        </div>

        <div class="quick-stats">
          <div class="stat-item">
            <div class="stat-number">{{ totalFlights }}</div>
            <div class="stat-label">Rezervácií</div>
          </div>

          <div class="stat-item">
            <div class="stat-number">{{ points }}</div>
            <div class="stat-label">Bodov</div>
          </div>
        </div>
      </div>

      <div class="card settings-card">
        <h3>⚙️ Nastavenia</h3>

        <div class="form-group">
          <label>E-mail</label>
          <input :value="user.email" readonly class="form-input" />
        </div>

        <div class="form-group">
          <label>Zmena hesla</label>
          <input
            v-model="newPassword"
            type="password"
            placeholder="Nové heslo (min. 6 znakov)"
            class="form-input"
          />
          <button class="change-pw-btn" @click="changePassword">
            Zmeniť heslo
          </button>
        </div>

        <button class="logout-btn" @click="logout">
          Odhlásiť sa
        </button>
      </div>
    </div>

    <div v-if="notification" class="notification">
      {{ notification }}
      <button 
        @click="notification = ''; document.querySelector('.notification')?.classList.remove('show')" 
        class="notification-close"
      >×</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import { useRouter } from "vue-router"

const router = useRouter()
const user = JSON.parse(localStorage.getItem("user") || "{}")

const notification = ref('')
const showNotification = (message, isError = false) => {
  notification.value = message
  const el = document.querySelector('.notification')
  if (el) {
    el.style.background = isError ? '#ef4444' : '#10b981'
    el.classList.add('show')
    setTimeout(() => {
      el.classList.remove('show')
      notification.value = ''
    }, 3000)
  }
}

const LEVELS = [
  { name: "Bronze", min: 0, max: 999, color: "#cd7f32" },
  { name: "Silver", min: 1000, max: 2499, color: "#c0c0c0" },
  { name: "Gold", min: 2500, max: 9999, color: "#facc15" },
  { name: "Platinum", min: 10000, max: Infinity, color: "#8b5cf6" }
]

const points = ref(0)
const totalFlights = ref(0)
const newPassword = ref("")

const currentLevel = computed(() =>
  LEVELS.find(l => points.value >= l.min && points.value <= l.max)
)

const nextLevel = computed(() => {
  const index = LEVELS.indexOf(currentLevel.value)
  return LEVELS[index + 1] || null
})

const progress = computed(() => {
  if (!nextLevel.value) return 100
  const range = nextLevel.value.min - currentLevel.value.min
  const gained = points.value - currentLevel.value.min
  return Math.min(100, Math.round((gained / range) * 100))
})

const userInitials = computed(() =>
  user.fullname
    ? user.fullname.split(" ").map(n => n[0]).join("").slice(0, 2).toUpperCase()
    : "U"
)

const avatarStyle = computed(() => ({
  background: currentLevel.value.color
}))

onMounted(async () => {
  if (!user.id) return

  const pointsRes = await fetch("/api/get_points.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ user_id: user.id })
  })
  const pointsData = await pointsRes.json()
  points.value = pointsData.points || 0

  const res = await fetch("/api/my_reservations.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ user_id: user.id })
  })
  const reservations = await res.json()
  totalFlights.value = reservations.length
})

const logout = () => {
  localStorage.removeItem("user")
  router.push("/login")
}

const goToLogin = () => router.push("/login")

const changePassword = async () => {
  if (!newPassword.value || newPassword.value.length < 6) return
  const old = prompt("Zadajte staré heslo:")
  if (!old) return

  const res = await fetch("/api/change_password.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      email: user.email,
      old_password: old,
      new_password: newPassword.value
    })
  })

  const data = await res.json()
  alert(data.message)
  if (data.success) newPassword.value = ""
}

const uploadProfilePicture = async (e) => {
  const file = e.target.files[0]
  if (!file || !user.id) return

  e.target.value = ''

  const formData = new FormData()
  formData.append("picture", file)
  formData.append("user_id", user.id)

  try {
    const res = await fetch("/api/upload_profile_picture.php", {
      method: "POST",
      body: formData
    })

    const data = await res.json()
    if (data.success) {
      user.profile_picture = data.picture_url
      localStorage.setItem("user", JSON.stringify(user))
      showNotification(data.message)
    } else {
      showNotification(data.message, true)
    }
  } catch (error) {
    showNotification('Chyba pri nahrávaní', true)
  }
}
</script>

<style scoped>
.profile-container {
  padding: 60px 20px;
  max-width: 1200px;
  margin: 0 auto;
  font-family: "Space Grotesk", system-ui, sans-serif;
}

.profile-header {
  background: var(--bg-alt);
  border-radius: 24px;
  padding: 2.5rem;
  margin-bottom: 2rem;
  box-shadow: var(--shadow-md);
  display: flex;
  gap: 2rem;
  border: 1px solid var(--border-soft);
}

.avatar-container {
  position: relative;
  display: inline-block;
}

.avatar-wrapper {
  position: relative;
  width: 130px;
  height: 130px;
  border-radius: 22px;
  overflow: hidden;
}

.avatar,
.avatar-img {
  width: 100%;
  height: 100%;
  border-radius: 22px;
}

.avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 40px;
  color: white;
  font-weight: 800;
}

.avatar-img {
  object-fit: cover;
}

.avatar-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.7);
  backdrop-filter: blur(2px);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1rem;
  opacity: 0;
  cursor: pointer;
  transition: all 0.3s ease;
  border-radius: 22px;
}

.avatar-wrapper:hover .avatar-overlay {
  opacity: 1;
}

.hidden {
  display: none;
}

.user-info h1 {
  font-size: 2.2rem;
  font-weight: 800;
  margin: 0 0 0.5rem 0;
  color: var(--text-main);
}

.email {
  font-size: 1.1rem;
  color: var(--text-muted);
  margin-bottom: 1rem;
}

.level-badge {
  padding: 10px 24px;
  color: white;
  border-radius: 50px;
  font-size: 14px;
  font-weight: 700;
  display: inline-block;
}

.not-logged {
  text-align: center;
  padding: 4rem 2rem;
  background: var(--bg-alt);
  border-radius: 24px;
  box-shadow: var(--shadow-md);
}

.guest-avatar {
  width: 100px;
  height: 100px;
  margin: 0 auto 2rem;
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  border-radius: 20px;
  font-size: 3rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.not-logged h2 {
  font-size: 2rem;
  color: var(--text-main);
  margin-bottom: 1rem;
}

.not-logged p {
  color: var(--text-muted);
  margin-bottom: 2rem;
}

.login-btn {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: white;
  padding: 1rem 2.5rem;
  border-radius: 16px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  box-shadow: var(--shadow-md);
  transition: 0.3s ease;
}

.login-btn:hover {
  transform: translateY(-2px);
}

.profile-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.card {
  background: var(--bg-alt);
  border-radius: 24px;
  padding: 2.5rem;
  box-shadow: var(--shadow-md);
  border: 1px solid var(--border-soft);
  transition: 0.3s ease;
}

.card:hover {
  transform: translateY(-4px);
}

.card h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-main);
  margin-bottom: 2rem;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 1rem;
  font-weight: 600;
  color: var(--text-main);
}

.progress-bar {
  width: 100%;
  height: 12px;
  background: var(--border-soft);
  border-radius: 10px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--turquoise), var(--primary));
  border-radius: 10px;
  transition: width 0.8s ease;
}

.progress-text {
  margin-top: 0.75rem;
  color: var(--turquoise);
  font-weight: 600;
}

.quick-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--border-soft);
}

.stat-item {
  text-align: center;
  padding: 1.5rem;
  background: var(--bg);
  border-radius: 16px;
}

.stat-number {
  font-size: 2.5rem;
  font-weight: 800;
  color: var(--primary);
}

.stat-label {
  font-size: 0.85rem;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 600;
}

.form-group label {
  font-weight: 600;
  color: var(--text-main);
  margin-bottom: 0.5rem;
  display: block;
}

.form-input {
  width: 100%;
  padding: 1rem 1.25rem;
  border: 2px solid var(--border-soft);
  border-radius: 16px;
  background: var(--bg);
  color: var(--text-main);
}

.form-input:focus {
  outline: none;
  border-color: var(--primary);
}

.logout-btn,
.change-pw-btn {
  width: 100%;
  background: linear-gradient(135deg, var(--accent), var(--turquoise));
  color: white;
  padding: 1.25rem;
  border-radius: 16px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  box-shadow: var(--shadow-md);
  transition: 0.3s ease;
  margin-top: 0.5rem;
}

.logout-btn:hover,
.change-pw-btn:hover {
  transform: translateY(-2px);
}

.notification {
  position: fixed;
  top: 20px;
  right: 20px;
  padding: 1rem 1.5rem;
  border-radius: 12px;
  color: white;
  font-weight: 600;
  box-shadow: var(--shadow-md);
  transform: translateX(400px);
  opacity: 0;
  transition: all 0.3s ease;
  z-index: 1000;
  cursor: pointer;
}

.notification.show {
  transform: translateX(0);
  opacity: 1;
}

.notification-close {
  margin-left: 1rem;
  background: none;
  border: none;
  color: white;
  font-size: 1.5rem;
  cursor: pointer;
}

@media (max-width: 768px) {
  .profile-content {
    grid-template-columns: 1fr;
  }

  .profile-header {
    flex-direction: column;
    text-align: center;
  }
}
</style>
