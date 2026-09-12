<template>
  <div class="min-h-screen bg-[#eef5f1] text-slate-900">
    <header class="bg-[#123f31] px-5 py-5 text-white"><div class="mx-auto flex max-w-6xl items-center justify-between gap-4"><RouterLink to="/" class="text-lg font-semibold">ImpacTrace Knowledge Hub</RouterLink><RouterLink to="/" class="text-sm text-emerald-100">Back to hub</RouterLink></div></header>
    <main class="mx-auto max-w-6xl px-5 py-10"><div class="max-w-2xl"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Support local action</p><h1 class="mt-2 text-4xl font-bold text-slate-900">Choose a cause to support</h1><p class="mt-3 text-lg leading-7 text-slate-600">Every contribution helps organisations turn community priorities into action.</p></div><div v-if="loading" class="mt-8 text-slate-500">Loading causes...</div><div v-else-if="!campaigns.length" class="mt-8 rounded-2xl border border-dashed bg-white p-10 text-center text-slate-500">No fundraising campaigns are published yet.</div><div v-else class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3"><article v-for="campaign in campaigns" :key="campaign.id" class="overflow-hidden rounded-2xl border border-amber-100 bg-white shadow-sm"><img v-if="campaign.photo_url" :src="campaign.photo_url" :alt="campaign.title" class="h-44 w-full object-cover" /><div class="p-5"><div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">{{ campaign.organization?.name }}</div><h2 class="mt-2 text-xl font-semibold">{{ campaign.title }}</h2><p class="mt-2 line-clamp-4 text-sm leading-6 text-slate-600">{{ campaign.summary }}</p><RouterLink :to="`/donate/${campaign.slug}`" class="mt-5 inline-flex w-full items-center justify-center rounded-xl bg-[#176b4d] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#10573e]">Donate to this cause</RouterLink></div></article></div></main>
  </div>
</template>
<script setup>
import { onMounted, ref } from 'vue'
import publicApi from '../api/publicClient'
const campaigns = ref([]), loading = ref(true)
async function load() { try { const { data } = await publicApi.get('/public/fundraisers'); campaigns.value = data.data || data } finally { loading.value = false } }
onMounted(load)
</script>
