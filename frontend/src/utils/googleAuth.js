export function googleLogin(clientId = null) {
  // Ak nemáš Google Console - iba popup
  const params = new URLSearchParams({
    client_id: clientId || 'dummy', 
    redirect_uri: window.location.origin + '/auth/google/callback',
    response_type: 'code',
    scope: 'openid email profile',
    access_type: 'offline'
  })
  
  window.location.href = `https://accounts.google.com/o/oauth2/v2/auth?${params}`
}
