<template>
  <div>
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <h1 class="text-xl font-semibold text-slate-800">Daily attendance</h1>

      <div v-if="auth.isSuperAdmin" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
        <span class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Organisation</span>
        <select :value="selectedOrgValue" @change="setSelectedOrg" class="rounded-lg border-slate-300 bg-white text-sm">
          <option value="">All organisations</option>
          <option v-for="org in organizations" :key="org.id" :value="String(org.id)">{{ org.name }}</option>
        </select>
      </div>
    </div>

    <div class="mb-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex flex-col gap-3 md:flex-row md:items-end">
        <div class="flex-1">
          <label class="mb-1 block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Project</label>
          <select v-model="projectId" class="w-full">
            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
        </div>
        <div class="flex-1">
          <label class="mb-1 block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Date</label>
          <input type="date" v-model="date" class="w-full" />
        </div>
        <button @click="load" class="rounded-xl bg-[#0d1d2d] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#16324c]">Extract attendance</button>
      </div>

      <div class="mt-4 flex flex-wrap items-center gap-3">
        <label class="cursor-pointer rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
          Upload attendance CSV
          <input type="file" accept=".csv,text/csv" class="hidden" @change="uploadCsv" />
        </label>
        <label class="cursor-pointer rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
          Scan from image
          <input type="file" accept="image/*" capture="environment" class="hidden" @change="scanAttendanceImage" />
        </label>
        <span v-if="uploadStatus" class="text-sm text-slate-500">{{ uploadStatus }}</span>
      </div>
    </div>

    <div v-if="isScanning" class="mb-4 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-700">
      Processing the image to extract attendance names…
    </div>

    <div v-if="result" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <p class="mb-3 text-sm text-slate-600">
        {{ result.total_attended }} people attended on {{ result.date }} ({{ result.female }} female, {{ result.male }} male).
      </p>
      <div class="overflow-x-auto">
        <table class="w-full min-w-[640px] text-sm">
          <thead class="border-b border-slate-200 text-left text-slate-500"><tr><th class="p-2">Name</th><th class="p-2">Sex</th><th class="p-2">Village</th><th class="p-2">Officer</th><th class="p-2">Time</th><th class="p-2">Signed</th></tr></thead>
          <tbody>
            <tr v-for="a in result.attendees" :key="a.submission_code" class="border-b border-slate-100">
              <td class="p-2">{{ a.full_name }}</td>
              <td class="p-2">{{ a.sex }}</td>
              <td class="p-2">{{ a.village }}</td>
              <td class="p-2">{{ a.collected_by }}</td>
              <td class="p-2">{{ a.time }}</td>
              <td class="p-2">{{ a.has_signature ? 'Yes' : 'No' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <button @click="exportCsv" class="mt-4 rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Export as CSV</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import Tesseract from 'tesseract.js'
import api from '../api/client'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const organizations = ref([])
const projects = ref([])
const projectId = ref(null)
const date = ref(new Date().toISOString().slice(0, 10))
const result = ref(null)
const uploadStatus = ref('')
const isScanning = ref(false)
const selectedOrgValue = ref(auth.selectedOrganizationId ? String(auth.selectedOrganizationId) : '')

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
  if (!projectId.value && data[0]) projectId.value = data[0].id
  if (data.length && !data.some((p) => p.id === projectId.value)) projectId.value = data[0].id
}

function setSelectedOrg(event) {
  const value = event.target.value
  auth.setSelectedOrganization(value || null)
  selectedOrgValue.value = value || ''
  loadProjects()
}

async function load() {
  const { data } = await api.get('/attendance', { params: { project_id: projectId.value, date: date.value } })
  result.value = data
}

function parseAttendanceText(text) {
  const lines = text
    .replace(/\r/g, '')
    .split('\n')
    .map((line) => line.trim())
    .filter((line) => line.length > 2)

  const clean = lines.filter((line) => !/attendance|date|project|officer|name|sex|village|total|female|male|signed|present/i.test(line))
  const rows = clean
    .map((line) => line.replace(/[|]/g, ' ').replace(/\s+/g, ' ').trim())
    .filter((line) => !/^\d+$/.test(line))

  const attendees = rows.slice(0, 80).map((line, index) => {
    const normalized = line.toLowerCase()
    let sex = 'unknown'
    if (normalized.includes('female')) sex = 'female'
    else if (normalized.includes('male')) sex = 'male'

    const nameMatch = line.match(/([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+|[A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,3})/)
    const full_name = nameMatch ? nameMatch[1] : line
    const village = line.includes('Village') ? line.split('Village')[1]?.trim() || '' : ''

    return {
      submission_code: `OCR-${index + 1}`,
      full_name: full_name || `Attendee ${index + 1}`,
      sex,
      village,
      collected_by: 'OCR Scan',
      time: '',
      has_signature: false,
    }
  })

  return attendees
}

async function scanAttendanceImage(event) {
  const file = event.target.files?.[0]
  if (!file) return

  isScanning.value = true
  uploadStatus.value = `Scanning ${file.name}…`

  try {
    const { data } = await Tesseract.recognize(file, 'eng', {
      logger: (m) => {
        if (m.status === 'recognizing text') uploadStatus.value = `Scanning ${file.name}… ${Math.round(m.progress * 100)}%`
      },
    })

    const attendees = parseAttendanceText(data.text)
    const total = attendees.length
    result.value = {
      date: date.value,
      total_attended: total,
      female: attendees.filter((a) => a.sex === 'female').length,
      male: attendees.filter((a) => a.sex === 'male').length,
      attendees,
    }
    uploadStatus.value = `Extracted ${total} attendee rows from ${file.name}`
  } catch (error) {
    uploadStatus.value = 'Unable to read the attendance image. Try a sharper photo or upload a CSV instead.'
    console.error(error)
  } finally {
    isScanning.value = false
  }
}

function uploadCsv(event) {
  const file = event.target.files?.[0]
  if (!file) return

  const reader = new FileReader()
  reader.onload = () => {
    const text = String(reader.result || '')
    const lines = text.split(/\r?\n/).filter(Boolean)
    if (lines.length < 2) {
      uploadStatus.value = 'CSV is empty or missing rows.'
      return
    }

    const headers = lines[0].split(',').map((h) => h.trim().toLowerCase())
    const records = lines.slice(1).map((line) => {
      const values = line.split(',')
      return headers.reduce((acc, key, idx) => {
        acc[key] = values[idx]?.trim() || ''
        return acc
      }, {})
    })

    result.value = {
      date: date.value,
      total_attended: records.length,
      female: records.filter((r) => String(r.sex || '').toLowerCase() === 'female').length,
      male: records.filter((r) => String(r.sex || '').toLowerCase() === 'male').length,
      attendees: records.map((row, index) => ({
        submission_code: `CSV-${index + 1}`,
        full_name: row.name || row.full_name || 'Unknown',
        sex: row.sex || 'unknown',
        village: row.village || '',
        collected_by: row.officer || 'Imported',
        time: row.time || '',
        has_signature: row.signed?.toLowerCase() === 'yes',
      }))
    }
    uploadStatus.value = `Imported ${records.length} attendance rows from ${file.name}`
  }
  reader.readAsText(file)
}

function exportCsv() {
  const rows = [['Name', 'Sex', 'Village', 'Officer', 'Time', 'Signed'], ...result.value.attendees.map((a) => [a.full_name, a.sex, a.village, a.collected_by, a.time, a.has_signature ? 'Yes' : 'No'])]
  const csv = rows.map((r) => r.map((c) => `"${c ?? ''}"`).join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv' })
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = `attendance-${date.value}.csv`
  a.click()
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
