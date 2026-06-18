// @ts-check
import { describe, it, before, after } from 'node:test';
import assert from 'node:assert/strict';

describe('GET /health', () => {
  let server;
  before(async () => {
    const { default: app } = await import('./server.mjs');
    server = app.listen(0);
  });
  after(() => server?.close());

  it('returns 200 with ok:true', async () => {
    const port = server.address().port;
    const res = await fetch(`http://localhost:${port}/health`);
    assert.equal(res.status, 200);
    const body = await res.json();
    assert.equal(body.ok, true);
  });
});

describe('Auth middleware', () => {
  let server;
  before(async () => {
    process.env.API_KEY = 'test-secret';
    const { default: app } = await import(`./server.mjs?t=${Date.now()}`);
    server = app.listen(0);
  });
  after(() => { server?.close(); delete process.env.API_KEY; });

  it('returns 401 on /scan with wrong key', async () => {
    const port = server.address().port;
    const res = await fetch(`http://localhost:${port}/scan`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Api-Key': 'wrong' },
      body: JSON.stringify({ watched_companies: [] }),
    });
    assert.equal(res.status, 401);
  });

  it('passes through with correct key (returns 400, not 401)', async () => {
    const port = server.address().port;
    const res = await fetch(`http://localhost:${port}/scan`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Api-Key': 'test-secret' },
      body: JSON.stringify({ watched_companies: [] }),
    });
    assert.notEqual(res.status, 401);
  });
});
