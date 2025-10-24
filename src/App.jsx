import { useEffect, useState } from 'react'
import './App.css'
import Tile from './Tile'
import { startAuth, handleRedirect, fetchApi, getAccessToken } from './spotifyAuth'

// Put your Spotify Client ID here (no angle brackets). Example: '012345...'
const CLIENT_ID = '4ecdb30ce3d64922bdd2152c99afea47'

// Use the full origin + pathname as the redirect URI so it matches GitHub Pages project paths
function computeRedirectUri() {
  const origin = window.location.origin
  const path = window.location.pathname
  // ensure trailing slash
  return origin + (path.endsWith('/') ? path : path + '/')
}

const REDIRECT_URI = computeRedirectUri()
console.log('Spotify REDIRECT_URI =', REDIRECT_URI)
const SCOPES = ['user-read-email', 'user-read-private']

function App() {
  const [profile, setProfile] = useState(null)
  const [error, setError] = useState(null)

  useEffect(() => {
    // finish auth if we're being redirected back
    handleRedirect({ clientId: CLIENT_ID, redirectUri: REDIRECT_URI }).then((res) => {
      if (res && res.error) setError(res.error)
      // if tokens exist, fetch profile
      if (getAccessToken()) {
        fetchApi('/v1/me').then(r => r.json()).then(setProfile).catch(e => setError(String(e)))
      }
    }).catch(e => setError(String(e)))
  }, [])

  function connect() {
    startAuth({ clientId: CLIENT_ID, scopes: SCOPES, redirectUri: REDIRECT_URI })
  }

  return (
    <div style={{ padding: 20 }}>
      <h1>8-Track</h1>
      {profile ? (
        <div>
          <p>Connected as {profile.display_name || profile.id}</p>
          <img src={profile.images?.[0]?.url} alt="profile" style={{ width: 48, height: 48, borderRadius: 24 }} />
        </div>
      ) : (
        <div>
          <button onClick={connect}>Connect to Spotify</button>
          {error && <p style={{ color: 'salmon' }}>{error}</p>}
        </div>
      )}

      <hr />
      <Tile size={300} />
    </div>
  )
}

export default App
