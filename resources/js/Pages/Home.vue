<script setup>
// resources/js/Pages/Home.vue — pagina principale Spotwork
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import SearchBar     from '@/Components/SearchBar.vue'
import MapView       from '@/Components/MapView.vue'
import Sidebar       from '@/Components/Sidebar.vue'
import CompanyDetail from '@/Components/CompanyDetail.vue'
import { useSpotwork } from '@/Composables/useSpotwork'
import { onMounted, onBeforeUnmount, ref } from 'vue'

defineOptions({ layout: AppLayout })

const sw = useSpotwork()
const density = ref(localStorage.getItem('sw_density') || 'cozy')
function setDensity(d) { density.value = d; localStorage.setItem('sw_density', d) }

function onSearch(patch) {
  if (patch) Object.assign(sw.query, patch)
  sw.search()
}

function onSelect(id) { sw.selectedId.value = id }

function onLoadJobs(id) { sw.loadJobs(id) }

function onEsc(e) { if (e.key === 'Escape') sw.selectedId.value = null }
onMounted(() => document.addEventListener('keydown', onEsc))
onBeforeUnmount(() => document.removeEventListener('keydown', onEsc))
</script>

<template>
  <Head title="Mappa" />
  <div class="sw-app" :data-density="density">
    <SearchBar
      :query="sw.query"
      :categories="sw.CATEGORIES"
      :radii="sw.RADII"
      :loading="sw.mode.value === 'loading'"
      :mode="sw.mode.value"
@update:query="q => Object.assign(sw.query, q)"
      @search="onSearch"
    />

    <div class="sw-stage">
      <MapView
        :center="sw.center.value"
        :radius="sw.query.radius"
        :companies="sw.mode.value === 'results' ? sw.companies.value : []"
        :selected-id="sw.selectedId.value"
        :mode="sw.mode.value"
        @select="onSelect"
      />

      <div v-if="sw.mode.value === 'idle'" class="sw-overlay-hint">
        <div class="sw-hint-card">
          <div class="sw-hint-kicker">Spotwork</div>
          <h1>Scopri quali aziende <em>assumono</em> intorno a te.</h1>
          <p>Dati pubblici da OpenStreetMap, incrociati con gli annunci attivi su Indeed. Inizia con una città qui sopra.</p>
          <div class="sw-hint-row">
            <button v-for="c in ['Milano, MI','Torino, TO','Bologna, BO','Firenze, FI']" :key="c"
                    class="sw-chip" @click="onSearch({ city: c })">{{ c.split(',')[0] }}</button>
          </div>
        </div>
      </div>

      <Sidebar
        :mode="sw.mode.value"
        :companies="sw.companies.value"
        :filtered-companies="sw.filteredCompanies.value"
        :selected-id="sw.selectedId.value"
        :saved="sw.saved.value"
        :filter="sw.filter.value"
        :sort="sw.sort.value"
        :density="density"
        :query="sw.query"
        :error="sw.error.value"
        :hiring-count="sw.hiringCount.value"
        :saved-count="sw.savedCount.value"
        :category-for="sw.categoryFor"
        @select="onSelect"
        @toggle-save="sw.toggleSave"
        @update:filter="v => sw.filter.value = v"
        @update:sort="v => sw.sort.value = v"
        @update:density="setDensity"
        @export="sw.exportCsv"
        @search="onSearch"
      />

      <CompanyDetail
        v-if="sw.selected.value"
        :company="sw.selected.value"
        :is-saved="sw.saved.value.has(sw.selected.value.id)"
        :jobs="sw.jobs[sw.selected.value.id] || []"
        :jobs-loading="sw.jobsLoading.value"
        :category="sw.categoryFor(sw.selected.value.category)"
        @close="sw.selectedId.value = null"
        @toggle-save="sw.toggleSave"
        @load-jobs="onLoadJobs"
      />
    </div>
  </div>
</template>
