import { ref } from 'vue'

const open = ref(false)

export function useLoginModal() {
  return {
    loginModalOpen: open,
    openLoginModal() { open.value = true },
    closeLoginModal() { open.value = false },
  }
}
