// @ts-check
import express from 'express';
import { existsSync, readdirSync } from 'fs';
import { pathToFileURL, fileURLToPath } from 'url';
import path from 'path';
import { makeHttpCtx } from './providers/_http.mjs';

const app = express();
app.use(express.json({ limit: '1mb' }));

const API_KEY = process.env.API_KEY;

function requireApiKey(req, res, next) {
  if (!API_KEY) return next();
  const key = req.headers['x-api-key'];
  if (key !== API_KEY) return res.status(401).json({ error: 'Unauthorized' });
  next();
}

const PROVIDERS_DIR = path.resolve(path.dirname(fileURLToPath(import.meta.url)), 'providers');
const SCAN_CONCURRENCY = 5;

async function loadProviders() {
  const map = new Map();
  if (!existsSync(PROVIDERS_DIR)) return map;
  const files = readdirSync(PROVIDERS_DIR)
    .filter(f => f.endsWith('.mjs') && !f.startsWith('_'))
    .sort();
  for (const file of files) {
    try {
      const mod = await import(pathToFileURL(path.join(PROVIDERS_DIR, file)).href);
      const p = mod.default;
      if (p?.id && typeof p.fetch === 'function') map.set(p.id, p);
    } catch (err) {
      console.error(`provider ${file}: ${err.message}`);
    }
  }
  return map;
}

const providersReady = loadProviders();

app.get('/health', (_req, res) => res.json({ ok: true }));

app.post('/scan', requireApiKey, async (req, res) => {
  const { watched_companies } = req.body ?? {};
  if (!Array.isArray(watched_companies) || watched_companies.length === 0) {
    return res.status(400).json({ error: 'watched_companies must be a non-empty array' });
  }

  const providers = await providersReady;
  const ctx = makeHttpCtx();
  const enabled = watched_companies.filter(c => c.enabled !== false);
  const allJobs = [];

  for (let i = 0; i < enabled.length; i += SCAN_CONCURRENCY) {
    const batch = enabled.slice(i, i + SCAN_CONCURRENCY);
    const results = await Promise.allSettled(
      batch.map(async (entry) => {
        let provider = entry.provider ? providers.get(entry.provider) : null;
        if (!provider) {
          for (const p of providers.values()) {
            if (p.detect?.(entry)) { provider = p; break; }
          }
        }
        if (!provider) return [];
        return provider.fetch(entry, ctx);
      })
    );
    for (const r of results) {
      if (r.status === 'fulfilled') allJobs.push(...r.value);
      else console.error('scan entry failed:', r.reason?.message);
    }
  }

  res.json({ jobs: allJobs });
});

const PORT = process.env.PORT ?? 3001;
if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  app.listen(PORT, () => console.log(`career-ops microservice on :${PORT}`));
}

export default app;
