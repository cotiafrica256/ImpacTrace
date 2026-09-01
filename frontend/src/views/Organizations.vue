<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <div>
        <h1 class="text-xl font-semibold text-navy-900">Client organisations</h1>
        <p class="text-sm text-slate-500">Every organisation below sees only its own projects, staff and data.</p>
      </div>
      <button @click="showCreate = true" class="bg-navy-800 text-white text-sm rounded-lg px-4 py-2">+ Onboard organisation</button>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <div v-for="o in organizations" :key="o.id" class="bg-white rounded-xl p-5 shadow-sm border">
        <div class="flex items-start justify-between">
          <div>
            <div class="font-medium text-navy-900">{{ o.name }}</div>
            <div class="text-xs text-slate-400">{{ o.code }}</div>
          </div>
          <span class="text-xs px-2 py-0.5 rounded-full" :class="o.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'">
            {{ o.is_active ? 'Active' : 'Suspended' }}
          </span>
        </div>
        <div class="text-sm text-slate-500 mt-3 flex gap-4">
          <span>{{ o.users_count }} user(s)</span>
          <span>{{ o.projects_count }} project(s)</span>
        </div>
        <div class="text-xs text-slate-400 mt-2">{{ o.contact_email }}</div>
        <button @click="toggleActive(o)" class="text-xs mt-3 underline" :class="o.is_active ? 'text-red-600' : 'text-green-700'">
          {{ o.is_active ? 'Suspend access' : 'Reactivate' }}
        </button>
      </div>
      <p v-if="!organizations.length" class="text-sm text-slate-400 col-span-2">No organisations onboarded yet.</p>
    </div>

    <div v-if="showCreate" class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h2 class="font-medium text-navy-900 mb-1">Onboard a new organisation</h2>
        <p class="text-xs text-slate-500 mb-4">
          This creates the organisation and its first Executive Director account in one step.
          That ED can then create their own users, projects and forms independently.
        </p>
        <div class="space-y-3">
          <input v-model="form.name" placeholder="Organisation name" class="w-full rounded-lg border-slate-300" />
          <input v-model="form.code" placeholder="Short code (optional, auto-generated if blank)" class="w-full rounded-lg border-slate-300" />
          <input v-model="form.contact_email" placeholder="Organisation contact email (optional)" class="w-full rounded-lg border-slate-300" />
          <hr class="my-2" />
          <input v-model="form.ed_name" placeholder="Executive Director's full name" class="w-full rounded-lg border-slate-300" />
          <input v-model="form.ed_email" placeholder="Executive Director's email (their login)" class="w-full rounded-lg border-slate-300" />
          <input v-model="form.ed_password" type="password" placeholder="Temporary password" class="w-full rounded-lg border-slate-300" />
        </div>
        <p v-if="error" class="text-sm text-red-600 mt-2">{{ error }}</p>
        <div class="flex justify-end gap-2 mt-5">
          <button @click="showCreate = false" class="px-4 py-2 text-sm text-slate-500">Cancel</button>
          <button @click="create" class="px-4 py-2 text-sm bg-navy-800 text-white rounded-lg">Create organisation</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api/client'

const organizations = ref([])
const showCreate = ref(false)
const error = ref('')
const form = ref({ name: '', code: '', contact_email: '', ed_name: '', ed_email: '', ed_password: '' })

async function load() {
  const { data } = await api.get('/organizations')
  organizations.value = data
}

async function create() {
  error.value = ''
  try {
    await api.post('/organizations', form.value)
    showCreate.value = false
    form.value = { name: '', code: '', contact_email: '', ed_name: '', ed_email: '', ed_password: '' }
    load()
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not create this organisation. Check the details and try again.'
  }
}

async function toggleActive(o) {
  await api.put(`/organizations/${o.id}`, { is_active: !o.is_active })
  load()
}

onMounted(load)
</script>
