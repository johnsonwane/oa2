<template>
  <LegacyFrame :src="legacyIndexUrl" />
</template>

<script setup>
import LegacyFrame from '../../components/LegacyFrame.vue'

const legacyIndexUrl = '/index.html'
</script>
