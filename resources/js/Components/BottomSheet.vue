<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  snap: { type: String, default: 'peek' }, // 'peek' | 'mid' | 'full'
})
const emit = defineEmits(['update:snap'])

const sheetEl = ref(null)
const heights = { peek: 148, mid: 460, full: 720 }

function computeHeights() {
  const vh = window.innerHeight
  heights.peek = 148
  heights.mid  = Math.round(vh * 0.55)
  heights.full = Math.round(vh * 0.92)
}

function applySnap(s, animate = true) {
  const el = sheetEl.value
  if (!el) return
  el.style.transition = animate ? 'height .28s cubic-bezier(.2,.8,.2,1)' : 'none'
  el.style.height = heights[s] + 'px'
}

watch(() => props.snap, s => applySnap(s))

function onResize() { computeHeights(); applySnap(props.snap, false) }
onMounted(() => { computeHeights(); applySnap(props.snap, false); window.addEventListener('resize', onResize) })
onBeforeUnmount(() => window.removeEventListener('resize', onResize))

// Drag
const drag = { active: false, startY: 0, startH: 0, moved: false, lastDy: 0 }

function pickSnap(h, dir) {
  const { peek, mid, full } = heights
  if (dir < -8) return h < mid ? 'mid' : 'full'
  if (dir >  8) return h > mid ? 'mid' : 'peek'
  const opts = [['peek', peek], ['mid', mid], ['full', full]]
  let best = opts[0]
  for (const o of opts) if (Math.abs(h - o[1]) < Math.abs(h - best[1])) best = o
  return best[0]
}

function onPointerDown(e) {
  if (e.pointerType === 'mouse' && e.button !== 0) return
  Object.assign(drag, { active: true, startY: e.clientY, startH: sheetEl.value.offsetHeight, moved: false, lastDy: 0 })
  sheetEl.value.style.transition = 'none'
  try { e.currentTarget.setPointerCapture(e.pointerId) } catch {}
}

function onPointerMove(e) {
  if (!drag.active) return
  const dy = e.clientY - drag.startY
  if (Math.abs(dy) > 4) drag.moved = true
  const newH = Math.max(heights.peek - 20, Math.min(heights.full + 20, drag.startH - dy))
  sheetEl.value.style.height = newH + 'px'
  drag.lastDy = dy
}

function onPointerUp() {
  if (!drag.active) return
  drag.active = false
  const h = sheetEl.value.offsetHeight
  const next = pickSnap(h, drag.lastDy)
  applySnap(next)
  if (!drag.moved) {
    const cycle = { peek: 'mid', mid: 'full', full: 'peek' }
    emit('update:snap', cycle[props.snap])
  } else if (next !== props.snap) {
    emit('update:snap', next)
  }
}
</script>

<template>
  <div :class="['sw-sheet', `sw-sheet-${snap}`]" ref="sheetEl">
    <div
      class="sw-sheet-handle"
      @pointerdown="onPointerDown"
      @pointermove="onPointerMove"
      @pointerup="onPointerUp"
      @pointercancel="onPointerUp"
    >
      <div class="sw-sheet-grip" />
      <slot name="header" />
    </div>
    <div class="sw-sheet-body">
      <slot />
    </div>
  </div>
</template>
