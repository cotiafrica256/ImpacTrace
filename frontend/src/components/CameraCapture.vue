<template>
  <div>
    <div v-if="!preview" class="rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-4 text-center">
      <video v-if="streaming" ref="video" autoplay playsinline class="mb-3 w-full rounded-xl object-cover"></video>
      <div class="flex flex-wrap justify-center gap-2">
        <button v-if="!streaming" type="button" @click="startCamera" class="rounded-xl bg-[#0d1d2d] px-4 py-2 text-sm font-medium text-white hover:bg-[#16324c]">
          Open camera
        </button>
        <button v-if="streaming" type="button" @click="capture" class="rounded-xl bg-[#0d1d2d] px-4 py-2 text-sm font-medium text-white hover:bg-[#16324c]">
          Take photo
        </button>
        <label class="cursor-pointer rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
          Upload instead
          <input type="file" accept="image/*" class="hidden" @change="onFile" />
        </label>
      </div>
      <p v-if="error" class="mt-3 text-xs text-red-600">{{ error }}</p>
      <p class="mt-2 text-xs text-slate-500">{{ label }}</p>
    </div>
    <div v-else class="relative">
      <img :src="preview" class="w-full rounded-xl object-cover" />
      <button type="button" @click="reset" class="absolute right-2 top-2 rounded-full bg-black/60 px-2 py-1 text-xs text-white">Retake</button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({ label: { type: String, default: 'Works with a phone camera or a desk scanner (upload the scan).' } })
const emit = defineEmits(['captured'])

const video = ref(null)
const streaming = ref(false)
const preview = ref(null)
const error = ref('')
let stream = null

async function startCamera() {
  error.value = ''
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    error.value = 'Camera access is not available in this browser.'
    return
  }

  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
    streaming.value = true
    setTimeout(() => { if (video.value) video.value.srcObject = stream }, 0)
  } catch (e) {
    error.value = 'Camera permission was denied. You can still upload a photo instead.'
  }
}

function capture() {
  const c = document.createElement('canvas')
  c.width = video.value.videoWidth
  c.height = video.value.videoHeight
  c.getContext('2d').drawImage(video.value, 0, 0)
  c.toBlob((blob) => {
    preview.value = URL.createObjectURL(blob)
    emit('captured', blob)
    stopCamera()
  }, 'image/jpeg', 0.9)
}

function stopCamera() {
  stream?.getTracks().forEach((t) => t.stop())
  streaming.value = false
}

function onFile(e) {
  const file = e.target.files[0]
  if (!file) return
  preview.value = URL.createObjectURL(file)
  emit('captured', file)
}

function reset() {
  preview.value = null
  emit('captured', null)
}
</script>
