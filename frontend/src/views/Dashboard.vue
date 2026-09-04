<template>
  <div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-700">Workspace overview</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-navy-900">Welcome, {{ auth.user?.name }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ today }}</p>
      </div>

      <div v-if="auth.isSuperAdmin" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
        <span class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Organisation</span>
        <select :value="selectedOrgValue" @change="setSelectedOrg" class="rounded-lg border-slate-300 bg-white text-sm">
          <option value="">All organisations</option>
          <option v-for="org in organizations" :key="org.id" :value="String(org.id)">{{ org.name }}</option>
        </select>
      </div>
    </div>

    <div class="mb-8 grid grid-cols-2 gap-3 lg:grid-cols-4">
      <div v-for="metric in metrics" :key="metric.label" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-2"><div class="text-2xl font-semibold text-navy-900">{{ metric.value }}</div><span :class="metric.tone" class="rounded-lg px-2 py-1 text-[10px] font-bold">{{ metric.icon }}</span></div>
        <div class="mt-2 text-xs font-medium text-slate-500">{{ metric.label }}</div>
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

    <div v-else class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
      <div class="mb-4 flex items-center justify-between"><h2 class="font-semibold text-navy-900">Your projects</h2><RouterLink to="/app/projects" class="text-sm font-semibold text-teal-700">View all</RouterLink></div>
      <ul class="text-sm divide-y">
        <li v-for="p in projects" :key="p.id" class="py-2 flex justify-between">
          <RouterLink :to="`/app/projects/${p.id}`" class="text-navy-800 hover:underline">{{ p.name }}</RouterLink>
          <span class="text-slate-400">{{ p.code }}</span>
        </li>
      </ul>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="mb-4 flex items-center justify-between"><h2 class="font-semibold text-navy-900">Recent submissions</h2><RouterLink to="/app/submissions" class="text-sm font-semibold text-teal-700">Open data</RouterLink></div>
      <div v-if="recentSubmissions.length" class="space-y-3"><div v-for="submission in recentSubmissions" :key="submission.id" class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-3"><div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-700">{{ submission.respondent?.full_name || submission.submission_code }}</p><p class="mt-1 text-xs text-slate-500">{{ submission.village || 'Location pending' }} · {{ submission.activity_date }}</p></div><span class="shrink-0 rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-semibold capitalize text-emerald-700">{{ submission.status }}</span></div></div>
      <p v-else class="text-sm text-slate-500">Your latest submissions will appear here.</p>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import api from '../api/client'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const organizations = ref([])
const projects = ref([])
const stats = ref({})
const submissions = ref([])
const selectedOrgValue = ref(auth.selectedOrganizationId ? String(auth.selectedOrganizationId) : '')
const today = new Date().toLocaleDateString('en-GB', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
const recentSubmissions = computed(() => submissions.value.slice(0, 4))
const metrics = computed(() => [
  { label: 'Submissions', value: submissions.value.length || '—', icon: 'DATA', tone: 'bg-sky-50 text-sky-700' },
  { label: 'Active projects', value: projects.value.length || '—', icon: 'WORK', tone: 'bg-amber-50 text-amber-700' },
  { label: 'Approved records', value: submissions.value.filter((item) => item.status === 'approved').length || '—', icon: 'OK', tone: 'bg-emerald-50 text-emerald-700' },
  { label: 'High vulnerability', value: submissions.value.filter((item) => item.vulnerability_class === 'High').length || '—', icon: 'CARE', tone: 'bg-rose-50 text-rose-700' },
])

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

async function loadSubmissions() {
  const { data } = await api.get('/submissions')
  submissions.value = data.data || data
}

function setSelectedOrg(event) {
  const value = event.target.value
  auth.setSelectedOrganization(value || null)
  selectedOrgValue.value = value || ''
  loadProjects()
  loadSubmissions()
}

watch(() => auth.selectedOrganizationId, () => {
  selectedOrgValue.value = auth.selectedOrganizationId ? String(auth.selectedOrganizationId) : ''
  loadProjects()
})

onMounted(async () => {
  await loadOrganizations()
  await loadProjects()
  await loadSubmissions()
})
</script>
