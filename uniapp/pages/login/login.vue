<template>
  <view class="page">
    <view class="card">
      <view class="title">培训公司 OA 系统</view>
      <view class="subtitle">请登录后进入仪表盘</view>

      <view class="form-item">
        <text class="label">用户名</text>
        <input v-model="form.username" class="input" placeholder="请输入用户名" />
      </view>

      <view class="form-item">
        <text class="label">密码</text>
        <input v-model="form.password" class="input" type="password" placeholder="请输入密码" />
      </view>

      <button class="btn" :disabled="loading" @click="onLogin">{{ loading ? '登录中...' : '登录' }}</button>
      <view class="hint">样例账号：admin / 123456</view>
      <view v-if="error" class="error">{{ error }}</view>
    </view>
  </view>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { post } from '../../utils/request'

const form = reactive({ username: '', password: '' })
const loading = ref(false)
const error = ref('')

const onLogin = async () => {
  error.value = ''
  if (!form.username || !form.password) {
    error.value = '用户名和密码不能为空1'
    return
  }

  loading.value = true
  try {
    const res = await post('/login.php', form)
    const body = res.data || {}

    if (res.statusCode !== 200 || body.code !== 0) {
      error.value = body.message || '登录失败，请重试'
      return
    }

    uni.setStorageSync('oa_token', body.data.token)
    uni.setStorageSync('oa_username', body.data.username)
    uni.reLaunch({ url: '/pages/dashboard/dashboard' })
  } catch (e) {
    error.value = '网络异常，请稍后重试'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped lang="scss">
.page {
  min-height: 100vh;
  padding: 40rpx;
  display: flex;
  align-items: center;
}
.card {
  width: 100%;
  background: $oa-card;
  border-radius: 24rpx;
  padding: 40rpx;
  box-shadow: 0 12rpx 36rpx rgba(15, 23, 42, 0.1);
}
.title { font-size: 40rpx; font-weight: 700; }
.subtitle { margin-top: 12rpx; color: $oa-muted; font-size: 28rpx; }
.form-item { margin-top: 24rpx; }
.label { display: block; margin-bottom: 10rpx; font-size: 26rpx; }
.input {
  border: 1px solid #d1d5db;
  border-radius: 12rpx;
  padding: 18rpx;
  font-size: 28rpx;
  background: #fff;
}
.btn {
  margin-top: 30rpx;
  background: $oa-primary;
  color: #fff;
  border: none;
  border-radius: 12rpx;
}
.hint { margin-top: 20rpx; color: $oa-muted; font-size: 24rpx; }
.error { margin-top: 12rpx; color: $oa-danger; font-size: 24rpx; }
</style>
