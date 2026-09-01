<template>
  <div v-if="s" class="max-w-2xl">
    <h1 class="text-xl font-semibold text-navy-900">{{ s.submission_code }}</h1>
    <p class="text-sm text-slate-500 mb-4">{{ s.respondent?.full_name }} · {{ s.village }} · {{ s.activity_date }}</p>

    <div class="bg-white rounded-xl border shadow-sm p-5 mb-4 grid grid-cols-2 gap-3 text-sm">
      <div><span class="text-slate-400">Collected by</span><br />{{ s.collector?.name }}</div>
      <div><span class="text-slate-400">Vulnerability</span><br />{{ s.vulnerability_score }}/80 ({{ s.vulnerability_class }})</div>
      <div><span class="text-slate-400">Status</span><br />{{ s.status }}</div>
      <div><span class="text-slate-400">GPS</span><br />{{ s.gps_lat }}, {{ s.gps_lng }}</div>
    </div>

    <div v-if="s.consent" class="bg-white rounded-xl border shadow-sm p-5 mb-4">
      <h2 class="font-medium text-navy-900 mb-3">Consent evidence</h2>
      <div class="grid grid-cols-3 gap-3">
        <img v-if="s.consent.id_document_path" :src="mediaUrl(s.consent.id_document_path)" class="rounded-lg border" />
        <img v-if="s.consent.signature_path" :src="mediaUrl(s.consent.signature_path)" class="rounded-lg border bg-white" />
        <img v-if="s.consent.respondent_photo_path" :src="mediaUrl(s.consent.respondent_photo_path)" class="rounded-lg border" />
      </div>
      <audio v-if="s.consent.voice_note_path" :src="mediaUrl(s.consent.voice_note_path)" controls class="w-full mt-3"></audio>
    </div>

    <div v-if="auth.canReview" class="bg-white rounded-xl border shadow-sm p-5">
      <h2 class="font-medium text-navy-900 mb-3">Review</h2>
      <textarea v-model="notes" placeholder="Review notes (optional)" class="w-full rounded-lg border-slate-300 mb-3" rows="2"></textarea>
      <div class="flex gap-2">
        <button @click="review('reviewed')" class="text-sm bg-slate-100 rounded-lg px-3 py-2">Mark reviewed</button>
        <button @click="review('approved')" class="text-sm bg-green-700 text-white rounded-lg px-3 py-2">Approve</button>
        <button @click="review('flagged_duplicate')" class="text-sm bg-red-100 text-red-700 rounded-lg px-3 py-2">Flag duplicate</button>
      </div>
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
const s = ref(null)
const notes = ref('')
const apiBase = (import.meta.env.VITE_API_URL || 'http://localhost:8000/api').replace('/api', '')

function mediaUrl(path) { return `${apiBase}/storage/${path}` }

async function load() {
  const { data } = await api.get(`/submissions/${route.params.id}`)
  s.value = data
}

async function review(status) {
  await api.post(`/submissions/${route.params.id}/review`, { status, review_notes: notes.value })
  load()
}

onMounted(load)
</script>
