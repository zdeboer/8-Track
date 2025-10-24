// Minimal Spotify PKCE client helper for browser (SPA)
// Notes:
// - You must register a Spotify app at https://developer.spotify.com/dashboard and add your redirect URI (e.g. http://localhost:5173).
// - This tries to perform the Authorization Code flow with PKCE entirely in the browser.
// - Some environments or Spotify token endpoint CORS policies may block the client-side POST to /api/token.
//   If that happens, run a tiny server to perform the token exchange (server example documented in README).

// Usage:
// import { startAuth, handleRedirect, fetchApi, getAccessToken } from './spotifyAuth'
// startAuth({ clientId, scopes, redirectUri }) -> redirects to Spotify authorize page
// call handleRedirect({ clientId, redirectUri }) on app load to finish the flow and store tokens
// fetchApi('/v1/me') to call Spotify Web API endpoints

const SPOTIFY_ACCOUNTS = 'https://accounts.spotify.com'
const TOKEN_URL = `${SPOTIFY_ACCOUNTS}/api/token`
const AUTHORIZE_URL = `${SPOTIFY_ACCOUNTS}/authorize`

function base64UrlEncode(arrayBuffer) {
  // Convert ArrayBuffer to base64url
  const bytes = new Uint8Array(arrayBuffer)
  let str = ''
  for (let i = 0; i < bytes.byteLength; i++) {
    str += String.fromCharCode(bytes[i])
  }
  return btoa(str).replace(/=/g, '').replace(/\+/g, '-').replace(/\//g, '_')
}

async function sha256(plain) {
  const encoder = new TextEncoder()
  const data = encoder.encode(plain)
  const hash = await crypto.subtle.digest('SHA-256', data)
  return base64UrlEncode(hash)
}

function genRandomString(length = 64) {
  const array = new Uint8Array(length)
  crypto.getRandomValues(array)
  // base64url encode the random bytes
  return base64UrlEncode(array)
}

export async function startAuth({ clientId, scopes = [], redirectUri }) {
  const codeVerifier = genRandomString(128)
  const codeChallenge = await sha256(codeVerifier)

  sessionStorage.setItem('spotify_code_verifier', codeVerifier)

  const params = new URLSearchParams({
    response_type: 'code',
    client_id: clientId,
    scope: scopes.join(' '),
    redirect_uri: redirectUri,
    code_challenge_method: 'S256',
    code_challenge: codeChallenge,
    show_dialog: 'true'
  })

  window.location.href = `${AUTHORIZE_URL}?${params.toString()}`
}

function saveTokens(obj) {
  // store tokens with expiry time
  const now = Date.now()
  const payload = {
    access_token: obj.access_token,
    refresh_token: obj.refresh_token,
    expires_at: obj.expires_in ? now + obj.expires_in * 1000 : null
  }
  sessionStorage.setItem('spotify_tokens', JSON.stringify(payload))
}

export function getStoredTokens() {
  const raw = sessionStorage.getItem('spotify_tokens')
  return raw ? JSON.parse(raw) : null
}

export function getAccessToken() {
  const t = getStoredTokens()
  if (!t) return null
  if (t.expires_at && Date.now() > t.expires_at) return null
  return t.access_token
}

async function exchangeCodeForToken({ clientId, code, redirectUri }) {
  // Use PKCE: send code_verifier and client_id
  const verifier = sessionStorage.getItem('spotify_code_verifier')
  if (!verifier) throw new Error('Missing code verifier')

  const body = new URLSearchParams({
    grant_type: 'authorization_code',
    code,
    redirect_uri: redirectUri,
    client_id: clientId,
    code_verifier: verifier
  })

  // DEBUG: log the outgoing exchange parameters (safe for debugging while you test)
  try {
    console.log('[spotifyAuth] Exchanging code for token. Request body:', Object.fromEntries(body))
  } catch (e) {
    // ignore
  }

  const res = await fetch(TOKEN_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body.toString()
  })

  const txt = await res.text()
  // DEBUG: log raw response text
  try {
    console.log('[spotifyAuth] Token endpoint response status:', res.status, 'body:', txt)
  } catch (e) {}

  if (!res.ok) {
    // Surface the response body for easier debugging
    throw new Error(`Token exchange failed: ${res.status} ${txt}`)
  }

  const data = JSON.parse(txt)
  saveTokens(data)
  return data
}

async function refreshToken({ clientId, refresh_token }) {
  const body = new URLSearchParams({
    grant_type: 'refresh_token',
    refresh_token,
    client_id: clientId
  })

  const res = await fetch(TOKEN_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body.toString()
  })
  if (!res.ok) {
    throw new Error('Failed to refresh token')
  }
  const data = await res.json()
  // merge new tokens with existing
  const existing = getStoredTokens() || {}
  const merged = {
    access_token: data.access_token,
    refresh_token: data.refresh_token || existing.refresh_token,
    expires_at: data.expires_in ? Date.now() + data.expires_in * 1000 : existing.expires_at
  }
  sessionStorage.setItem('spotify_tokens', JSON.stringify(merged))
  return merged
}

export async function handleRedirect({ clientId, redirectUri }) {
  // call this on app load; it will parse ?code= and exchange for token if present
  const params = new URLSearchParams(window.location.search)
  const code = params.get('code')
  const error = params.get('error')
  if (error) {
    console.error('Spotify auth error', error)
    return { error }
  }
  if (!code) return null

  try {
    const tokenData = await exchangeCodeForToken({ clientId, code, redirectUri })
    // clear code from URL
    const url = new URL(window.location.href)
    url.search = ''
    window.history.replaceState({}, document.title, url.toString())
    return tokenData
  } catch (err) {
    console.error('handleRedirect error', err)
    return { error: String(err) }
  }
}

export async function fetchApi(path, opts = {}) {
  // path can be full url or Spotify path like /v1/me
  const token = getAccessToken()
  if (!token) throw new Error('No access token')

  const url = path.startsWith('http') ? path : `https://api.spotify.com${path}`
  const res = await fetch(url, {
    ...opts,
    headers: { ...(opts.headers || {}), Authorization: `Bearer ${token}` }
  })

  if (res.status === 401) {
    // token expired; try refresh (note: refresh might fail in browser depending on CORS)
    const stored = getStoredTokens()
    if (stored && stored.refresh_token) {
      try {
        // clientId must be provided via opts._clientId for refresh to work client-side
        if (!opts._clientId) throw new Error('clientId required to refresh token')
        await refreshToken({ clientId: opts._clientId, refresh_token: stored.refresh_token })
        return fetchApi(path, opts) // retry
      } catch (err) {
        throw err
      }
    }
  }

  return res
}

// Expose for debugging
export default { startAuth, handleRedirect, fetchApi, getAccessToken, getStoredTokens }
