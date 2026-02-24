<template>
  <view class="page">
    <view class="card">
      <view class="title">仪表盘</view>
      <view class="subtitle">欢迎你，{{ username }}</view>

      <view class="panel">
        <view class="panel-title">系统状态</view>
        <view class="row">前端地址：https://oac.hahahaxinli.com</view>
        <view class="row">后端地址：https://oac.hahahaxinli.com/api</view>
        <view class="row">当前令牌：{{ token }}</view>
      </view>

      <button class="btn" @click="logout">退出登录</button>
    </view>
  </view>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const username = ref('')
const token = ref('')

onMounted(() => {
  const savedToken = uni.getStorageSync('oa_token')
  const savedUser = uni.getStorageSync('oa_username')

  if (!savedToken || !savedUser) {
    uni.reLaunch({ url: '/pages/login/login' })
    return
  }

  token.value = savedToken
  username.value = savedUser
})

const logout = () => {
  uni.removeStorageSync('oa_token')
  uni.removeStorageSync('oa_username')
  uni.reLaunch({ url: '/pages/login/login' })
}
</script>

<style scoped lang="scss">
.page {
  min-height: 100vh;
  padding: 40rpx;
}
.card {
  background: $oa-card;
  border-radius: 24rpx;
  padding: 40rpx;
  box-shadow: 0 12rpx 36rpx rgba(15, 23, 42, 0.1);
}
.title { font-size: 40rpx; font-weight: 700; }
.subtitle { margin-top: 12rpx; color: $oa-muted; font-size: 28rpx; }
.panel {
  margin-top: 24rpx;
  background: #f9fafb;
  border-radius: 12rpx;
  padding: 20rpx;
}
.panel-title { font-size: 28rpx; font-weight: 600; margin-bottom: 10rpx; }
.row { font-size: 24rpx; color: $oa-text; margin-top: 8rpx; word-break: break-all; }
.btn {
  margin-top: 28rpx;
  background: $oa-primary;
  color: #fff;
  border: none;
  border-radius: 12rpx;
}
</style>
