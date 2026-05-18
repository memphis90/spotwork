// resources/js/Composables/useSpotwork.js
// Stato condiviso per la ricerca Spotwork.
// Chiama il backend Laravel: GET /api/search e GET /api/jobs.
// Endpoints dichiarati in routes/web.php — adatta se hai prefissi diversi.

import { ref, computed, reactive } from 'vue'
import axios from 'axios'

const CATEGORIES = [
  { id: 'all',      label: 'Tutte',        icon: '○' },
  { id: 'it',       label: 'Informatica',  icon: '▢' },
  { id: 'industry', label: 'Industria',    icon: '▣' },
  { id: 'retail',   label: 'Commercio',    icon: '▤' },
  { id: 'health',   label: 'Sanità',       icon: '✚' },
  { id: 'food',     label: 'Ristorazione', icon: '◐' },
  { id: 'finance',  label: 'Finanza',      icon: '◇' },
]
const RADII = [
  { value: 2000,  label: '2 km' },
  { value: 5000,  label: '5 km' },
  { value: 10000, label: '10 km' },
  { value: 25000, label: '25 km' },
  { value: 50000, label: '50 km' },
]

export function useSpotwork() {
  const query  = reactive({ city: 'Milano, MI', radius: 5000, category: 'all' })
  const mode   = ref('idle')           // idle | loading | results | error
  const error  = ref(null)
  const center = ref({ lat: 45.4642, lon: 9.1900 })
  const companies  = ref([])
  const selectedId = ref(null)
  const saved      = ref(new Set(JSON.parse(localStorage.getItem('sw_saved') || '[]')))
  const filter     = ref('all')        // all | hiring | saved
  const sort       = ref('hiring')
  const jobs       = reactive({})       // companyId -> Job[]
  const jobsLoading = ref(false)

  const hiringCount = computed(() => companies.value.filter(c => c.hiring).length)
  const savedCount  = computed(() => companies.value.filter(c => saved.value.has(c.id)).length)

  const selected = computed(() => companies.value.find(c => c.id === selectedId.value) || null)

  const filteredCompanies = computed(() => {
    let list = companies.value.slice()
    if (filter.value === 'hiring') list = list.filter(c => c.hiring)
    if (filter.value === 'saved')  list = list.filter(c => saved.value.has(c.id))
    list.sort((a, b) => {
      if (sort.value === 'hiring')   return (b.hiring - a.hiring) || (b.jobs - a.jobs) || a.distance - b.distance
      if (sort.value === 'distance') return a.distance - b.distance
      if (sort.value === 'name')     return a.name.localeCompare(b.name, 'it')
      if (sort.value === 'size')     return parseInt(b.size, 10) - parseInt(a.size, 10)
      return 0
    })
    return list
  })

  async function search() {
    mode.value = 'loading'
    error.value = null
    selectedId.value = null
    try {
      const { data } = await axios.get('/api/search', { params: { ...query } })
      center.value    = { lat: data.lat, lon: data.lon }
      companies.value = data.companies || []
      mode.value      = 'results'
    } catch (e) {
      mode.value = 'error'
      error.value = { city: query.city, message: e?.response?.data?.message || 'Città non trovata' }
      companies.value = []
    }
  }

  async function loadJobs(companyId) {
    if (jobs[companyId]) return
    const company = companies.value.find(c => c.id === companyId)
    if (!company) return
    jobsLoading.value = true
    try {
      const { data } = await axios.get('/api/jobs', {
        params: { name: company.name, city: query.city }
      })
      jobs[companyId] = data.jobs || []
    } catch (e) {
      jobs[companyId] = []
    } finally {
      jobsLoading.value = false
    }
  }

  function toggleSave(id) {
    const s = new Set(saved.value)
    s.has(id) ? s.delete(id) : s.add(id)
    saved.value = s
    localStorage.setItem('sw_saved', JSON.stringify([...s]))
  }

  function exportCsv() {
    const rows = [['Nome','Categoria','Indirizzo','Sito','Telefono','Dipendenti','Distanza km','Annunci attivi']]
    filteredCompanies.value.forEach(c => {
      rows.push([
        c.name,
        CATEGORIES.find(x => x.id === c.category)?.label || c.category,
        c.address, c.website, c.phone, c.size, c.distance,
        c.hiring ? c.jobs : 0
      ])
    })
    const csv = rows.map(r => r.map(x => `"${String(x).replace(/"/g, '""')}"`).join(',')).join('\n')
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' })
    const url  = URL.createObjectURL(blob)
    const a    = document.createElement('a')
    a.href = url; a.download = 'spotwork-aziende.csv'; a.click()
    URL.revokeObjectURL(url)
  }

  function categoryFor(id) { return CATEGORIES.find(c => c.id === id) || CATEGORIES[0] }

  return {
    // costanti
    CATEGORIES, RADII,
    // stato
    query, mode, error, center, companies, selectedId, saved,
    filter, sort, jobs, jobsLoading,
    // derivate
    hiringCount, savedCount, selected, filteredCompanies,
    // azioni
    search, loadJobs, toggleSave, exportCsv, categoryFor,
  }
}
