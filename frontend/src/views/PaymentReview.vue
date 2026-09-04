<template>
  <section>
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div><p class="text-sm font-semibold uppercase tracking-widest text-amber-700">Revenue</p><h1 class="mt-1 text-3xl font-bold text-slate-900">Payment review</h1><p class="mt-2 text-slate-600">Verify manual MoMo references and activate paid access.</p></div>
      <div class="flex gap-2"><input v-model="search" @keyup.enter="load" placeholder="Search reader or phone" class="text-sm" /><button @click="load" class="rounded-lg border px-4 py-2 text-sm">Refresh</button></div>
    </div>
    <div class="mt-6 overflow-hidden rounded-2xl border bg-white">
      <div v-if="!payments.length" class="p-8 text-center text-slate-500">No pending payments.</div>
      <div v-for="payment in payments" :key="payment.id" class="grid gap-3 border-b p-5 md:grid-cols-[1fr_1fr_auto] md:items-center">
        <div><div class="font-semibold">{{ payment.user?.name || 'Reader' }}</div><div class="text-sm text-slate-500">{{ payment.user?.phone || payment.phone }} · {{ payment.package?.publication?.title }}</div></div>
        <div><div class="font-semibold">UGX {{ Number(payment.amount_ugx).toLocaleString() }}</div><div class="text-sm text-slate-500">{{ payment.method }} · Ref {{ payment.last5_reference || 'not submitted' }}</div></div>
        <div class="flex gap-2"><button @click="verify(payment, 'paid')" class="rounded-lg bg-emerald-700 px-3 py-2 text-sm text-white">Approve</button><button @click="verify(payment, 'rejected')" class="rounded-lg border border-red-200 px-3 py-2 text-sm text-red-700">Reject</button></div>
      </div>
    </div>
  </section>
</template>
<script setup>
import { onMounted, ref } from 'vue'
import api from '../api/client'
const payments = ref([])
const search = ref('')
async function load () { const { data } = await api.get('/admin/payments/pending', { params: { q: search.value || undefined } }); payments.value = data.data || data }
async function verify (payment, status) { await api.post(`/admin/payments/${payment.id}/verify`, { status, provider_reference: payment.last5_reference }); payments.value = payments.value.filter((item) => item.id !== payment.id) }
onMounted(load)
</script>
