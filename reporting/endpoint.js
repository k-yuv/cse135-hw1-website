
const express = require('express');
const { Pool } = require('pg');

const app = express();
const PORT = 3006;

// Database connection
const db = new Pool({
    host: 'localhost',
    database: 'collector_cse135',
    user: 'postgres',
    password: 'Sanrio135Cse',
    port: 5432
  });


app.use(express.json());

// CORS (for frontend dashboard later)
app.use((req, res, next) => {
  res.header('Access-Control-Allow-Origin', '*');
  res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
  res.header('Access-Control-Allow-Headers', 'Content-Type');
  if (req.method === 'OPTIONS') return res.sendStatus(204);
  next();
});

///// Setting up rest routes
// GET all pageviews
app.get('/api/pageviews', async (req, res) => {
    try {
      const result = await db.query('SELECT * FROM pageviews ORDER BY id DESC');
      res.json(result.rows);
    } catch (err) {
      console.error(err);
      res.sendStatus(500);
    }
  });
  
  // GET pageview by ID
  app.get('/api/pageviews/:id', async (req, res) => {
    try {
      const result = await db.query(
        'SELECT * FROM pageviews WHERE id = $1',
        [req.params.id]
      );
  
      if (result.rows.length === 0) {
        return res.sendStatus(404);
      }
  
      res.json(result.rows[0]);
    } catch (err) {
      console.error(err);
      res.sendStatus(500);
    }
  });
  
  // POST new pageview (NO ID IN URL)
  app.post('/api/pageviews', async (req, res) => {
    try {
      const { url, session_id } = req.body;
  
      const result = await db.query(
        'INSERT INTO pageviews (url, session_id) VALUES ($1, $2) RETURNING *',
        [url, session_id]
      );
  
      res.status(201).json(result.rows[0]);
    } catch (err) {
      console.error(err);
      res.sendStatus(500);
    }
  });
  
  // PUT update pageview (ID REQUIRED)
  app.put('/api/pageviews/:id', async (req, res) => {
    try {
      const { url } = req.body;
  
      const result = await db.query(
        'UPDATE pageviews SET url = $1 WHERE id = $2 RETURNING *',
        [url, req.params.id]
      );
  
      if (result.rows.length === 0) {
        return res.sendStatus(404);
      }
  
      res.json(result.rows[0]);
    } catch (err) {
      console.error(err);
      res.sendStatus(500);
    }
  });
  
  // DELETE pageview (ID REQUIRED)
  app.delete('/api/pageviews/:id', async (req, res) => {
    try {
      await db.query(
        'DELETE FROM pageviews WHERE id = $1',
        [req.params.id]
      );
      res.sendStatus(204);
    } catch (err) {
      console.error(err);
      res.sendStatus(500);
    }
  });

  app.listen(PORT, () => {
    console.log(`Reporting API running on http://localhost:${PORT}`);
  });