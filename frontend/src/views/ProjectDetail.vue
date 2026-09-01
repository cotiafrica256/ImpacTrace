<template>
  <div v-if="project">
    <h1 class="text-xl font-semibold text-navy-900">{{ project.name }}</h1>
    <p class="text-sm text-slate-500 mb-6">{{ project.code }} · {{ project.theme }}</p>

    <div class="bg-white rounded-xl p-5 shadow-sm border mb-6">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-medium text-navy-900">Data collection forms</h2>
        <button v-if="auth.canManageProjects" @click="showBuilder = !showBuilder" class="text-sm bg-navy-800 text-white rounded-lg px-3 py-1.5">
          {{ showBuilder ? 'Close' : '+ New form' }}
        </button>
      </div>
      <ul class="divide-y text-sm">
        <li v-for="f in project.forms" :key="f.id" class="py-2 flex justify-between items-center">
          <span>{{ f.title }} <span class="text-slate-400">v{{ f.version }}</span></span>
          <RouterLink :to="`/collect/${f.id}`" class="text-navy-700 hover:underline">Collect data →</RouterLink>
        </li>
      </ul>

      <div v-if="showBuilder" class="mt-4 border-t pt-4">
        <p class="text-xs text-slate-500 mb-2">
          Paste a form schema (sections/fields as JSON) or start from a blank template. The ED / M&E Officer
          designs new instruments here without needing a developer — this is what makes the system reusable
          for the next project after MECPA.
        </p>
        <input v-model="newFormTitle" placeholder="Form title" class="w-full rounded-lg border-slate-300 mb-2" />
        <textarea v-model="newFormSchema" rows="8" class="w-full rounded-lg border-slate-300 font-mono text-xs" placeholder='{"sections": [...]}'></textarea>
        <button @click="createForm" class="mt-2 bg-navy-800 text-white text-sm rounded-lg px-4 py-2">Save form</button>
      </div>
    </div>

    <div class="bg-white rounded-xl p-5 shadow-sm border">
      <h2 class="font-medium text-navy-900 mb-3">Officers on this project</h2>
      <ul class="text-sm divide-y">
        <li v-for="o in project.officers" :key="o.id" class="py-2 flex justify-between">
          <span>{{ o.name }}</span>
          <span class="text-slate-400 uppercase text-xs">{{ o.role }}</span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api/client'
import { useAuthStore } from '../store/auth'

const route = useRoute()
const auth = useAuthStore()
const project = ref(null)
const showBuilder = ref(false)
const newFormTitle = ref('')
const newFormSchema = ref('{\n  "sections": []\n}')

async function load() {
  const { data } = await api.get(`/projects/${route.params.id}`)
  project.value = data
}

async function createForm() {
  const form_schema = JSON.parse(newFormSchema.value)
  await api.post(`/projects/${route.params.id}/forms`, {
    title: newFormTitle.value,
    form_schema,
    requires_consent: true,
    requires_signature: true,
    requires_id_capture: true,
    requires_photo: true,
    allows_voice_note: true,
  })
  showBuilder.value = false
  newFormTitle.value = ''
  load()
}

onMounted(load)
</script>
