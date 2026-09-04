<template>
  <div class="min-h-screen bg-slate-50">
    <header class="bg-[#0d1d2d] text-white">
      <div class="mx-auto max-w-7xl px-5 py-6 flex items-center justify-between">
        <div><div class="text-xl font-bold">ImpacTrace Data Bank</div><div class="text-xs text-slate-300">Research · Evidence · Participation · Advocacy</div></div>
        <nav class="flex flex-wrap items-center justify-end gap-2">
          <a href="#plans" class="hidden text-sm text-slate-300 sm:inline">Plans</a>
          <a href="#issues" class="hidden text-sm text-slate-300 sm:inline">Issues</a>
          <a href="https://www.youtube.com/results?search_query=CodeToInnovate+Africa" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-red-300/40 px-4 py-2 text-sm text-red-100">Watch YouTube</a>
          <RouterLink to="/login" class="rounded-lg border border-white/20 px-4 py-2 text-sm">Organisation Login</RouterLink>
          <button @click="openAuth('login')" class="rounded-lg bg-[#d9b15d] px-4 py-2 text-sm font-semibold text-[#0d1d2d]">Sign in / Register</button>
        </nav>
      </div>
    </header>
    <main class="mx-auto max-w-7xl px-5 py-8">
      <div class="mb-8 rounded-2xl bg-white p-6 shadow-sm border">
        <h1 class="text-3xl font-bold text-slate-900">Research and evidence you can use</h1>
        <p class="mt-2 max-w-3xl text-slate-600">Explore published research, surveys, district development plans, community evidence and advocacy issues. Read the public summary, then unlock the full report when needed.</p>
        <div class="mt-5 flex gap-2"><input v-model="q" @keyup.enter="load" placeholder="Search reports, issues, districts..." class="flex-1 rounded-xl border px-4 py-3"/><button @click="load" class="rounded-xl bg-[#0d1d2d] px-5 text-white">Search</button></div>
      </div>
      <div v-if="loading" class="text-slate-500">Loading publications…</div>
      <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        <article v-for="p in items" :key="p.id" class="rounded-2xl bg-white border shadow-sm overflow-hidden">
          <div class="h-32 bg-slate-200" :style="p.cover_image ? {backgroundImage:`url(${p.cover_image})`,backgroundSize:'cover',backgroundPosition:'center'} : {}"></div>
          <div class="p-5"><div class="text-xs uppercase tracking-wider text-slate-500">{{ p.category || 'Research' }}</div><h2 class="mt-1 text-lg font-semibold">{{ p.title }}</h2><p class="mt-2 text-sm text-slate-600 line-clamp-3">{{ p.summary }}</p><RouterLink :to="`/publications/${p.slug}`" class="mt-4 inline-block font-semibold text-[#0d1d2d]">Read more →</RouterLink></div>
        </article>
      </div>
      <div v-if="!loading && !items.length" class="rounded-xl border bg-white p-8 text-center text-slate-500">No published reports matched your search.</div>
      <section id="plans" class="mt-12">
        <div class="flex items-end justify-between"><div><div class="text-xs font-semibold uppercase tracking-widest text-amber-700">Local planning</div><h2 class="mt-1 text-2xl font-bold text-slate-900">Development plans</h2></div><span class="text-sm text-slate-500">Public records</span></div>
        <div class="mt-4 grid gap-4 md:grid-cols-3"><article v-for="plan in plans" :key="plan.id" class="rounded-xl border bg-white p-5"><div class="text-xs text-slate-500">{{ plan.geography?.name || 'Community plan' }}</div><h3 class="mt-1 font-semibold">{{ plan.title }}</h3><p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ plan.content || 'Published local development plan.' }}</p></article></div>
        <div v-if="!plans.length" class="mt-4 rounded-xl border bg-white p-5 text-sm text-slate-500">Published development plans will appear here.</div>
      </section>
      <section id="issues" class="mt-12">
        <div class="flex items-end justify-between"><div><div class="text-xs font-semibold uppercase tracking-widest text-amber-700">Community voice</div><h2 class="mt-1 text-2xl font-bold text-slate-900">Issues in action</h2></div><span class="text-sm text-slate-500">Evidence for change</span></div>
        <div class="mt-4 grid gap-4 md:grid-cols-3"><article v-for="issue in issues" :key="issue.id" class="rounded-xl border bg-white p-5"><div class="text-xs uppercase text-emerald-700">{{ issue.status.replace('_', ' ') }}</div><h3 class="mt-1 font-semibold">{{ issue.title }}</h3><p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ issue.problem || issue.evidence || 'Community advocacy issue.' }}</p></article></div>
        <div v-if="!issues.length" class="mt-4 rounded-xl border bg-white p-5 text-sm text-slate-500">Published advocacy issues will appear here.</div>
      </section>
    </main>
    <div v-if="showRegister" class="fixed inset-0 bg-black/50 grid place-items-center p-5">
      <form @submit.prevent="register" class="w-full max-w-md rounded-2xl bg-white p-6">
        <h2 class="text-xl font-semibold">{{ authMode === 'login' ? 'Sign in to Data Bank' : 'Create reader account' }}</h2><input v-if="authMode === 'register'" v-model="form.name" required placeholder="Name" class="mt-4 w-full rounded-lg border p-3"/>
        <input v-model="form.email" type="email" required placeholder="Email" class="mt-3 w-full rounded-lg border p-3"/>
        <input v-model="form.phone" placeholder="Phone" class="mt-3 w-full rounded-lg border p-3"/>
        <input v-model="form.password" type="password" required minlength="8" placeholder="Password" class="mt-3 w-full rounded-lg border p-3"/>
        <input v-if="authMode === 'register'" v-model="form.password_confirmation" type="password" required placeholder="Confirm password" class="mt-3 w-full rounded-lg border p-3"/>
        <div class="mt-4 flex gap-2"><button class="flex-1 rounded-lg bg-[#0d1d2d] p-3 text-white">{{ authMode === 'login' ? 'Sign in' : 'Register' }}</button><button type="button" @click="showRegister=false" class="rounded-lg border px-4">Cancel</button></div>
        <button type="button" @click="authMode=authMode === 'login' ? 'register' : 'login'" class="mt-3 text-sm text-[#0d1d2d] underline">{{ authMode === 'login' ? 'Create a reader account' : 'I already have an account' }}</button>
      </form>
    </div>
  </div>
