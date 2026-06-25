// @ts-check
const ALLOWED_HOSTS = new Set(['apply.workable.com']);

function assertUrl(url) {
  let parsed;
  try { parsed = new URL(url); } catch { throw new Error(`workable: invalid URL: ${url}`); }
  if (parsed.protocol !== 'https:') throw new Error(`workable: URL must use HTTPS`);
  if (!ALLOWED_HOSTS.has(parsed.hostname)) throw new Error(`workable: untrusted hostname "${parsed.hostname}"`);
  return url;
}

function resolveFeedUrl(entry) {
  const raw = typeof entry.careers_url === 'string' ? entry.careers_url : '';
  if (!raw) return null;
  let parsed;
  try { parsed = new URL(raw); } catch { return null; }
  if (parsed.protocol !== 'https:' || parsed.hostname !== 'apply.workable.com') return null;
  const slug = parsed.pathname.split('/').filter(Boolean)[0];
  if (!slug) return null;
  return `https://apply.workable.com/${slug}/jobs.md`;
}

export function parseWorkableMarkdown(text, companyName) {
  if (typeof text !== 'string') return [];
  const jobs = [];
  for (const line of text.split('\n')) {
    if (!line.startsWith('|') || !line.includes('[View]')) continue;
    const cols = line.split('|').map(c => c.trim());
    if (cols.length < 8) continue;
    const title = cols[1];
    if (!title || title === 'Title') continue;
    const location = cols[3] || '';
    const urlMatch = line.match(/\[View\]\(([^)]+)\)/);
    let url = urlMatch ? urlMatch[1] : '';
    if (url.endsWith('.md')) url = url.slice(0, -3);
    if (!url) continue;
    try {
      const p = new URL(url);
      if (p.protocol !== 'https:' || p.hostname !== 'apply.workable.com') continue;
      url = p.href;
    } catch { continue; }
    jobs.push({ title, url, location, company: companyName });
  }
  return jobs;
}

export default {
  id: 'workable',

  detect(entry) {
    const url = resolveFeedUrl(entry);
    return url ? { url } : null;
  },

  async fetch(entry, ctx) {
    const feedUrl = resolveFeedUrl(entry);
    if (!feedUrl) throw new Error(`workable: cannot derive feed URL for ${entry.name}`);
    assertUrl(feedUrl);
    const text = await ctx.fetchText(feedUrl, { redirect: 'error' });
    return parseWorkableMarkdown(text, entry.name);
  },
};
