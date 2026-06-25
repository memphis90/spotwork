// @ts-check
const ALLOWED_HOSTS = new Set([
  'boards-api.greenhouse.io',
  'boards.greenhouse.io',
  'job-boards.greenhouse.io',
  'job-boards.eu.greenhouse.io',
]);

function assertUrl(url) {
  let parsed;
  try { parsed = new URL(url); } catch { throw new Error(`greenhouse: invalid URL: ${url}`); }
  if (parsed.protocol !== 'https:') throw new Error(`greenhouse: URL must use HTTPS`);
  if (!ALLOWED_HOSTS.has(parsed.hostname)) throw new Error(`greenhouse: untrusted hostname "${parsed.hostname}"`);
  return url;
}

function resolveApiUrl(entry) {
  if (entry.api) { assertUrl(entry.api); return entry.api; }
  const url = entry.careers_url || '';
  const match = url.match(/job-boards(?:\.eu)?\.greenhouse\.io\/([^/?#]+)/);
  if (match) return `https://boards-api.greenhouse.io/v1/boards/${match[1]}/jobs`;
  return null;
}

function toEpochMs(value) {
  if (!value) return undefined;
  const parsed = Date.parse(value);
  return Number.isNaN(parsed) ? undefined : parsed;
}

export default {
  id: 'greenhouse',

  detect(entry) {
    try { const url = resolveApiUrl(entry); return url ? { url } : null; } catch { return null; }
  },

  async fetch(entry, ctx) {
    const apiUrl = resolveApiUrl(entry);
    if (!apiUrl) throw new Error(`greenhouse: cannot derive API URL for ${entry.name}`);
    assertUrl(apiUrl);
    const json = await ctx.fetchJson(apiUrl, { redirect: 'error' });
    const jobs = Array.isArray(json?.jobs) ? json.jobs : [];
    return jobs.filter(j => j.absolute_url).map(j => ({
      title: j.title || '',
      url: j.absolute_url,
      company: entry.name,
      location: j.location?.name || '',
      postedAt: toEpochMs(j.first_published),
    }));
  },
};
