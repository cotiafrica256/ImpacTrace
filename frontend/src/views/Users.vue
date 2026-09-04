<template>
  <div>
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div><h1 class="text-xl font-semibold text-navy-900">Users &amp; roles</h1><input v-if="auth.isSuperAdmin" v-model="search" @input="load" placeholder="Search all users..." class="mt-2 w-full max-w-sm text-sm" /></div>
      <div v-if="auth.isSuperAdmin" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
        <span class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Organisation</span>
        <select :value="selectedOrgValue" @change="setSelectedOrg" class="rounded-lg border-slate-300 bg-white text-sm">
          <option value="">All organisations</option>
          <option v-for="org in organizations" :key="org.id" :value="String(org.id)">{{ org.name }}</option>
        </select>
      </div>
      <button @click="showCreate = true" class="bg-navy-800 text-white text-sm rounded-lg px-4 py-2">+ New user</button>
    </div>

    <div class="bg-white rounded-xl border shadow-sm overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="text-left text-slate-400 border-b"><tr><th class="p-3">Name</th><th class="p-3">Email</th><th class="p-3">Role</th><th class="p-3">Status</th><th class="p-3"></th></tr></thead>
        <tbody>
          <tr v-for="u in users" :key="u.id" class="border-b">
            <td class="p-3">{{ u.name }}</td>
            <td class="p-3">{{ u.email }}</td>
            <td class="p-3">
              <select :value="u.role" @change="changeRole(u, $event.target.value)" class="rounded-lg border-slate-300 text-xs">
                <option value="ed">Executive Director</option>
                <option value="meo">M&E Officer</option>
                <option value="po">Project Officer</option>
                <option value="fo">Field Officer</option>
                <option value="customer_service">Customer Service</option>
                <option value="reader_manager">Reader Manager</option>
              </select>
            </td>
            <td class="p-3">{{ u.is_active ? 'Active' : 'Deactivated' }}</td>
            <td class="p-3"><button @click="deactivate(u)" class="text-xs text-red-600">Deactivate</button></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showCreate" class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h2 class="font-medium text-navy-900 mb-4">New user</h2>
        <div class="space-y-3">
          <div v-if="auth.isSuperAdmin" class="space-y-1">
            <label class="block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Organisation</label>
            <select v-model="form.organization_id" class="w-full rounded-lg border-slate-300">
              <option value="">Select organisation</option>
              <option v-for="org in organizations" :key="org.id" :value="org.id">{{ org.name }}</option>
            </select>
          </div>
          <input v-model="form.name" placeholder="Full name" class="w-full rounded-lg border-slate-300" />
          <input v-model="form.email" placeholder="Email" class="w-full rounded-lg border-slate-300" />
          <input v-model="form.phone" placeholder="Phone (optional)" class="w-full rounded-lg border-slate-300" />
          <select v-model="form.role" class="w-full rounded-lg border-slate-300">
            <option value="fo">Field Officer</option>
            <option value="po">Project Officer</option>
            <option value="meo">M&E Officer</option>
            <option value="ed">Executive Director</option>
            <option value="customer_service">Customer Service</option>
            <option value="reader_manager">Reader Manager</option>
          </select>
          <input v-model="form.password" type="password" placeholder="Temporary password" class="w-full rounded-lg border-slate-300" />
        </div>
        <div class="flex justify-end gap-2 mt-5">
          <button @click="showCreate = false" class="px-4 py-2 text-sm text-slate-500">Cancel</button>
          <button @click="create" class="px-4 py-2 text-sm bg-navy-800 text-white rounded-lg">Create</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import api from '../api/client'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const users = ref([])
const organizations = ref([])
const showCreate = ref(false)
const search = ref('')
const selectedOrgValue = ref(auth.selectedOrganizationId ? String(auth.selectedOrganizationId) : '')
const form = ref({
  name: '',
  email: '',
  phone: '',
  role: 'fo',
  password: '',
  organization_id: auth.selectedOrganizationId || '',
})

async function loadOrganizations() {
  if (!auth.isSuperAdmin) return

  const { data } = await api.get('/organizations')
  organizations.value = data

  if (!auth.selectedOrganizationId && data[0]) {
    auth.setSelectedOrganization(data[0].id)
  }

  selectedOrgValue.value = auth.selectedOrganizationId ? String(auth.selectedOrganizationId) : ''
  form.value.organization_id = auth.selectedOrganizationId || ''
}

async function load() {
  const params = new URLSearchParams()
  if (auth.isSuperAdmin && auth.selectedOrganizationId) params.set('organization_id', auth.selectedOrganizationId)
  if (auth.isSuperAdmin && search.value) params.set('q', search.value)
  const query = params.toString() ? `?${params.toString()}` : ''
  const { data } = await api.get(`/users${query}`)
  users.value = data
}

function setSelectedOrg(event) {
  const value = event.target.value
  auth.setSelectedOrganization(value || null)
  selectedOrgValue.value = value || ''
  form.value.organization_id = value || ''
  load()
}

async function create() {
  const payload = { ...form.value }

  if (auth.isSuperAdmin) {
    if (!payload.organization_id) {
      alert('Please select an organisation before creating a user.')
      return
    }
  } else {
    delete payload.organization_id
  }

  await api.post('/users', payload)
  showCreate.value = false
  form.value = {
    name: '',
    email: '',
    phone: '',
    role: 'fo',
    password: '',
    organization_id: auth.selectedOrganizationId || '',
  }
  load()
}

async function changeRole(u, role) {
  await api.put(`/users/${u.id}`, { role })
  load()
}

async function deactivate(u) {
  if (!confirm(`Deactivate ${u.name}?`)) return
  await api.delete(`/users/${u.id}`)
  load()
}

watch(() => auth.selectedOrganizationId, () => {
  selectedOrgValue.value = auth.selectedOrganizationId ? String(auth.selectedOrganizationId) : ''
  form.value.organization_id = auth.selectedOrganizationId || ''
  load()
})

onMounted(async () => {
  await loadOrganizations()
  await load()
})
</script>
