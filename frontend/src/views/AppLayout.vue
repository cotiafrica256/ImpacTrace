<template>
	<div class="min-h-screen bg-slate-100 text-slate-800 md:flex">
		<aside :style="sidebarStyle" class="text-white md:min-h-screen md:w-72">
			<div class="flex items-center justify-between border-b border-white/10 px-5 py-4 md:block md:pb-6 md:pt-7">
				<div class="flex items-center gap-3 md:block">
					<div v-if="currentOrgLogo" class="mb-3 flex h-11 w-11 items-center justify-center overflow-hidden rounded-xl bg-white/10 md:mb-3">
						<img :src="currentOrgLogo" alt="Organisation logo" class="h-full w-full object-cover" />
					</div>
					<div v-else class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-700 text-lg font-bold" :style="{ color: currentAccentColor }">I</div>
					<div>
						<div class="text-lg font-semibold tracking-tight">ImpacTrace</div>
						<div class="text-[11px] text-slate-300 md:mt-1">{{ auth.user?.name }} · {{ roleLabel }}</div>
					</div>
				</div>
				<button @click="logout" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs text-slate-200 hover:bg-white/5 md:hidden">Sign out</button>
			</div>

			<div v-if="auth.isSuperAdmin" class="border-b border-white/10 px-4 py-4">
				<label class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-300">Acting organisation</label>
				<select v-model="selectedOrgValue" @change="setSelectedOrg" class="w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none">
					<option value="" class="text-slate-900">All organisations</option>
					<option v-for="org in organizations" :key="org.id" :value="String(org.id)" class="text-slate-900">{{ org.name }}</option>
				</select>
				<div v-if="activeOrganizationName" class="mt-2 text-[11px] text-slate-200">{{ activeOrganizationName }}</div>
			</div>

			<nav class="flex gap-1 overflow-x-auto p-2 md:flex-col md:gap-2 md:p-4">
				<RouterLink v-for="item in nav" :key="item.to" :to="item.to" class="whitespace-nowrap rounded-xl px-4 py-3 text-sm font-medium text-slate-200 hover:bg-white/5" active-class="bg-white/10 text-[#d9b15d] ring-1 ring-white/10">
					{{ item.label }}
				</RouterLink>
			</nav>

			<div class="hidden px-5 pb-6 md:block">
				<div v-if="activeOrganizationName" class="mb-3 text-xs text-slate-400">{{ activeOrganizationName }}</div>
				<button @click="logout" class="text-sm text-slate-300 hover:text-white">Sign out</button>
			</div>
		</aside>

		<main class="flex-1 p-4 pb-20 md:p-8 md:pb-10">
			<router-view />
		</main>
	</div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/client'
import { useAuthStore } from '../store/auth'

const auth = useAuthStore()
const router = useRouter()
const organizations = ref([])
const selectedOrgValue = ref(auth.selectedOrganizationId ? String(auth.selectedOrganizationId) : '')

const currentOrg = computed(() => organizations.value.find((org) => org.id === auth.selectedOrganizationId) || auth.user?.organization || null)
const currentOrgLogo = computed(() => currentOrg.value?.logo_url || null)
const currentAccentColor = computed(() => currentOrg.value?.secondary_color || '#d9b15d')
const sidebarStyle = computed(() => ({ background: `linear-gradient(180deg, ${currentOrg.value?.primary_color || '#0d1d2d'} 0%, ${currentOrg.value?.primary_color || '#0d1d2d'} 100%)` }))
const activeOrganizationName = computed(() => auth.isSuperAdmin && auth.selectedOrganizationId
	? organizations.value.find((org) => org.id === auth.selectedOrganizationId)?.name || null
	: auth.user?.organization?.name || null)
const roleLabel = computed(() => ({ super_admin: 'COTIA Platform Admin', ed: 'Executive Director', meo: 'M&E Officer', po: 'Project Officer', fo: 'Field Officer' }[auth.role] || auth.role))
const nav = computed(() => {
	if (auth.isSuperAdmin) return [
		{ to: '/organizations', label: 'Organizations' }, { to: '/projects', label: 'Projects' },
		{ to: '/users', label: 'Users' }, { to: '/reports', label: 'Reports' },
		{ to: '/admin/publications', label: 'Publications' }, { to: '/finance', label: 'Finance' }, { to: '/knowledge', label: 'Knowledge' }, { to: '/payments', label: 'Payments' },
	]
	const items = [
		{ to: '/', label: 'Dashboard' }, { to: '/projects', label: 'Projects' }, { to: '/submissions', label: 'Data' },
		{ to: '/attendance', label: 'Attendance' }, { to: '/reports', label: 'Reports' },
	]
	if (auth.isEd || auth.role === 'meo') items.push({ to: '/admin/publications', label: 'Publications' }, { to: '/finance', label: 'Finance' }, { to: '/knowledge', label: 'Knowledge' })
	if (auth.isEd) items.push({ to: '/users', label: 'Users' })
	return items
})

async function loadOrganizations() {
	if (!auth.isSuperAdmin) return
	const { data } = await api.get('/organizations')
	organizations.value = data
	if (!auth.selectedOrganizationId && data[0]) {
		auth.setSelectedOrganization(data[0].id)
		selectedOrgValue.value = String(data[0].id)
	}
}
function setSelectedOrg(event) {
	const value = event.target.value
	auth.setSelectedOrganization(value || null)
	selectedOrgValue.value = value
}
watch(() => auth.selectedOrganizationId, (value) => { selectedOrgValue.value = value ? String(value) : '' })
async function logout() { await auth.logout(); router.push({ name: 'login' }) }
onMounted(loadOrganizations)
</script>
