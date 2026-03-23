<template>
  <LegacyFrame :src="legacyLoginUrl" />
</template>

<script setup>
import LegacyFrame from '../../components/LegacyFrame.vue'

const legacyLoginUrl = '/login.html'
</script>
