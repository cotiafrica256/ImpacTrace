<template>
  <div>
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <h1 class="text-xl font-semibold text-navy-900">Reports</h1>

      <div v-if="auth.isSuperAdmin" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
        <span class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Organisation</span>
        <select :value="selectedOrgValue" @change="setSelectedOrg" class="rounded-lg border-slate-300 bg-white text-sm">
          <option value="">All organisations</option>
          <option v-for="org in organizations" :key="org.id" :value="String(org.id)">{{ org.name }}</option>
        </select>
      </div>
    </div>

    <div class="bg-white rounded-xl border shadow-sm p-5 mb-6 flex flex-wrap gap-3 items-end">
      <div>
        <label class="text-xs text-slate-500 block mb-1">Project</label>
        <select v-model="gen.project_id" class="rounded-lg border-slate-300">
          <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
        </select>
      </div>
      <div>
        <label class="text-xs text-slate-500 block mb-1">Type</label>
        <select v-model="gen.type" class="rounded-lg border-slate-300">
          <option value="weekly_activity">Weekly Activity Report</option>
          <option value="monthly_activity">Monthly Activity Report</option>
          <option value="monthly_me">Monthly M&E Report</option>
          <option value="quarterly_progress">Quarterly Progressive Report (funders)</option>
          <option value="annual">Annual Report</option>
        </select>
      </div>
      <div>
        <label class="text-xs text-slate-500 block mb-1">From</label>
        <input type="date" v-model="gen.period_start" class="rounded-lg border-slate-300" />
      </div>
      <div>
        <label class="text-xs text-slate-500 block mb-1">To</label>
        <input type="date" v-model="gen.period_end" class="rounded-lg border-slate-300" />
      </div>
      <button @click="generate" class="bg-navy-800 text-white text-sm rounded-lg px-4 py-2">Generate</button>
    </div>

    <div v-if="active" class="bg-white rounded-xl border shadow-sm p-5 mb-6">
      <h2 class="font-medium text-navy-900 mb-3">{{ typeLabel(active.type) }} — {{ active.period_start }} to {{ active.period_end }}</h2>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4 text-sm">
        <div class="bg-slate-50 rounded-lg p-3"><div class="text-lg font-semibold">{{ active.auto_stats.total_submissions }}</div><div class="text-xs text-slate-500">Households/entries</div></div>
        <div class="bg-slate-50 rounded-lg p-3"><div class="text-lg font-semibold">{{ active.auto_stats.unique_respondents }}</div><div class="text-xs text-slate-500">Unique respondents</div></div>
        <div class="bg-slate-50 rounded-lg p-3"><div class="text-lg font-semibold">{{ active.auto_stats.average_vulnerability_score ?? '—' }}</div><div class="text-xs text-slate-500">Avg. vulnerability /80</div></div>
        <div class="bg-slate-50 rounded-lg p-3"><div class="text-lg font-semibold">{{ Object.keys(active.auto_stats.by_village_top10 || {}).length }}</div><div class="text-xs text-slate-500">Villages reached</div></div>
      </div>

      <div class="space-y-3">
        <div v-for="field in narrativeFields" :key="field.key">
          <label class="text-sm font-medium text-slate-700">{{ field.label }}</label>
          <textarea v-model="active.narrative[field.key]" rows="3" class="w-full rounded-lg border-slate-300"></textarea>
        </div>
      </div>
      <div class="flex justify-end gap-2 mt-4 flex-wrap">
        <button @click="downloadPdf" class="text-sm px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700">Download PDF</button>
        <button @click="save('draft')" class="text-sm px-4 py-2 text-slate-500">Save draft</button>
        <button @click="save('submitted_for_review')" class="text-sm px-4 py-2 bg-slate-100 rounded-lg">Submit for review</button>
        <button v-if="auth.isEd || auth.isMeo" @click="save('approved')" class="text-sm px-4 py-2 bg-green-700 text-white rounded-lg">Approve</button>
      </div>
    </div>

    <div class="bg-white rounded-xl border shadow-sm">
      <table class="w-full text-sm">
        <thead class="text-left text-slate-400 border-b"><tr><th class="p-3">Type</th><th class="p-3">Period</th><th class="p-3">Status</th></tr></thead>
        <tbody>
          <tr v-for="r in reports" :key="r.id" class="border-b hover:bg-slate-50 cursor-pointer" @click="active = r">
            <td class="p-3">{{ typeLabel(r.type) }}</td>
            <td class="p-3">{{ r.period_start }} → {{ r.period_end }}</td>
            <td class="p-3"><span class="text-xs px-2 py-0.5 rounded-full bg-slate-100">{{ r.status }}</span></td>
          </tr>
        </tbody>
      </table>
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
const reports = ref([])
const active = ref(null)
const selectedOrgValue = ref(auth.selectedOrganizationId ? String(auth.selectedOrganizationId) : '')
const gen = ref({ project_id: null, type: 'weekly_activity', period_start: '', period_end: '' })

const narrativeFields = [
  { key: 'summary', label: 'Summary of the period' },
  { key: 'achievements', label: 'Key achievements' },
  { key: 'challenges', label: 'Challenges encountered' },
  { key: 'lessons_learned', label: 'Lessons learned' },
  { key: 'next_steps', label: 'Planned next steps' },
]

function typeLabel(t) {
  return { weekly_activity: 'Weekly Activity Report', monthly_activity: 'Monthly Activity Report', monthly_me: 'Monthly M&E Report', quarterly_progress: 'Quarterly Progressive Report', annual: 'Annual Report' }[t] || t
}

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
  if (!data.some((p) => p.id === gen.value.project_id)) {
    gen.value.project_id = data[0]?.id || null
  }
}

async function loadReports() {
  const { data } = await api.get('/reports')
  reports.value = data.data || data
}

function setSelectedOrg(event) {
  const value = event.target.value
  auth.setSelectedOrganization(value || null)
  selectedOrgValue.value = value || ''
  loadProjects()
  loadReports()
}

async function generate() {
  if (auth.isSuperAdmin && !auth.selectedOrganizationId) {
    alert('Please select an organisation before generating a report.')
    return
  }

  const { data } = await api.post('/reports/generate', gen.value)
  active.value = data
  loadReports()
}

async function save(status) {
  const { data } = await api.put(`/reports/${active.value.id}`, { narrative: active.value.narrative, status })
  active.value = data
  loadReports()
}

async function downloadPdf() {
  if (!active.value) return
  const response = await api.get(`/reports/${active.value.id}/pdf`, { responseType: 'blob' })
  const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', `report-${active.value.id}.pdf`)
  document.body.appendChild(link)
  link.click()
  link.remove()
}

watch(() => auth.selectedOrganizationId, () => {
  selectedOrgValue.value = auth.selectedOrganizationId ? String(auth.selectedOrganizationId) : ''
  loadProjects()
  loadReports()
})

onMounted(async () => {
  await loadOrganizations()
  await loadProjects()
  await loadReports()
})
</script>
