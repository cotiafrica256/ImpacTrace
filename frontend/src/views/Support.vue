<template>
  <section>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-widest text-emerald-700">Service desk</p><h1 class="mt-1 text-3xl font-bold text-slate-900">Help inbox</h1><p class="mt-2 text-slate-600">Respond to reader questions about access and payments.</p></div><input v-model="search" @keyup.enter="load" placeholder="Search messages" class="text-sm" /></div>
    <div class="grid gap-4"> <article v-for="item in requests" :key="item.id" class="rounded-2xl border bg-white p-5 shadow-sm"><div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-semibold text-slate-900">{{ item.subject || 'Reader question' }}</h2><p class="mt-1 text-sm text-slate-500">{{ item.name }} · {{ item.email }}</p></div><select v-model="item.status" class="text-sm"><option value="open">Open</option><option value="in_progress">In progress</option><option value="resolved">Resolved</option></select></div><p class="mt-4 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ item.message }}</p><textarea v-model="item.response" rows="3" placeholder="Write a response" class="mt-4 w-full text-sm"></textarea><button @click="respond(item)" class="mt-3 rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white">Send response</button></article></div><p v-if="!requests.length" class="rounded-2xl border bg-white p-8 text-center text-slate-500">No support messages found.</p>
  </section>
</template>
<script setup>
import { onMounted, ref } from 'vue'
import api from '../api/client'
const requests = ref([]); const search = ref('')
async function load () { const { data } = await api.get('/support', { params: { q: search.value || undefined } }); requests.value = data.data || data }
async function respond (item) { await api.put(`/support/${item.id}`, { status: item.status, response: item.response }); await load() }
onMounted(load)
</script>
