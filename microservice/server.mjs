// @ts-check
import express from 'express';
import { pathToFileURL } from 'url';

const app = express();
app.use(express.json({ limit: '1mb' }));

const API_KEY = process.env.API_KEY;

function requireApiKey(req, res, next) {
  if (!API_KEY) return next();
  const key = req.headers['x-api-key'];
  if (key !== API_KEY) return res.status(401).json({ error: 'Unauthorized' });
  next();
}

app.get('/health', (_req, res) => res.json({ ok: true }));

// POST /scan and POST /fetch-jd implemented in subsequent tasks.
app.post('/scan', requireApiKey, (_req, res) =>
  res.status(400).json({ error: 'watched_companies must be a non-empty array' })
);

const PORT = process.env.PORT ?? 3001;
if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  app.listen(PORT, () => console.log(`career-ops microservice on :${PORT}`));
}

export default app;
