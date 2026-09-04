<template>
  <div class="min-h-screen bg-[#123f31] px-4 py-10 sm:py-16">
    <main class="mx-auto max-w-md rounded-3xl bg-[#f4faf6] p-6 shadow-2xl sm:p-9">
      <RouterLink to="/" class="text-sm font-semibold text-teal-700">← Back to Data Bank</RouterLink>
      <div class="mt-8">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Reader access</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Register or sign in</h1>
        <p class="mt-2 text-sm leading-6 text-slate-500">Create a reader account to request access to protected reports and publications.</p>
      </div>

      <form class="mt-7 space-y-4" @submit.prevent="submit">
        <div v-if="mode === 'register'">
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Name</label>
          <input v-model="form.name" required placeholder="Your full name" class="w-full" />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
          <input v-model="form.email" required type="email" placeholder="you@example.org" class="w-full" />
        </div>
        <div v-if="mode === 'register'">
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Phone</label>
          <input v-model="form.phone" placeholder="Mobile money number" class="w-full" />
        </div>
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
          <input v-model="form.password" required minlength="8" type="password" placeholder="At least 8 characters" class="w-full" />
        </div>
        <div v-if="mode === 'register'">
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Confirm password</label>
          <input v-model="form.password_confirmation" required minlength="8" type="password" placeholder="Repeat your password" class="w-full" />
        </div>
        <p v-if="error" class="rounded-xl bg-rose-50 p-3 text-sm leading-5 text-rose-700">{{ error }}</p>
        <button :disabled="loading" class="w-full rounded-xl bg-[#0d1d2d] px-4 py-3.5 font-semibold text-white disabled:opacity-60">{{ loading ? 'Please wait...' : mode === 'register' ? 'Create reader account' : 'Sign in' }}</button>
      </form>

      <button @click="mode = mode === 'register' ? 'login' : 'register'; error = ''" class="mt-5 w-full text-center text-sm font-semibold text-teal-700 underline underline-offset-4">
        {{ mode === 'register' ? 'Already have an account? Sign in' : 'New reader? Create an account' }}
      </button>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api/client'

const route = useRoute()
const router = useRouter()
const mode = ref('register')
const loading = ref(false)
const error = ref('')
const form = ref({ name: '', email: '', phone: '', password: '', password_confirmation: '' })

async function submit() {
  loading.value = true
  error.value = ''
  try {
    const endpoint = mode.value === 'register' ? '/public/auth/register' : '/public/auth/login'
    const payload = mode.value === 'register' ? form.value : { email: form.value.email, password: form.value.password }
    const { data } = await api.post(endpoint, payload)
    localStorage.setItem('public_token', data.token)
    if (route.query.redirect) router.push(route.query.redirect)
    else router.push('/')
  } catch (requestError) {
    const errors = requestError.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : requestError.response?.data?.message || 'We could not sign you in. Check your details and try again.'
  } finally {
    loading.value = false
  }
}
</script>
