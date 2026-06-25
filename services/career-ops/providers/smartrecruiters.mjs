// @ts-check
const ALLOWED_API_HOSTS = new Set(['api.smartrecruiters.com']);
const CAREERS_HOSTS = new Set(['careers.smartrecruiters.com', 'jobs.smartrecruiters.com']);
const PAGE_SIZE = 100;
const MAX_PAGES = 50;

function assertUrl(url) {
  let parsed;
  try { parsed = new URL(url); } catch { throw new Error(`smartrecruiters: invalid URL: ${url}`); }
  if (parsed.protocol !== 'https:') throw new Error(`smartrecruiters: URL must use HTTPS`);
  if (!ALLOWED_API_HOSTS.has(parsed.hostname)) throw new Error(`smartrecruiters: untrusted hostname "${parsed.hostname}"`);
  return url;
}

function resolveSlug(entry) {
  const raw = typeof entry.careers_url === 'string' ? entry.careers_url : '';
  if (!raw) return null;
  let parsed;
  try { parsed = new URL(raw); } catch { return null; }
  if (parsed.protocol !== 'https:' || !CAREERS_HOSTS.has(parsed.hostname)) return null;
  return parsed.pathname.split('/').filter(Boolean)[0] || null;
}

function buildPostingsUrl(slug, offset = 0) {
  return `https://api.smartrecruiters.com/v1/companies/${slug}/postings?limit=${PAGE_SIZE}&offset=${offset}&status=PUBLIC`;
}

export function parseSmartRecruitersResponse(json, companyName) {
  const items = json?.content;
  if (!Array.isArray(items)) return [];
  return items.map(j => {
    const loc = j.location || {};
    const fullLocation = loc.fullLocation || [loc.city, loc.region, loc.country].filter(Boolean).join(', ');
    const remote = loc.remote ? 'Remote' : '';
    const location = [fullLocation, remote].filter(Boolean).join(', ');
    const slugified = (j.name || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    let url = '';
    if (typeof j.ref === 'string') {
      try {
        const p = new URL(j.ref);
        if (p.protocol === 'https:' && p.hostname === 'api.smartrecruiters.com' && p.pathname.startsWith('/v1/companies/')) {
          url = `https://jobs.smartrecruiters.com/${p.pathname.slice('/v1/companies/'.length)}`;
        }
      } catch { /* ignore */ }
    }
    if (!url && j.id) {
      const cSlug = (companyName || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
      if (cSlug) url = `https://jobs.smartrecruiters.com/${cSlug}/${j.id}-${slugified}`;
    }
    return { title: j.name || '', url, location, company: companyName };
  });
}

export default {
  id: 'smartrecruiters',

  detect(entry) {
    const slug = resolveSlug(entry);
    return slug ? { url: buildPostingsUrl(slug) } : null;
  },

  async fetch(entry, ctx) {
    const slug = resolveSlug(entry);
    if (!slug) throw new Error(`smartrecruiters: cannot derive API URL for ${entry.name}`);
    const all = [];
    for (let page = 0; page < MAX_PAGES; page++) {
      const url = buildPostingsUrl(slug, page * PAGE_SIZE);
      assertUrl(url);
      const json = await ctx.fetchJson(url, { redirect: 'error' });
      const parsed = parseSmartRecruitersResponse(json, entry.name);
      if (parsed.length === 0) break;
      all.push(...parsed);
      if (parsed.length < PAGE_SIZE) break;
    }
    return all;
  },
};
