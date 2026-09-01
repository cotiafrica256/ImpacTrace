<template>
  <div>
    <canvas ref="canvas" class="border rounded-lg bg-white w-full touch-none" height="180"
      @pointerdown="start" @pointermove="move" @pointerup="end" @pointerleave="end"></canvas>
    <div class="flex justify-between mt-2">
      <button type="button" @click="clear" class="text-xs text-slate-500 underline">Clear</button>
      <span class="text-xs" :class="hasSignature ? 'text-green-600' : 'text-slate-400'">
        {{ hasSignature ? 'Signature captured' : 'Not signed yet' }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const emit = defineEmits(['captured'])
const canvas = ref(null)
let ctx, drawing = false
const hasSignature = ref(false)

onMounted(() => {
  const c = canvas.value
  c.width = c.offsetWidth
  ctx = c.getContext('2d')
  ctx.lineWidth = 2.2
  ctx.lineCap = 'round'
  ctx.strokeStyle = '#0b2545'
})

function pos(e) {
  const rect = canvas.value.getBoundingClientRect()
  return { x: e.clientX - rect.left, y: e.clientY - rect.top }
}

function start(e) {
  drawing = true
  const { x, y } = pos(e)
  ctx.beginPath()
  ctx.moveTo(x, y)
}
function move(e) {
  if (!drawing) return
  const { x, y } = pos(e)
  ctx.lineTo(x, y)
  ctx.stroke()
  hasSignature.value = true
}
function end() {
  if (!drawing) return
  drawing = false
  canvas.value.toBlob((blob) => emit('captured', blob), 'image/png')
}
function clear() {
  ctx.clearRect(0, 0, canvas.value.width, canvas.value.height)
  hasSignature.value = false
  emit('captured', null)
}

defineExpose({ clear })
</script>
