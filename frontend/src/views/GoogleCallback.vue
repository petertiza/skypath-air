<template>
  <div style="padding: 50px; text-align: center;">
    <h2>Spracovávam Google login...</h2>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

onMounted(async () => {
  const code = route.query.code
  if (code) {
    const res = await fetch('/api/google-login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ code })
    })
    const data = await res.json()
    
    if (data.success) {
      localStorage.setItem('user', JSON.stringify(data.user))
      router.push('/')
    } else {
      router.push('/login')
    }
  } else {
    router.push('/login')
  }
})
</script>
