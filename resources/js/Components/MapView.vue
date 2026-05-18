<script setup>
// resources/js/Components/MapView.vue
// Wrapper Leaflet â€” props: center, radius, companies, selectedId; emits: select.
// Richiede leaflet 1.9+ in package.json e leaflet/dist/leaflet.css importato in app.js.

import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import L from 'leaflet'

const props = defineProps({
  center:     { type: Object,  required: true },          // { lat, lon }
  radius:     { type: Number,  default: 0 },              // in metri
  companies:  { type: Array,   default: () => [] },
  selectedId: { type: [String, Number, null], default: null },
  mode:       { type: String,  default: 'idle' },
})
const emit = defineEmits(['select'])

const el = ref(null)
let map, markersMap = new Map(), centerMarker, radiusCircle

const TILES = {
  url: 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
  attribution: '&copy; OpenStreetMap & CARTO',
  maxZoom: 19,
}

function pinIcon({ hiring, jobs, selected }) {
  const size = hiring ? (selected ? 38 : 32) : selected ? 30 : 24
  if (hiring) {
    return L.divIcon({
      className: 'sw-pin-wrap',
      html: `<div class="sw-pin sw-pin-hiring${selected ? ' sw-pin-sel' : ''}" style="width:${size}px;height:${size}px;">
              <div class="sw-pin-dot"></div>
              ${jobs > 0 ? `<div class="sw-pin-badge">${jobs > 99 ? '99+' : jobs}</div>` : ''}
            </div>`,
      iconSize: [size, size], iconAnchor: [size / 2, size / 2],
    })
  }
  return L.divIcon({
    className: 'sw-pin-wrap',
    html: `<div class="sw-pin sw-pin-open${selected ? ' sw-pin-sel' : ''}" style="width:${size}px;height:${size}px;">
            <div class="sw-pin-dot-sm"></div>
          </div>`,
    iconSize: [size, size], iconAnchor: [size / 2, size / 2],
  })
}

function centerIcon() {
  return L.divIcon({
    className: 'sw-center-wrap',
    html: `<div class="sw-center-pulse"></div><div class="sw-center-dot"></div>`,
    iconSize: [22, 22], iconAnchor: [11, 11],
  })
}

onMounted(() => {
  map = L.map(el.value, {
    center: [props.center.lat, props.center.lon],
    zoom: 13,
    zoomControl: false,
  })
  L.tileLayer(TILES.url, { attribution: TILES.attribution, maxZoom: TILES.maxZoom }).addTo(map)
  L.control.zoom({ position: 'bottomright' }).addTo(map)
  drawCenter()
  drawMarkers()
  setTimeout(() => map?.invalidateSize(), 100)
})

onBeforeUnmount(() => { map?.remove() })

function drawCenter() {
  if (!map) return
  centerMarker?.remove()
  centerMarker = L.marker([props.center.lat, props.center.lon], {
    icon: centerIcon(), interactive: false, keyboard: false,
  }).addTo(map)
}

function drawRadius() {
  if (!map) return
  radiusCircle?.remove()
  if (props.radius && props.mode === 'results') {
    radiusCircle = L.circle([props.center.lat, props.center.lon], {
      radius: props.radius, color: '#0e1014', weight: 1, opacity: 0.25,
      fillColor: '#0e1014', fillOpacity: 0.04, dashArray: '4 6', interactive: false,
    }).addTo(map)
    try { map.fitBounds(radiusCircle.getBounds(), { padding: [80, 80], maxZoom: 14 }) } catch (e) {}
  }
}

function drawMarkers() {
  if (!map) return
  const nextIds = new Set(props.companies.map(c => c.id))
  for (const [id, mk] of markersMap.entries()) {
    if (!nextIds.has(id)) { mk.remove(); markersMap.delete(id) }
  }
  props.companies.forEach(c => {
    const sel  = c.id === props.selectedId
    const icon = pinIcon({ hiring: c.hiring, jobs: c.jobs, selected: sel })
    let mk = markersMap.get(c.id)
    if (!mk) {
      mk = L.marker([c.lat, c.lon], { icon, riseOnHover: true }).addTo(map)
      mk.on('click', () => emit('select', c.id))
      markersMap.set(c.id, mk)
    } else {
      mk.setIcon(icon); mk.setLatLng([c.lat, c.lon])
    }
    mk.setZIndexOffset(sel ? 1000 : 0)
  })
}

watch(() => [props.center.lat, props.center.lon], () => {
  if (!map) return
  map.flyTo([props.center.lat, props.center.lon], 13, { duration: 0.8 })
  drawCenter()
})
watch(() => [props.radius, props.mode], drawRadius)
watch(() => props.companies, drawMarkers, { deep: true })
watch(() => props.selectedId, () => {
  drawMarkers()
  const sel = props.companies.find(c => c.id === props.selectedId)
  if (sel && map) map.panTo([sel.lat, sel.lon], { animate: true, duration: 0.4 })
})
</script>

<template>
  <div ref="el" class="sw-map" />
</template>
