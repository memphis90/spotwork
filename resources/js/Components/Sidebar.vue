<script setup>
// resources/js/Components/Sidebar.vue
import CompanyCard from './CompanyCard.vue'

const props = defineProps({
  mode:               { type: String, required: true },
  companies:          { type: Array, required: true },
  filteredCompanies:  { type: Array, required: true },
  selectedId:         { type: [String, Number, null], default: null },
  saved:              { type: Set, required: true },
  filter:             { type: String, required: true },
  sort:               { type: String, required: true },
  density:            { type: String, default: 'cozy' },
  query:              { type: Object, required: true },
  error:              { type: Object, default: null },
  hiringCount:        { type: Number, default: 0 },
  savedCount:         { type: Number, default: 0 },
  categoryFor:        { type: Function, required: true },
})
const emit = defineEmits([
  'select','toggleSave',
  'update:filter','update:sort','update:density',
  'export','search',
])
</script>

<template>
  <aside class="sw-side">
    <div class="sw-side-top">
      <div class="sw-side-tabs">
        <button :class="['sw-tab', filter === 'all' && 'is-on']" @click="$emit('update:filter','all')">
          Tutte <em>{{ companies.length }}</em>
        </button>
        <button :class="['sw-tab', filter === 'hiring' && 'is-on']" @click="$emit('update:filter','hiring')">
          <span class="sw-tab-dot sw-tab-dot-green" />
          Assumono <em>{{ hiringCount }}</em>
        </button>
        <button :class="['sw-tab', filter === 'saved' && 'is-on']" @click="$emit('update:filter','saved')">
          <span class="sw-tab-dot sw-tab-dot-ink" />
          Salvate <em>{{ savedCount }}</em>
        </button>
      </div>
      <div class="sw-side-tools">
        <div class="sw-sort">
          <span>Ordina</span>
          <select :value="sort" @change="$emit('update:sort', $event.target.value)">
            <option value="hiring">Più assunzioni</option>
            <option value="distance">Distanza</option>
            <option value="name">Nome</option>
            <option value="size">Dimensione</option>
          </select>
        </div>
        <div class="sw-density" role="group" aria-label="Densità">
          <button :class="density === 'compact' && 'is-on'" @click="$emit('update:density','compact')" title="Compatta">
            <svg width="12" height="12" viewBox="0 0 12 12"><rect x="1" y="2" width="10" height="1.2" fill="currentColor"/><rect x="1" y="5.4" width="10" height="1.2" fill="currentColor"/><rect x="1" y="8.8" width="10" height="1.2" fill="currentColor"/></svg>
          </button>
          <button :class="density === 'cozy' && 'is-on'" @click="$emit('update:density','cozy')" title="Spaziosa">
            <svg width="12" height="12" viewBox="0 0 12 12"><rect x="1" y="1.5" width="10" height="2.4" fill="currentColor"/><rect x="1" y="8.1" width="10" height="2.4" fill="currentColor"/></svg>
          </button>
        </div>
        <button class="sw-export" @click="$emit('export')" title="Esporta CSV">
          <svg width="13" height="13" viewBox="0 0 16 16" fill="none">
            <path d="M8 2v8m0 0 3-3m-3 3-3-3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            <path d="M3 11v2a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-2" stroke="currentColor" stroke-width="1.4"/>
          </svg>
        </button>
      </div>
    </div>

    <div class="sw-side-list">
      <!-- loading -->
      <div v-if="mode === 'loading'" class="sw-skel-wrap">
        <div v-for="i in 6" :key="i" class="sw-skel">
          <div class="sw-skel-c" />
          <div class="sw-skel-body">
            <div class="sw-skel-l sw-skel-l-1" />
            <div class="sw-skel-l sw-skel-l-2" />
            <div class="sw-skel-l sw-skel-l-3" />
          </div>
        </div>
      </div>

      <!-- error -->
      <div v-else-if="mode === 'error'" class="sw-state">
        <div class="sw-state-ic">⚠</div>
        <h3>Città non trovata</h3>
        <p>Non riusciamo a localizzare «<b>{{ error?.city || query.city }}</b>». Controlla l'ortografia o prova con un comune limitrofo.</p>
        <div class="sw-state-cta">
          <button v-for="s in ['Milano, MI','Roma, RM','Bologna, BO']" :key="s" class="sw-chip"
                  @click="$emit('search', { city: s })">{{ s }}</button>
        </div>
      </div>

      <!-- idle -->
      <div v-else-if="mode === 'idle'" class="sw-state sw-state-idle">
        <h3>Inizia da una città</h3>
        <p>Inserisci una città, scegli un raggio e una categoria. Ti mostriamo le aziende intorno a te e quelle che stanno assumendo adesso su Indeed.</p>
        <div class="sw-legend">
          <div><span class="sw-leg sw-leg-green" /> Assume — annunci attivi</div>
          <div><span class="sw-leg sw-leg-amber" /> Aperta a candidature spontanee</div>
        </div>
      </div>

      <!-- empty results -->
      <div v-else-if="filteredCompanies.length === 0" class="sw-state">
        <h3>Nessun risultato</h3>
        <p>Nessuna azienda corrisponde a questo filtro. Prova ad allargare il raggio o cambiare categoria.</p>
      </div>

      <!-- list -->
      <div v-else class="sw-list">
        <CompanyCard v-for="c in filteredCompanies" :key="c.id"
                     :c="c"
                     :density="density"
                     :active="c.id === selectedId"
                     :saved="saved.has(c.id)"
                     :category="categoryFor(c.category)"
                     @select="id => $emit('select', id)"
                     @toggleSave="id => $emit('toggleSave', id)" />
      </div>
    </div>

    <div v-if="mode === 'results' && filteredCompanies.length > 0" class="sw-side-foot">
      <span>Dati: OpenStreetMap · Indeed</span>
      <span>·</span>
      <span>Aggiornato adesso</span>
    </div>
  </aside>
</template>
