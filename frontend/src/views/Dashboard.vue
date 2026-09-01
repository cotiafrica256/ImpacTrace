<template>
  <div>
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-navy-900 mb-1">Welcome, {{ auth.user?.name }}</h1>
        <p class="text-sm text-slate-500">{{ today }}</p>
      </div>

      <div v-if="auth.isSuperAdmin" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
        <span class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Organisation</span>
        <select :value="selectedOrgValue" @change="setSelectedOrg" class="rounded-lg border-slate-300 bg-white text-sm">
          <option value="">All organisations</option>
          <option v-for="org in organizations" :key="org.id" :value="String(org.id)">{{ org.name }}</option>
        </select>
      </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-white rounded-xl p-4 shadow-sm border">
        <div class="text-2xl font-semibold text-navy-900">{{ stats.total_submissions ?? '—' }}</div>
        <div class="text-xs text-slate-500 mt-1">Submissions (all time)</div>
      </div>
      <div class="bg-white rounded-xl p-4 shadow-sm border">
        <div class="text-2xl font-semibold text-navy-900">{{ projects.length }}</div>
        <div class="text-xs text-slate-500 mt-1">Active projects</div>
      </div>
    </div>

    <div v-if="auth.isFo" class="bg-white rounded-xl p-5 shadow-sm border">
      <h2 class="font-medium text-navy-900 mb-3">Start data collection</h2>
      <div v-for="p in projects" :key="p.id" class="mb-3">
        <div class="text-sm font-medium text-slate-700 mb-1">{{ p.name }}</div>
        <RouterLink v-for="f in p.forms" :key="f.id" :to="`/collect/${f.id}`"
          class="inline-block bg-navy-800 text-white text-sm rounded-lg px-3 py-2 mr-2 mb-2 hover:bg-navy-700">
          {{ f.title }}
        </RouterLink>
      </div>
      <p v-if="!projects.length" class="text-sm text-slate-400">You are not yet assigned to a project. Contact your Project Officer.</p>
    </div>

    <div v-else class="bg-white rounded-xl p-5 shadow-sm border">
      <h2 class="font-medium text-navy-900 mb-2">Your projects</h2>
      <ul class="text-sm divide-y">
        <li v-for="p in projects" :key="p.id" class="py-2 flex justify-between">
          <RouterLink :to="`/projects/${p.id}`" class="text-navy-800 hover:underline">{{ p.name }}</RouterLink>
          <span class="text-slate-400">{{ p.code }}</span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import api from '../api/client'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const organizations = ref([])
const projects = ref([])
const stats = ref({})
const selectedOrgValue = ref(auth.selectedOrganizationId ? String(auth.selectedOrganizationId) : '')
const today = new Date().toLocaleDateString('en-GB', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })

async function loadOrganizations() {
  if (!auth.isSuperAdmin) return

  const { data } = await api.get('/organizations')
  organizations.value = data

  if (!auth.selectedOrganizationId && data[0]) {
    auth.setSelectedOrganization(data[0].id)
  }

  selectedOrgValue.value = auth.selectedOrganizationId ? String(auth.selectedOrganizationId) : ''
}

async function loadProjects() {
  const { data } = await api.get('/projects')
  projects.value = data
}

function setSelectedOrg(event) {
  const value = event.target.value
  auth.setSelectedOrganization(value || null)
  selectedOrgValue.value = value || ''
  loadProjects()
}

watch(() => auth.selectedOrganizationId, () => {
  selectedOrgValue.value = auth.selectedOrganizationId ? String(auth.selectedOrganizationId) : ''
  loadProjects()
})

onMounted(async () => {
  await loadOrganizations()
  await loadProjects()
})
</script>