</template>
<script setup>
import {onMounted,ref} from 'vue'; import api from '../api/client'
const items=ref([]),plans=ref([]),issues=ref([]),q=ref(''),loading=ref(false),showRegister=ref(false),authMode=ref('login')
const form=ref({name:'',email:'',phone:'',password:'',password_confirmation:''})
function openAuth(mode){authMode.value=mode;showRegister.value=true}
async function load(){loading.value=true;try{const {data}=await api.get('/public/publications',{params:{q:q.value}});items.value=data.data||data}catch(e){items.value=[]}finally{loading.value=false}}
async function loadPublicRecords(){const [plansResponse,issuesResponse]=await Promise.all([api.get('/public/plans'),api.get('/public/issues')]);plans.value=plansResponse.data.data||plansResponse.data;issues.value=issuesResponse.data.data||issuesResponse.data}
async function register(){try{const endpoint=authMode.value === 'login' ? '/public/auth/login' : '/public/auth/register';const payload=authMode.value === 'login' ? {email:form.value.email,password:form.value.password}:form.value;const {data}=await api.post(endpoint,payload);localStorage.setItem('public_token',data.token);showRegister.value=false;alert(authMode.value === 'login' ? 'Signed in.' : 'Account created. You can now purchase reading access.')}catch(e){const errors=e.response?.data?.errors;const detail=errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message;alert(detail||'Authentication failed. Check your email and password, or create a reader account.')}}
onMounted(()=>{load();loadPublicRecords()})
</script>