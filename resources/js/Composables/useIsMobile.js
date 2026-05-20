import { ref, onMounted, onBeforeUnmount } from 'vue'

export function useIsMobile(bp = 640) {
  const isMobile = ref(
    typeof window !== 'undefined'
      ? window.matchMedia(`(max-width: ${bp}px)`).matches
      : false
  )

  let mq
  function update(e) { isMobile.value = e.matches }

  onMounted(() => {
    mq = window.matchMedia(`(max-width: ${bp}px)`)
    isMobile.value = mq.matches
    mq.addEventListener('change', update)
  })
  onBeforeUnmount(() => mq?.removeEventListener('change', update))

  return isMobile
}
