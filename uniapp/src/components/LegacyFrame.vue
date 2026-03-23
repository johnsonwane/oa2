<template>
  <view class="legacy-frame">
    <!-- #ifdef H5 -->
    <iframe class="h5-frame" :src="src" frameborder="0"></iframe>
    <!-- #endif -->
    <!-- #ifndef H5 -->
    <web-view :src="src"></web-view>
    <!-- #endif -->
  </view>
</template>

<script setup>
defineProps({
  src: { type: String, required: true }
})
</script>

<style scoped>
.legacy-frame { width: 100%; height: 100vh; overflow: hidden; background: #fff; }
.h5-frame { width: 100%; height: 100vh; border: 0; display: block; }
</style>
