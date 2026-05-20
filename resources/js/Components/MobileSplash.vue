<script setup>
import { ref } from 'vue'

const playKey = ref(0)
function replay() { playKey.value++ }
</script>

<template>
  <div class="sw-msplash" @click="replay">
    <div class="sw-msplash-inner" :key="playKey">
      <svg class="sw-msplash-logo" viewBox="0 0 320 290" aria-hidden="true" overflow="visible">
        <!-- Beat 1: navy sphere -->
        <g class="sw-splash-circle">
          <circle cx="160" cy="110" r="92" fill="#1a2540"/>
        </g>
        <!-- Beat 2: teal swoosh -->
        <path
          class="sw-splash-swoosh"
          d="M 58 158 C 110 205 195 200 248 64"
          pathLength="100"
          fill="none" stroke="#2EBFA5" stroke-width="26"
          stroke-linecap="round"
        />
        <!-- Beat 3: arrow head -->
        <polygon
          class="sw-splash-arrow"
          points="262,38 232,98 218,72"
          fill="#2EBFA5"
        />
        <!-- Beat 4: wordmark -->
        <g class="sw-splash-word">
          <text x="160" y="270" text-anchor="middle"
            font-family="Manrope, sans-serif" font-weight="800" font-size="58"
            letter-spacing="-0.02em">
            <tspan fill="#1a2540">Spot</tspan><tspan fill="#2EBFA5">Work</tspan>
          </text>
        </g>
      </svg>
      <div class="sw-msplash-by">BY ARABEL</div>
    </div>
  </div>
</template>
