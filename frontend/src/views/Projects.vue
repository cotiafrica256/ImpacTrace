<template>
  <div>
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <h1 class="text-xl font-semibold text-navy-900">Projects</h1>

      <div v-if="auth.isSuperAdmin" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
        <span class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Organisation</span>
        <select :value="selectedOrgValue" @change="setSelectedOrg" class="rounded-lg border-slate-300 bg-white text-sm">
          <option value="">All organisations</option>
          <option v-for="org in organizations" :key="org.id" :value="String(org.id)">{{ org.name }}</option>
        </select>
      </div>

      <button v-if="auth.canManageProjects" @click="showCreate = true" class="bg-navy-800 text-white text-sm rounded-lg px-4 py-2">
        + New project
      </button>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <RouterLink v-for="p in projects" :key="p.id" :to="`/app/projects/${p.id}`"
        class="bg-white rounded-xl p-5 shadow-sm border hover:border-sky-300 hover:shadow-md">
        <div class="flex items-center justify-between gap-3">
          <div class="text-xs uppercase tracking-[0.12em] text-slate-400">{{ p.code }}</div>
          <span v-if="auth.isSuperAdmin && p.organization" class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-medium text-slate-600">{{ p.organization.name }}</span>
        </div>
        <div class="mt-3 text-lg font-semibold text-slate-800">{{ p.name }}</div>
        <div class="mt-2 text-sm text-slate-500">{{ p.forms?.length || 0 }} form(s) · {{ p.officers?.length || 0 }} officer(s)</div>
      </RouterLink>
    </div>

    <!-- Minimal create-project modal -->
    <div v-if="showCreate" class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h2 class="font-medium text-navy-900 mb-4">New project</h2>
        <div class="space-y-3">
          <input v-model="form.code" placeholder="Project code (e.g. MECPA-REACT-2027)" class="w-full rounded-lg border-slate-300" />
          <input v-model="form.name" placeholder="Project name" class="w-full rounded-lg border-slate-300" />
          <input v-model="form.theme" placeholder="Theme (optional)" class="w-full rounded-lg border-slate-300" />
          <input v-model="form.donor_funder" placeholder="Donor / funder (optional)" class="w-full rounded-lg border-slate-300" />
        </div>
        <div class="flex justify-end gap-2 mt-5">
          <button @click="showCreate = false" class="px-4 py-2 text-sm text-slate-500">Cancel</button>
          <button @click="create" class="px-4 py-2 text-sm bg-navy-800 text-white rounded-lg">Create</button>
        </div>
        <p class="text-xs text-slate-400 mt-3">
          After creating the project, open it to build its data-collection form(s) — every project gets its own,
          the same way MECPA's household survey is just one form under one project.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import api from '../api/client'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const projects = ref([])
const organizations = ref([])
const showCreate = ref(false)
const selectedOrgValue = ref(auth.selectedOrganizationId ? String(auth.selectedOrganizationId) : '')
const form = ref({ code: '', name: '', theme: '', donor_funder: '' })

async function loadOrganizations() {
  if (!auth.isSuperAdmin) return

  const { data } = await api.get('/organizations')
  organizations.value = data

  if (!auth.selectedOrganizationId && data[0]) {
    auth.setSelectedOrganization(data[0].id)
  }

  selectedOrgValue.value = auth.selectedOrganizationId ? String(auth.selectedOrganizationId) : ''
}

async function load() {
  if (auth.isSuperAdmin && !auth.selectedOrganizationId) {
    await loadOrganizations()
  }

  const { data } = await api.get('/projects')
  projects.value = data
}

function setSelectedOrg(event) {
  const value = event.target.value
  auth.setSelectedOrganization(value || null)
  selectedOrgValue.value = value || ''
  load()
}

async function create() {
  if (auth.isSuperAdmin && !auth.selectedOrganizationId) {
    alert('Please select an organisation before creating a project.')
    return
  }

  await api.post('/projects', form.value)
  showCreate.value = false
  form.value = { code: '', name: '', theme: '', donor_funder: '' }
  load()
}

watch(() => auth.selectedOrganizationId, () => {
  selectedOrgValue.value = auth.selectedOrganizationId ? String(auth.selectedOrganizationId) : ''
  load()
})

onMounted(async () => {
  await loadOrganizations()
  await load()
})
</script>
