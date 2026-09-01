<template>
  <div v-if="form" class="mx-auto max-w-3xl pb-24">
    <div class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-slate-800">{{ form.title }}</h1>
          <p class="text-xs text-slate-500">Section {{ current + 1 }} of {{ form.form_schema.sections.length }}: {{ section.title }}</p>
        </div>
        <div class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">
          Recorded at {{ recordedAt }}
        </div>
      </div>
      <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-200">
        <div class="h-full rounded-full bg-[#0d1d2d] transition-all" :style="{ width: progress + '%' }"></div>
      </div>
    </div>

    <div v-if="previewMode" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-800">Form preview</h2>
        <button type="button" @click="previewMode = false" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-100">Back to editing</button>
      </div>
      <div class="space-y-4 text-sm text-slate-700">
        <div v-for="(value, key) in previewSummary" :key="key" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
          <div class="font-medium text-slate-600">{{ key }}</div>
          <div class="mt-1 text-slate-800">{{ value || 'Not answered yet' }}</div>
        </div>
      </div>
    </div>

    <div v-else class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="space-y-5">
        <template v-for="field in section.fields" :key="field.id">
          <div v-if="field.type !== 'consent_statement'">
            <label class="mb-2 block text-sm font-medium text-slate-700">{{ field.label }}</label>

            <input v-if="['text','number','date'].includes(field.type)" v-model="answers[field.id]" :type="field.type" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm outline-none transition focus:border-[#0d1d2d] focus:ring-2 focus:ring-[#0d1d2d]/10" />

            <textarea v-else-if="field.type === 'textarea'" v-model="answers[field.id]" rows="3" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm outline-none transition focus:border-[#0d1d2d] focus:ring-2 focus:ring-[#0d1d2d]/10"></textarea>

            <div v-else-if="field.type === 'select'" class="space-y-2">
              <button
                v-for="o in field.options"
                :key="o"
                type="button"
                @click="answers[field.id] = o"
                class="w-full rounded-xl border px-3 py-3 text-left text-sm font-medium transition-all duration-150 shadow-sm"
                :class="answers[field.id] === o
                  ? 'border-[#0d1d2d] bg-[#0d1d2d] text-white ring-2 ring-[#0d1d2d]/10'
                  : 'border-slate-300 bg-white text-slate-700 hover:border-slate-400 hover:bg-slate-50'"
              >
                {{ o }}
              </button>
            </div>

            <div v-else-if="field.type === 'multi_select'" class="space-y-2">
              <button
                v-for="o in field.options"
                :key="o"
                type="button"
                @click="toggleMultiSelect(field.id, o)"
                class="w-full rounded-xl border px-3 py-3 text-left text-sm font-medium transition-all duration-150 shadow-sm"
                :class="(answers[field.id] || []).includes(o)
                  ? 'border-[#0d1d2d] bg-[#0d1d2d] text-white ring-2 ring-[#0d1d2d]/10'
                  : 'border-slate-300 bg-white text-slate-700 hover:border-slate-400 hover:bg-slate-50'"
              >
                {{ o }}
              </button>
            </div>

            <div v-else-if="field.type === 'boolean_yes_no'" class="space-y-2">
              <button type="button" v-for="opt in ['Yes','No']" :key="opt" @click="answers[field.id] = opt"
                class="w-full rounded-xl border px-3 py-3 text-left text-sm font-medium shadow-sm transition"
                :class="answers[field.id]===opt ? 'border-[#0d1d2d] bg-[#0d1d2d] text-white ring-2 ring-[#0d1d2d]/10' : 'border-slate-300 bg-white text-slate-700 hover:border-slate-400 hover:bg-slate-50'">
                {{ opt }}
              </button>
            </div>

            <div v-else-if="field.type === 'gps'" class="space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3">
              <button type="button" @click="captureGps" class="rounded-xl bg-[#0d1d2d] px-4 py-2 text-sm font-medium text-white hover:bg-[#16324c]">Capture GPS</button>
              <div v-if="gps" class="text-sm text-slate-600">{{ gps.lat.toFixed(5) }}, {{ gps.lng.toFixed(5) }}</div>
              <div v-else class="text-sm text-slate-500">Location not captured.</div>
            </div>

            <div v-else-if="field.type === 'rating_table'" class="space-y-2">
              <div v-for="row in field.rows" :key="row" class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm">
                <span class="text-slate-700">{{ row }}</span>
                <select v-model.number="ratings[row]" class="w-24 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm outline-none transition focus:border-[#0d1d2d] focus:ring-2 focus:ring-[#0d1d2d]/10">
                  <option v-for="n in field.scale" :key="n" :value="n">{{ n }}</option>
                </select>
              </div>
            </div>

            <div v-else-if="field.type === 'computed'" class="text-lg font-semibold text-slate-800">
              {{ totalScore }} / {{ maxScore }}
            </div>

            <CameraCapture v-else-if="field.type === 'id_capture'" label="Scan or photograph the respondent's ID card." @captured="(b) => idFile = b" />
            <SignaturePad v-else-if="field.type === 'signature_pad'" @captured="(b) => (field.id === 't_signature' ? signatureFile = b : interviewerSignature = b)" />
            <CameraCapture v-else-if="field.type === 'photo_capture'" label="Take the respondent's photo." @captured="(b) => photoFile = b" />
            <VoiceRecorder v-else-if="field.type === 'voice_recorder'" @captured="(b) => voiceFile = b" />
            <input v-else-if="field.type === 'multi_photo_capture'" type="file" accept="image/*" multiple @change="onExtraPhotos" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm outline-none transition focus:border-[#0d1d2d] focus:ring-2 focus:ring-[#0d1d2d]/10" />

            <input v-else v-model="answers[field.id]" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 shadow-sm outline-none transition focus:border-[#0d1d2d] focus:ring-2 focus:ring-[#0d1d2d]/10" />
          </div>

          <div v-else class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
            {{ field.text }}
          </div>
        </template>

        <div v-if="section.consent_section && dupResult" class="rounded-xl border p-3 text-sm"
          :class="dupResult.status === 'exact_duplicate' ? 'border-red-200 bg-red-50 text-red-700' : dupResult.status === 'possible_duplicate' ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-green-200 bg-green-50 text-green-700'">
          <template v-if="dupResult.status === 'exact_duplicate'">
            This ID is already registered as {{ dupResult.respondent.full_name }} ({{ dupResult.respondent.respondent_code }}). This submission cannot proceed.
          </template>
          <template v-else-if="dupResult.status === 'possible_duplicate'">
            A very similar person already exists: {{ dupResult.respondent.full_name }}. If this is genuinely a different person, tick below to continue.
            <label class="mt-2 flex items-center gap-2">
              <input type="checkbox" v-model="overrideDuplicate" /> This is a different person
            </label>
          </template>
          <template v-else>No existing record found — clear to proceed.</template>
        </div>

        <button v-if="section.consent_section" type="button" @click="runDuplicateCheck" class="text-sm font-medium text-[#0d1d2d] underline">
          Re-check for duplicate
        </button>
      </div>
    </div>

    <div class="mt-5 flex items-center justify-between gap-3">
      <button v-if="current > 0" @click="current--" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100">Back</button>
      <div v-else class="flex-1"></div>
      <div class="flex items-center gap-2">
        <button type="button" @click="saveDraft" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100">Save draft</button>
        <button type="button" @click="previewMode = true" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100">Preview</button>
        <button v-if="current < form.form_schema.sections.length - 1" @click="next" class="rounded-xl bg-[#0d1d2d] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#16324c]">Next</button>
        <button v-else @click="submit" :disabled="submitting || blocked" class="rounded-xl bg-[#0f7b4f] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#0a6440] disabled:cursor-not-allowed disabled:opacity-60">
          {{ submitting ? 'Saving…' : 'Submit' }}
        </button>
      </div>
    </div>

    <p v-if="draftStatus" class="mt-3 text-sm text-slate-500">{{ draftStatus }}</p>
    <p v-if="submitError" class="mt-3 text-sm text-red-600">{{ submitError }}</p>
    <p v-if="submitted" class="mt-3 text-sm text-green-700">Saved as {{ submitted.submission_code }}. You can start a new entry from the dashboard.</p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api/client'
import { useAuthStore } from '../store/auth'
import SignaturePad from '../components/SignaturePad.vue'
import CameraCapture from '../components/CameraCapture.vue'
import VoiceRecorder from '../components/VoiceRecorder.vue'
import { queueSubmission, readOfflineQueue, rebuildFormDataFromQueue, writeOfflineQueue } from '../utils/offlineQueue'

const route = useRoute()
const auth = useAuthStore()
const form = ref(null)
const current = ref(0)
const answers = ref({})
const ratings = ref({})
const gps = ref(null)
const recordedAt = ref(new Date().toLocaleString())
const previewMode = ref(false)
const draftStatus = ref('')

const idFile = ref(null)
const signatureFile = ref(null)
const interviewerSignature = ref(null)
const photoFile = ref(null)
const voiceFile = ref(null)
const extraPhotos = ref([])

const dupResult = ref(null)
const overrideDuplicate = ref(false)
const submitting = ref(false)
const submitError = ref('')
const submitted = ref(null)

const section = computed(() => form.value?.form_schema?.sections?.[current.value] || { fields: [] })
const progress = computed(() => form.value && form.value.form_schema.sections.length ? Math.round(((current.value + 1) / form.value.form_schema.sections.length) * 100) : 0)

const maxScore = computed(() => {
  const ratingField = form.value?.form_schema.sections.flatMap((s) => s.fields).find((f) => f.type === 'rating_table')
  return ratingField?.max_score || 0
})
const totalScore = computed(() => Object.values(ratings.value).reduce((a, b) => a + (b || 0), 0))
const vulnerabilityClass = computed(() => {
  const pct = maxScore.value ? totalScore.value / maxScore.value : 0
  if (pct >= 0.8) return 'Very High'
  if (pct >= 0.6) return 'High'
  if (pct >= 0.4) return 'Moderate'
  if (pct >= 0.2) return 'Low'
  return 'Very Low'
})
const previewSummary = computed(() => {
  const summary = {}
  Object.entries(answers.value).forEach(([key, value]) => {
    summary[key] = Array.isArray(value) ? value.join(', ') : value || 'Not answered yet'
  })
  if (gps.value) summary['GPS location'] = `${gps.value.lat.toFixed(5)}, ${gps.value.lng.toFixed(5)}`
  if (Object.keys(ratings.value).length) summary['Rating summary'] = JSON.stringify(ratings.value)
  return summary
})

const blocked = computed(() => dupResult.value?.status === 'exact_duplicate')

function toggleMultiSelect(fieldId, option) {
  const current = Array.isArray(answers.value[fieldId]) ? [...answers.value[fieldId]] : []
  const index = current.indexOf(option)

  if (index >= 0) {
    current.splice(index, 1)
  } else {
    current.push(option)
  }

  answers.value[fieldId] = current
}

function buildSubmissionPayload() {
  return {
    project_id: form.value.project_id,
    project_form_id: form.value.id,
    activity_date: answers.value['a1_date'],
    village: answers.value['a1_village'] || '',
    parish: answers.value['a1_parish'] || '',
    sub_county: answers.value['a1_subcounty'] || '',
    district: answers.value['a1_district'] || '',
    gps_lat: gps.value?.lat ?? '',
    gps_lng: gps.value?.lng ?? '',
    answers: { ...answers.value, ratings: ratings.value },
    vulnerability_score: totalScore.value,
    vulnerability_class: vulnerabilityClass.value,
    respondent: {
      full_name: answers.value['a2_name'] || '',
      sex: (answers.value['a2_sex'] || '').toLowerCase(),
      age: answers.value['a2_age'] || '',
      id_number: answers.value['respondent_id_number'] || '',
      village: answers.value['a1_village'] || '',
      parish: answers.value['a1_parish'] || '',
      sub_county: answers.value['a1_subcounty'] || '',
      district: answers.value['a1_district'] || '',
    },
    override_duplicate: overrideDuplicate.value ? 1 : 0,
    consent: {
      consent_given: answers.value['t_consent_given'] === 'Yes' ? 1 : 0,
      permission_for_learning_advocacy: answers.value['t_permission_learning'] === 'Yes' ? 1 : 0,
      permission_for_photos: answers.value['t_permission_photos'] === 'Yes' ? 1 : 0,
    },
    files: {
      id_document: idFile.value,
      signature: signatureFile.value,
      respondent_photo: photoFile.value,
      voice_note: voiceFile.value,
      extra_photos: extraPhotos.value,
    },
  }
}

function buildSubmissionFormData(payload) {
  const fd = new FormData()
  fd.append('project_id', payload.project_id)
  fd.append('project_form_id', payload.project_form_id)
  fd.append('activity_date', payload.activity_date)
  fd.append('village', payload.village)
  fd.append('parish', payload.parish)
  fd.append('sub_county', payload.sub_county)
  fd.append('district', payload.district)
  if (payload.gps_lat !== '' && payload.gps_lng !== '') {
    fd.append('gps_lat', payload.gps_lat)
    fd.append('gps_lng', payload.gps_lng)
  }
  fd.append('answers', JSON.stringify(payload.answers))
  fd.append('vulnerability_score', payload.vulnerability_score)
  fd.append('vulnerability_class', payload.vulnerability_class)

  fd.append('respondent[full_name]', payload.respondent.full_name)
  fd.append('respondent[sex]', payload.respondent.sex)
  fd.append('respondent[age]', payload.respondent.age)
  fd.append('respondent[id_number]', payload.respondent.id_number)
  fd.append('respondent[village]', payload.respondent.village)
  fd.append('respondent[parish]', payload.respondent.parish)
  fd.append('respondent[sub_county]', payload.respondent.sub_county)
  fd.append('respondent[district]', payload.respondent.district)

  fd.append('override_duplicate', payload.override_duplicate)
  fd.append('consent[consent_given]', payload.consent.consent_given)
  fd.append('consent[permission_for_learning_advocacy]', payload.consent.permission_for_learning_advocacy)
  fd.append('consent[permission_for_photos]', payload.consent.permission_for_photos)

  if (payload.files.id_document) fd.append('id_document', payload.files.id_document, 'id.jpg')
  if (payload.files.signature) fd.append('signature', payload.files.signature, 'signature.png')
  if (payload.files.respondent_photo) fd.append('respondent_photo', payload.files.respondent_photo, 'photo.jpg')
  if (payload.files.voice_note) fd.append('voice_note', payload.files.voice_note, 'voice.webm')
  ;(payload.files.extra_photos || []).forEach((f) => fd.append('extra_photos[]', f))

  return fd
}

async function flushOfflineQueue() {
  if (!navigator.onLine) return

  const queue = readOfflineQueue()
  if (!queue.length) return

  const remaining = []
  for (const item of queue) {
    try {
      const fd = rebuildFormDataFromQueue(item)
      await api.post('/submissions', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    } catch (error) {
      remaining.push(item)
    }
  }

  if (remaining.length !== queue.length) {
    draftStatus.value = 'Offline form(s) uploaded successfully.'
  }
  writeOfflineQueue(remaining)
}

onMounted(async () => {
  const { data } = await api.get(`/forms/${route.params.formId}`)
  form.value = data
  answers.value['a1_date'] = new Date().toISOString().slice(0, 10)
  recordedAt.value = new Date().toLocaleString()

  const savedDraft = localStorage.getItem(`impactrace-draft-${route.params.formId}`)
  if (savedDraft) {
    try {
      const draft = JSON.parse(savedDraft)
      Object.assign(answers.value, draft.answers || {})
      Object.assign(ratings.value, draft.ratings || {})
      if (draft.gps) gps.value = draft.gps
      draftStatus.value = `Draft restored from ${draft.savedAt}`
    } catch (error) {
      console.warn('Unable to restore draft', error)
    }
  }

  flushOfflineQueue()
  window.addEventListener('online', flushOfflineQueue)
})

onBeforeUnmount(() => window.removeEventListener('online', flushOfflineQueue))

function saveDraft() {
  const payload = {
    savedAt: new Date().toLocaleString(),
    answers: answers.value,
    ratings: ratings.value,
    gps: gps.value,
  }
  localStorage.setItem(`impactrace-draft-${route.params.formId}`, JSON.stringify(payload))
  recordedAt.value = new Date().toLocaleString()
  draftStatus.value = `Draft saved at ${payload.savedAt}`
}

function captureGps() {
  if (!navigator.geolocation) {
    submitError.value = 'GPS is not available in this browser.'
    return
  }

  navigator.geolocation.getCurrentPosition(
    (pos) => {
      gps.value = { lat: pos.coords.latitude, lng: pos.coords.longitude }
      saveDraft()
      submitError.value = ''
    },
    () => {
      submitError.value = 'GPS could not be captured. Please check location permissions and try again.'
    },
    { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
  )
}

function onExtraPhotos(e) {
  extraPhotos.value = Array.from(e.target.files)
}

async function runDuplicateCheck() {
  const idNumber = answers.value['respondent_id_number'] || null
  const fullName = answers.value['a2_name']
  if (!fullName) return
  const { data } = await api.post('/submissions/check-duplicate', {
    id_number: idNumber,
    full_name: fullName,
    age_or_dob: answers.value['a2_age'],
    village: answers.value['a1_village'],
  })
  dupResult.value = data
}

function next() {
  if (section.value.consent_section) runDuplicateCheck()
  current.value++
  saveDraft()
}

async function submit() {
  submitting.value = true
  submitError.value = ''

  const payload = buildSubmissionPayload()

  try {
    if (!navigator.onLine) {
      const queuedCount = await queueSubmission(payload)
      submitError.value = `Network unavailable. Your form is saved offline and will upload automatically when connection returns (${queuedCount} queued).`
      saveDraft()
      return
    }

    const fd = buildSubmissionFormData(payload)
    const { data } = await api.post('/submissions', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    submitted.value = data
    localStorage.removeItem(`impactrace-draft-${route.params.formId}`)
    draftStatus.value = 'Draft cleared after successful submission.'
  } catch (e) {
    if (!navigator.onLine || !e.response) {
      const queuedCount = await queueSubmission(payload)
      submitError.value = `No connection detected. The form has been queued for upload when the network is back (${queuedCount} queued).`
      saveDraft()
      return
    }

    submitError.value = e.response?.data?.message || 'Could not save this entry. Check your connection and try again.'
    saveDraft()
  } finally {
    submitting.value = false
  }
}
</script>
