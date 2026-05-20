<script setup>
const emit = defineEmits(['done'])
</script>

<template>
  <div class="sw-msplash" @click="$emit('done')">
    <video
      src="@img/spotwork.mp4"
      class="sw-msplash-logo-img"
      autoplay
      muted
      playsinline
      @ended="$emit('done')"
    />
  </div>
</template>
