<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref } from 'vue'
import { router, Head } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

const props = defineProps({
  companies: { type: Array, default: () => [] },
  jobs:      { type: Array, default: () => [] },
})

const tab = ref(props.companies.length ? 'companies' : 'jobs')

function removeCompany(savedId) {
  router.delete(`/saved/companies/${savedId}`, { preserveScroll: true })
}
function removeJob(jobId) {
  router.delete(`/saved/jobs/${jobId}`, { preserveScroll: true })
}
</script>

<template>
  <Head title="Salvati" />
  <div class="sv-page">
    <header class="sv-header">
      <a href="/" class="sv-back" aria-label="Torna alla mappa">
        <svg width="18" height="18" viewBox="0 0 16 16" fill="none">
          <path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </a>
      <h1 class="sv-title">Salvati</h1>
    </header>

    <div class="sv-tabs">
      <button :class="['sv-tab', tab === 'companies' && 'is-on']" @click="tab = 'companies'">
        Aziende <span class="sv-badge">{{ companies.length }}</span>
      </button>
      <button :class="['sv-tab', tab === 'jobs' && 'is-on']" @click="tab = 'jobs'">
        Annunci <span class="sv-badge">{{ jobs.length }}</span>
      </button>
    </div>

    <main class="sv-main">
      <!-- companies -->
      <template v-if="tab === 'companies'">
        <p v-if="!companies.length" class="sv-empty">Nessuna azienda salvata ancora.</p>
        <ul v-else class="sv-list">
          <li v-for="c in companies" :key="c.id" class="sv-item">
            <div class="sv-item-body">
              <span class="sv-item-name">{{ c.name }}</span>
              <span v-if="c.address" class="sv-item-sub">{{ c.address }}</span>
            </div>
            <a :href="`/?highlight=${c.id}`" class="sv-item-action" title="Mostra sulla mappa">
              <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                <path d="M8 14s5-4.5 5-8.5A5 5 0 1 0 3 5.5C3 9.5 8 14 8 14Z" stroke="currentColor" stroke-width="1.4"/>
                <circle cx="8" cy="5.5" r="1.8" stroke="currentColor" stroke-width="1.4"/>
              </svg>
            </a>
            <button class="sv-item-action sv-item-remove" @click="removeCompany(c.saved_id)" title="Rimuovi">
              <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                <path d="M3 4h10M6 4V3h4v1M5 4v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1V4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </li>
        </ul>
      </template>

      <!-- jobs -->
      <template v-else>
        <p v-if="!jobs.length" class="sv-empty">Nessun annuncio salvato ancora.</p>
        <ul v-else class="sv-list">
          <li v-for="j in jobs" :key="j.id" class="sv-item">
            <div class="sv-item-body">
              <span class="sv-item-name">{{ j.job_title }}</span>
              <span class="sv-item-sub">{{ j.company?.name }}</span>
            </div>
            <a :href="j.job_url" target="_blank" rel="noopener" class="sv-item-action" title="Apri annuncio">
              <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                <path d="M6 3H3a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h9a1 1 0 0 0 1-1v-3M10 2h4m0 0v4m0-4L7 9" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </a>
            <button class="sv-item-action sv-item-remove" @click="removeJob(j.id)" title="Rimuovi">
              <svg width="15" height="15" viewBox="0 0 16 16" fill="none">
                <path d="M3 4h10M6 4V3h4v1M5 4v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1V4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </li>
        </ul>
      </template>
    </main>
  </div>
</template>

<style scoped>
.sv-page {
  min-height: 100vh;
  background: var(--sw-bg);
  display: flex;
  flex-direction: column;
}

.sv-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px 24px 16px;
  border-bottom: 1px solid var(--sw-line);
  background: var(--sw-surface);
}

.sv-back {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  color: var(--sw-muted);
  text-decoration: none;
  transition: color .15s, background .15s;
}
.sv-back:hover { color: var(--sw-ink); background: var(--sw-line); }

.sv-title {
  margin: 0;
  font: 600 18px/1 "Manrope", sans-serif;
  color: var(--sw-ink);
}

.sv-tabs {
  display: flex;
  padding: 0 24px;
  border-bottom: 1px solid var(--sw-line);
  background: var(--sw-surface);
}

.sv-tab {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 12px 4px;
  margin-right: 24px;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  font: 500 13px/1 "Manrope", sans-serif;
  color: var(--sw-muted);
  cursor: pointer;
  transition: color .15s, border-color .15s;
}
.sv-tab.is-on { color: var(--sw-ink); border-bottom-color: var(--sw-ink); }
.sv-tab:hover:not(.is-on) { color: var(--sw-ink); }

.sv-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 999px;
  background: var(--sw-line);
  font: 600 11px/1 "Manrope", sans-serif;
  color: var(--sw-muted);
}
.sv-tab.is-on .sv-badge { background: var(--sw-ink); color: var(--sw-bg); }

.sv-main {
  flex: 1;
  max-width: 680px;
  width: 100%;
  margin: 0 auto;
  padding: 24px;
}

.sv-empty {
  color: var(--sw-muted);
  font-size: 14px;
  margin: 48px 0;
  text-align: center;
}

.sv-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 1px;
  background: var(--sw-line);
  border: 1px solid var(--sw-line);
  border-radius: var(--sw-radius);
  overflow: hidden;
}

.sv-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 16px;
  background: var(--sw-surface);
  transition: background .1s;
}
.sv-item:hover { background: var(--sw-bg); }

.sv-item-body {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.sv-item-name {
  font: 500 14px/1.3 "Manrope", sans-serif;
  color: var(--sw-ink);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sv-item-sub {
  font: 12px/1 "Manrope", sans-serif;
  color: var(--sw-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sv-item-action {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  color: var(--sw-muted);
  text-decoration: none;
  background: none;
  border: none;
  cursor: pointer;
  transition: color .15s, background .15s;
}
.sv-item-action:hover { color: var(--sw-ink); background: var(--sw-line); }
.sv-item-remove:hover { color: #dc2626; background: #fee2e2; }
</style>
