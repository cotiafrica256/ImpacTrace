<template>
  <div class="min-h-screen flex items-center justify-center bg-[#123f31] px-4 py-12">
    <div class="w-full max-w-md rounded-[28px] bg-[#f4faf6] p-7 shadow-2xl shadow-emerald-950/20 ring-1 ring-emerald-100 md:p-9">
      <div class="mb-8 text-center">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-[#176b4d] text-4xl font-bold text-[#e5c56b] shadow-lg shadow-emerald-950/20">I</div>
        <h1 class="mt-6 text-3xl font-semibold tracking-tight text-slate-800">ImpacTrace</h1>
        <p class="mt-2 text-base text-slate-500">Data collection &amp; reporting</p>
      </div>

      <form @submit.prevent="submit" class="space-y-5">
        <div>
          <label class="mb-2 block text-base font-medium text-slate-600">Email</label>
          <input v-model="email" type="email" required class="w-full" placeholder="name@organisation.org" />
        </div>
        <div>
          <label class="mb-2 block text-base font-medium text-slate-600">Password</label>
          <input v-model="password" type="password" required class="w-full" placeholder="Enter password" />
        </div>

        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

        <button :disabled="loading" class="w-full rounded-xl bg-[#176b4d] py-3.5 text-lg font-semibold text-white shadow-lg shadow-emerald-950/15 hover:bg-[#12563e] disabled:cursor-not-allowed disabled:opacity-70">
          {{ loading ? 'Signing in...' : 'Sign in' }}
        </button>
      </form>

      <div class="mt-6 text-center">
        <RouterLink to="/" class="text-sm font-medium text-[#0d1d2d] underline underline-offset-4">
          Browse the public Data Bank
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store/auth'

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)
const auth = useAuthStore()
const router = useRouter()

async function submit() {
  loading.value = true
  error.value = ''
  try {
    await auth.login(email.value, password.value)
    router.push({ name: 'dashboard' })
  } catch (e) {
    error.value = e.response?.data?.message || 'Login failed. Check your details.'
  } finally {
    loading.value = false
  }
}
</script>
