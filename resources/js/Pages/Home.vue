<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import SearchBar          from '@/Components/SearchBar.vue'
import MapView            from '@/Components/MapView.vue'
import Sidebar            from '@/Components/Sidebar.vue'
import CompanyDetail      from '@/Components/CompanyDetail.vue'
import MobileTopBar       from '@/Components/MobileTopBar.vue'
import MobileBottomSearch from '@/Components/MobileBottomSearch.vue'
import MobileSearchModal  from '@/Components/MobileSearchModal.vue'
import BottomSheet        from '@/Components/BottomSheet.vue'
import MobileFilterChips  from '@/Components/MobileFilterChips.vue'
import MobileSplash       from '@/Components/MobileSplash.vue'
import { useSpotwork } from '@/Composables/useSpotwork'
import { useIsMobile }  from '@/Composables/useIsMobile'
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'

defineOptions({ layout: AppLayout })

const sw         = useSpotwork()
const isMobile   = useIsMobile()
const density    = ref(localStorage.getItem('sw_density') || 'cozy')
const searchOpen = ref(false)
const sheetSnap  = ref('peek')
const showSplash = ref(true)

function setDensity(d) { density.value = d; localStorage.setItem('sw_density', d) }

function onSearch(patch) {
  if (patch) Object.assign(sw.query, patch)
  sw.search()
}

// When results arrive on mobile, pop sheet to mid
watch(sw.mode, v => {
  if (v === 'results' && isMobile.value) sheetSnap.value = 'mid'
})

function onSelect(id) { sw.selectedId.value = id }
function onEsc(e) { if (e.key === 'Escape') sw.selectedId.value = null }
onMounted(() => document.addEventListener('keydown', onEsc))
onBeforeUnmount(() => document.removeEventListener('keydown', onEsc))
</script>

<template>
  <Head title="Mappa" />
  <div class="sw-app" :data-density="density">

    <!-- Desktop search bar -->
    <SearchBar
      v-if="!isMobile"
      :query="sw.query"
      :categories="sw.CATEGORIES"
      :radii="sw.RADII"
      :loading="sw.mode.value === 'loading'"
      :mode="sw.mode.value"
      @update:query="q => Object.assign(sw.query, q)"
      @search="onSearch"
    />

    <!-- Mobile compact top bar (results / loading state) -->
    <MobileTopBar
      v-if="isMobile"
      :query="sw.query"
      :mode="sw.mode.value"
      :loading="sw.mode.value === 'loading'"
      :categories="sw.CATEGORIES"
      :radii="sw.RADII"
      @open-search="searchOpen = true"
    />

    <div class="sw-stage">
      <MapView
        :center="sw.center.value"
        :radius="sw.query.radius"
        :geo-type="sw.geoType.value"
        :companies="sw.mode.value === 'results' ? sw.filteredCompanies.value : []"
        :selected-id="sw.selectedId.value"
        :mode="sw.mode.value"
        @select="onSelect"
      />

      <!-- Loading overlay -->
      <Teleport to="body">
        <div v-if="sw.mode.value === 'loading'" class="sw-loading-overlay">
          <video src="@img/spotwork.mp4" class="sw-loading-logo" autoplay muted playsinline loop />
        </div>
      </Teleport>

      <!-- Desktop: idle hint card -->
      <div v-if="!isMobile && sw.mode.value === 'idle'" class="sw-overlay-hint">
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

      <!-- Mobile: idle hint card (above dock) -->
      <div v-if="isMobile && sw.mode.value === 'idle'" class="sw-moverlay-idle">
        <div class="sw-moverlay-card">
          <div class="sw-hint-kicker">Spotwork</div>
          <h1>Scopri quali aziende <em>assumono</em> intorno a te.</h1>
          <p>Dati da OpenStreetMap incrociati con Indeed. Cerca una città per iniziare.</p>
          <div class="sw-hint-row">
            <button v-for="c in ['Milano','Torino','Bologna','Firenze']" :key="c"
                    class="sw-chip" @click="searchOpen = true">{{ c }}</button>
          </div>
        </div>
      </div>

      <!-- Desktop: sidebar -->
      <Sidebar
        v-if="!isMobile"
        :mode="sw.mode.value"
        :companies="sw.companies.value"
        :filtered-companies="sw.filteredCompanies.value"
        :selected-id="sw.selectedId.value"
        :saved="sw.saved.value"
        :ratings="sw.ratings.value"
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

      <!-- Mobile: floating filter chips -->
      <MobileFilterChips
        v-if="isMobile && sw.mode.value === 'results'"
        :companies="sw.companies.value"
        :saved="sw.saved.value"
        :filter="sw.filter.value"
        :sheet-snap="sheetSnap"
        @update:filter="v => sw.filter.value = v"
      />

      <!-- Mobile: bottom sheet with company list -->
      <BottomSheet
        v-if="isMobile && sw.mode.value === 'results'"
        :snap="sheetSnap"
        @update:snap="sheetSnap = $event"
      >
        <template #header>
          <div class="sw-sheet-header">
            <span class="sw-sheet-count">
              <b>{{ sw.filteredCompanies.value.length }}</b>
              <span class="sw-sheet-count-sub"> aziende</span>
            </span>
            <button class="sw-sheet-sort">
              <svg width="12" height="12" viewBox="0 0 16 16" fill="none">
                <path d="M2 4h12M4 8h8M6 12h4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
              </svg>
              Ordina
            </button>
          </div>
        </template>

        <Sidebar
          :mode="sw.mode.value"
          :companies="sw.companies.value"
          :filtered-companies="sw.filteredCompanies.value"
          :selected-id="sw.selectedId.value"
          :saved="sw.saved.value"
          :ratings="sw.ratings.value"
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
      </BottomSheet>

      <!-- Mobile: idle bottom dock -->
      <MobileBottomSearch
        v-if="isMobile && sw.mode.value === 'idle'"
        @open-search="searchOpen = true"
      />

      <CompanyDetail
        v-if="sw.selected.value"
        :company="sw.selected.value"
        :is-saved="sw.saved.value.has(sw.selected.value.id)"
        :rating="sw.ratings.value[sw.selected.value.id] || 0"
        :category="sw.categoryFor(sw.selected.value.category)"
        :city="sw.query.city"
        @close="sw.selectedId.value = null"
        @toggle-save="sw.toggleSave"
        @rate="sw.rateCompany"
      />
    </div>

    <!-- Splash screen (first load, all viewports) -->
    <MobileSplash
      v-if="showSplash"
      @done="showSplash = false"
    />

    <!-- Mobile: full-screen search modal -->
    <MobileSearchModal
      v-if="isMobile"
      :open="searchOpen"
      :query="sw.query"
      :categories="sw.CATEGORIES"
      :radii="sw.RADII"
      @update:query="q => Object.assign(sw.query, q)"
      @search="onSearch"
      @close="searchOpen = false"
    />
  </div>
</template>
