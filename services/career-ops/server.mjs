import express from 'express';
import { chromium } from 'playwright';
import { makeHttpCtx } from './providers/_http.mjs';
import greenhouse from './providers/greenhouse.mjs';
import ashby from './providers/ashby.mjs';
import lever from './providers/lever.mjs';
import workday from './providers/workday.mjs';
import workable from './providers/workable.mjs';
import smartrecruiters from './providers/smartrecruiters.mjs';
import recruitee from './providers/recruitee.mjs';
import remotive from './providers/remotive.mjs';
import remoteok from './providers/remoteok.mjs';

const PORT = process.env.PORT || 3001;
const API_KEY = process.env.CAREER_OPS_API_KEY || null;

// Ordered: explicit API providers before broad aggregators.
const PROVIDERS = [greenhouse, ashby, lever, workday, workable, smartrecruiters, recruitee, remotive, remoteok];

function resolveProvider(entry) {
  if (entry.provider) {
    return PROVIDERS.find(p => p.id === entry.provider) ?? null;
  }
  for (const p of PROVIDERS) {
    if (typeof p.detect === 'function' && p.detect(entry)) return p;
  }
  return null;
}

const app = express();
app.set('trust proxy', 1);
app.use(express.json({ limit: '1mb' }));

if (API_KEY) {
  app.use((req, res, next) => {
    if (req.headers['x-api-key'] !== API_KEY) {
      return res.status(401).json({ error: 'unauthorized' });
    }
    next();
  });
}

app.post('/scan', async (req, res) => {
  const { watched_companies } = req.body;
  if (!Array.isArray(watched_companies)) {
    return res.status(400).json({ error: 'watched_companies must be an array' });
  }

  const ctx = makeHttpCtx();
  const jobs = [];
  const errors = [];

  const targets = watched_companies.filter(c =>
    c && typeof c === 'object' && c.enabled !== false && typeof c.careers_url === 'string' && c.careers_url
  );

  await Promise.allSettled(
    targets.map(async (company) => {
      const provider = resolveProvider(company);
      if (!provider) return;
      try {
        const fetched = await provider.fetch(company, ctx);
        jobs.push(...fetched);
      } catch (err) {
        errors.push({ company: company.name, error: err.message });
      }
    })
  );

  res.json({ jobs, errors });
});

app.post('/fetch-jd', async (req, res) => {
  const { url } = req.body;
  if (!url || typeof url !== 'string') {
    return res.status(400).json({ error: 'url is required' });
  }

  let parsed;
  try { parsed = new URL(url); } catch {
    return res.status(400).json({ error: 'invalid url' });
  }
  if (!['http:', 'https:'].includes(parsed.protocol)) {
    return res.status(400).json({ error: 'url must use http or https' });
  }

  let browser;
  try {
    browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();
    await page.setExtraHTTPHeaders({ 'user-agent': 'Mozilla/5.0 (compatible; career-ops-service/1.0)' });
    await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 15_000 });
    const description = await page.evaluate(() => document.body.innerText);
    res.json({ description: description.trim() });
  } catch (err) {
    res.status(500).json({ error: err.message });
  } finally {
    await browser?.close();
  }
});

app.get('/health', (_req, res) => res.json({ ok: true }));

app.listen(PORT, () => {
  console.log(`career-ops service on :${PORT}`);
});
