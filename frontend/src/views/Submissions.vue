<template>
  <div>
    <div class="mb-4 flex items-center justify-between gap-3">
      <h1 class="text-xl font-semibold text-navy-900">Data collected</h1>
      <div v-if="auth.isSuperAdmin" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium uppercase tracking-[0.12em] text-slate-500 shadow-sm">
        {{ currentOrgName || 'All organisations' }}
      </div>
    </div>
    <div class="bg-white rounded-xl border shadow-sm overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="text-left text-slate-400 border-b">
          <tr><th class="p-3">Code</th><th class="p-3">Respondent</th><th class="p-3">Village</th><th class="p-3">Officer</th><th class="p-3">Date</th><th class="p-3">Status</th></tr>
        </thead>
        <tbody>
          <tr v-for="s in submissions" :key="s.id" class="border-b hover:bg-slate-50 cursor-pointer" @click="$router.push(`/submissions/${s.id}`)">
            <td class="p-3">{{ s.submission_code }}</td>
            <td class="p-3">{{ s.respondent?.full_name }}</td>
            <td class="p-3">{{ s.village }}</td>
            <td class="p-3">{{ s.collector?.name }}</td>
            <td class="p-3">{{ s.activity_date }}</td>
            <td class="p-3"><span class="text-xs px-2 py-0.5 rounded-full bg-slate-100">{{ s.status }}</span></td>
          </tr>
        </tbody>
      </table>
      <p v-if="!submissions.length" class="p-6 text-center text-slate-400 text-sm">No submissions yet.</p>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, watch } from 'vue'
import api from '../api/client'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const submissions = ref([])
const organizations = ref([])
const currentOrgName = computed(() => {
  if (!auth.isSuperAdmin || !auth.selectedOrganizationId) return null
  return organizations.value.find((org) => org.id === auth.selectedOrganizationId)?.name || null
})

async function loadOrganizations() {
  if (!auth.isSuperAdmin) return
  const { data } = await api.get('/organizations')
  organizations.value = data
}

async function load() {
  const { data } = await api.get('/submissions')
  submissions.value = data.data || data
}

watch(() => auth.selectedOrganizationId, () => {
  load()
})

onMounted(async () => {
  await loadOrganizations()
  await load()
})
</script>
