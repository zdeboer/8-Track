const express = require('express');
const axios = require('axios');
const path = require('path');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;

app.use(express.static(path.join(__dirname, '..')));

app.get('/token', async (req, res) => {
  const clientId = process.env.CLIENT_ID;
  const clientSecret = process.env.CLIENT_SECRET;
  if (!clientId || !clientSecret) return res.status(500).json({ error: 'Missing CLIENT_ID / CLIENT_SECRET' });

  const b64 = Buffer.from(`${clientId}:${clientSecret}`).toString('base64');
  try {
    const r = await axios.post('https://accounts.spotify.com/api/token', 'grant_type=client_credentials', {
      headers: {
        Authorization: `Basic ${b64}`,
        'Content-Type': 'application/x-www-form-urlencoded'
      }
    });
    res.json(r.data); // { access_token, token_type, expires_in }
  } catch (err) {
    res.status(500).json({ error: err.response?.data || err.message });
  }
});

app.listen(PORT, () => console.log(`Server listening on http://localhost:${PORT}`));