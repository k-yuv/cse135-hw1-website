const express = require('express');
const fs = require('fs');
const path = require('path');
const { Pool } = require('pg');

const app = express();
const PORT = 3005;
const LOG_FILE = path.join(__dirname, 'analytics.jsonl');

// Database connection
const db = new Pool({
  host: 'localhost',
  database: 'collector_cse135',
  user: 'postgres',
  password: 'Sanrio135Cse',
  port: 5432
});

// CORS headers
app.use((req, res, next) => {
  res.header('Access-Control-Allow-Origin', '*');
  res.header('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.header('Access-Control-Allow-Headers', 'Content-Type');
  if (req.method === 'OPTIONS') {
    return res.sendStatus(204);
  }
  next();
});

app.use(express.json());

app.post('/collect', async (req, res) => {
  const payload = req.body;

  if (!payload || !payload.url || !payload.type) {
    return res.status(400).json({ error: 'Missing required fields: url, type' });
  }

  payload.serverTimestamp = new Date().toISOString();
  payload.ip = req.ip;

  // Keep writing to file as backup
  const line = JSON.stringify(payload) + '\n';
  fs.appendFile(LOG_FILE, line, (err) => {
    if (err) console.error('File write error:', err);
  });

  const session_id = payload.session;
  const url = payload.url;
  const server_timestamp = new Date();
  const client_ip = req.ip;
  const type = payload.type;

  try {
    if (type === 'pageview') {
      await db.query(`
        INSERT INTO pageviews
          (url, type, user_agent, viewport_width, viewport_height, referrer,
           client_timestamp, server_timestamp, client_ip, session_id, payload)
        VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11)
      `, [
        url,
        type,
        payload.technographics?.userAgent,
        payload.technographics?.viewportWidth,
        payload.technographics?.viewportHeight,
        payload.referrer,
        payload.timestamp ? Date.parse(payload.timestamp) : null,
        server_timestamp,
        client_ip,
        session_id,
        JSON.stringify(payload)
      ]);

    } else if (type === 'event') {
      await db.query(`
        INSERT INTO events
          (session_id, event_name, event_category, event_data, url, server_timestamp)
        VALUES ($1,$2,$3,$4,$5,$6)
      `, [
        session_id,
        payload.event,
        payload.data?.category || null,
        JSON.stringify(payload.data || {}),
        url,
        server_timestamp
      ]);

    } else if (type === 'error') {
      await db.query(`
        INSERT INTO errors
          (session_id, error_message, error_source, error_line, error_column,
           stack_trace, url, user_agent, server_timestamp)
        VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9)
      `, [
        session_id,
        payload.error?.message,
        payload.error?.source,
        payload.error?.line,
        payload.error?.column,
        payload.error?.stack,
        url,
        payload.technographics?.userAgent || null,
        server_timestamp
      ]);

    } else if (type === 'page_exit') {
      await db.query(`
        INSERT INTO performance
          (session_id, url, ttfb, dom_content_loaded, load_time,
           lcp, cls, inp, server_timestamp)
        VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9)
      `, [
        session_id,
        url,
        payload.timing?.ttfb,
        payload.timing?.domComplete,
        payload.timing?.loadEvent,
        payload.vitals?.lcp,
        payload.vitals?.cls,
        payload.vitals?.inp,
        server_timestamp
      ]);
    }

    res.sendStatus(204);

  } catch (err) {
    console.error('DB insert failed:', err);
    res.sendStatus(500);
  }
});

app.use(express.static(__dirname));

app.listen(PORT, () => {
  console.log(`Analytics endpoint listening on http://localhost:${PORT}`);
  console.log(`Data file: ${LOG_FILE}`);
});