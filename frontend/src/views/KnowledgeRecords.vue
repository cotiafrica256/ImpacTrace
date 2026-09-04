<template>
  <section>
    <div><p class="text-sm font-semibold uppercase tracking-widest text-amber-700">Participation</p><h1 class="mt-1 text-3xl font-bold text-slate-900">Knowledge records</h1><p class="mt-2 text-slate-600">Capture local plans, stakeholder conversations, and advocacy evidence.</p></div>
    <div class="mt-6 flex gap-2 overflow-x-auto border-b"><button v-for="item in tabs" :key="item.key" @click="tab=item.key" class="whitespace-nowrap border-b-2 px-3 py-3 text-sm font-semibold" :class="tab===item.key?'border-amber-600 text-slate-900':'border-transparent text-slate-500'">{{ item.label }}</button></div>
    <form @submit.prevent="save" class="mt-6 grid gap-3 rounded-2xl border bg-white p-5 md:grid-cols-2">
      <input v-model="form.title" required placeholder="Title" class="rounded-lg border p-3"/><input v-model="form.location" v-if="tab==='meetings'" placeholder="Location" class="rounded-lg border p-3"/>
      <input v-model="form.starts_at" v-if="tab==='meetings'" type="datetime-local" required class="rounded-lg border p-3"/><input v-model="form.target_decision_maker" v-if="tab==='issues'" placeholder="Decision maker" class="rounded-lg border p-3"/>
      <select v-model="form.status" v-if="tab==='issues'" class="rounded-lg border p-3"><option>identified</option><option>evidence_collected</option><option>engagement</option><option>action</option><option>resolved</option></select>
      <textarea v-model="form.content" v-if="tab==='plans'" placeholder="Plan content" class="min-h-32 rounded-lg border p-3 md:col-span-2"/><textarea v-model="form.problem" v-if="tab==='issues'" placeholder="Problem and evidence" class="min-h-32 rounded-lg border p-3 md:col-span-2"/><textarea v-model="form.agenda" v-if="tab==='meetings'" placeholder="Agenda, minutes, and action points" class="min-h-32 rounded-lg border p-3 md:col-span-2"/>
      <button class="rounded-lg bg-slate-900 px-4 py-3 text-white md:col-span-2">Save {{ tabLabel }}</button>
    </form>
    <div class="mt-5 space-y-3"><article v-for="record in records" :key="record.id" class="rounded-xl border bg-white p-5"><h2 class="font-semibold">{{ record.title }}</h2><p class="mt-1 whitespace-pre-wrap text-sm text-slate-600">{{ record.problem || record.content || record.agenda || record.location || '' }}</p></article></div>
  </section>
</template>
<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import api from '../api/client'
const tabs=[{key:'plans',label:'Development plans'},{key:'meetings',label:'Stakeholder meetings'},{key:'issues',label:'Advocacy issues'}]
const tab=ref('plans'),records=ref([]),form=ref({title:'',content:'',problem:'',agenda:'',location:'',starts_at:'',target_decision_maker:'',status:'identified'})
const tabLabel=computed(()=>tabs.find(item=>item.key===tab.value).label.toLowerCase())
async function load(){const {data}=await api.get(`/knowledge/${tab.value}`);records.value=data.data||data}
async function save(){const endpoint=tab.value==='plans'?'/knowledge/plans':`/knowledge/${tab.value}`;await api.post(endpoint,{...form.value});form.value={...form.value,title:'',content:'',problem:'',agenda:'',location:'',starts_at:'',target_decision_maker:''};await load()}
watch(tab,load);onMounted(load)
</script>
