// @ts-check
const HOST_RE = /^[a-z0-9][a-z0-9-]*\.recruitee\.com$/;

function assertUrl(url) {
  let parsed;
  try { parsed = new URL(url); } catch { throw new Error(`recruitee: invalid URL: ${url}`); }
  if (parsed.protocol !== 'https:') throw new Error(`recruitee: URL must use HTTPS`);
  if (!HOST_RE.test(parsed.hostname)) throw new Error(`recruitee: untrusted hostname "${parsed.hostname}"`);
  return url;
}

function resolveApiUrl(entry) {
  const raw = typeof entry.careers_url === 'string' ? entry.careers_url : '';
  if (!raw) return null;
  let parsed;
  try { parsed = new URL(raw); } catch { return null; }
  if (parsed.protocol !== 'https:' || !HOST_RE.test(parsed.hostname)) return null;
  return `https://${parsed.hostname}/api/offers/`;
}

export function parseRecruiteeResponse(json, companyName) {
  const offers = json?.offers;
  if (!Array.isArray(offers)) return [];
  return offers.map(j => {
    const location = j.location || [j.city, j.country, j.remote ? 'Remote' : ''].filter(Boolean).join(', ');
    let url = '';
    const rawUrl = j.careers_url || j.url || '';
    if (typeof rawUrl === 'string' && rawUrl) {
      try {
        const p = new URL(rawUrl);
        if (p.protocol === 'https:' && HOST_RE.test(p.hostname)) url = p.href;
      } catch { /* ignore */ }
    }
    return { title: j.title || '', url, location, company: companyName };
  });
}

export default {
  id: 'recruitee',

  detect(entry) {
    const url = resolveApiUrl(entry);
    return url ? { url } : null;
  },

  async fetch(entry, ctx) {
    const apiUrl = resolveApiUrl(entry);
    if (!apiUrl) throw new Error(`recruitee: cannot derive API URL for ${entry.name}`);
    assertUrl(apiUrl);
    const json = await ctx.fetchJson(apiUrl, { redirect: 'error' });
    return parseRecruiteeResponse(json, entry.name);
  },
};
