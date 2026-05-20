<script setup>
import { onMounted } from 'vue'

const emit = defineEmits(['done'])

onMounted(() => {
  setTimeout(() => emit('done'), 1800)
})
</script>

<template>
  <div class="sw-msplash" @click="$emit('done')">
    <img src="@img/sw_full.jpg" alt="Spotwork" class="sw-msplash-logo-img" />
  </div>
</template>
