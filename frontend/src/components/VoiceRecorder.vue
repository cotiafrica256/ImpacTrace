<template>
  <div class="rounded-xl border border-slate-300 bg-slate-50 p-3">
    <div class="flex items-center gap-3">
      <button type="button" @click="toggle" class="flex h-12 w-12 items-center justify-center rounded-full text-sm font-semibold text-white shadow-md"
        :class="recording ? 'bg-red-600' : 'bg-[#0d1d2d]'">
        {{ recording ? 'Stop' : 'Record' }}
      </button>
      <span class="text-sm text-slate-600">
        {{ recording ? 'Recording: ' + elapsed + 's' : (audioUrl ? 'Recorded: ' + elapsed + 's' : 'Tap to record a voice note (optional)') }}
      </span>
    </div>
    <p v-if="error" class="mt-2 text-xs text-red-600">{{ error }}</p>
    <audio v-if="audioUrl" :src="audioUrl" controls class="mt-3 w-full"></audio>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const emit = defineEmits(['captured'])
const recording = ref(false)
const audioUrl = ref(null)
const elapsed = ref(0)
const error = ref('')
let mediaRecorder, chunks = [], timer

async function toggle() {
  if (recording.value) {
    mediaRecorder?.stop()
    return
  }

  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.MediaRecorder) {
    error.value = 'Microphone recording is not supported in this browser.'
    return
  }

  try {
    error.value = ''
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    mediaRecorder = new MediaRecorder(stream)
    chunks = []
    elapsed.value = 0

    mediaRecorder.ondataavailable = (e) => chunks.push(e.data)
    mediaRecorder.onstop = () => {
      const blob = new Blob(chunks, { type: 'audio/webm' })
      audioUrl.value = URL.createObjectURL(blob)
      emit('captured', blob)
      stream.getTracks().forEach((track) => track.stop())
      clearInterval(timer)
      recording.value = false
    }

    mediaRecorder.start()
    recording.value = true
    timer = setInterval(() => {
      elapsed.value += 1
    }, 1000)
  } catch (e) {
    error.value = 'Microphone permission was denied. You can continue without a voice note.'
  }
}
</script>
