<template>
	<div class="min-h-screen bg-slate-100 text-slate-800 md:flex">
		<div v-if="mobileNavOpen" class="fixed inset-0 z-30 bg-slate-950/50 md:hidden" @click="mobileNavOpen = false"></div>
		<aside :style="sidebarStyle" :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-40 w-[min(84vw,18rem)] text-white shadow-2xl transition-transform duration-300 md:static md:z-auto md:min-h-screen md:w-72 md:translate-x-0 md:shadow-none">
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
				<button @click="mobileNavOpen = false" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs text-slate-200 hover:bg-white/5 md:hidden">Close</button>
			</div>

			<div v-if="auth.isSuperAdmin" class="border-b border-white/10 px-4 py-4">
				<label class="mb-2 block text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-300">Acting organisation</label>
				<select v-model="selectedOrgValue" @change="setSelectedOrg" class="w-full rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-sm text-white outline-none">
					<option value="" class="text-slate-900">All organisations</option>
					<option v-for="org in organizations" :key="org.id" :value="String(org.id)" class="text-slate-900">{{ org.name }}</option>
				</select>
				<div v-if="activeOrganizationName" class="mt-2 text-[11px] text-slate-200">{{ activeOrganizationName }}</div>
			</div>

			<nav class="flex flex-col gap-1 overflow-y-auto p-3 md:gap-2 md:p-4">
				<RouterLink v-for="item in nav" :key="item.to" :to="item.to" @click="mobileNavOpen = false" class="rounded-xl px-4 py-3 text-sm font-medium text-slate-200 hover:bg-white/5" active-class="bg-white/10 text-[#d9b15d] ring-1 ring-white/10">
					{{ item.label }}
				</RouterLink>
			</nav>

			<div class="hidden px-5 pb-6 md:block">
				<div v-if="activeOrganizationName" class="mb-3 text-xs text-slate-400">{{ activeOrganizationName }}</div>
				<button @click="logout" class="text-sm text-slate-300 hover:text-white">Sign out</button>
			</div>
		</aside>

		<main class="min-w-0 flex-1 p-4 pb-10 md:p-8">
			<button @click="mobileNavOpen = true" class="mb-4 flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm md:hidden"><span class="text-lg leading-none">☰</span> Menu</button>
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
const mobileNavOpen = ref(false)

const currentOrg = computed(() => organizations.value.find((org) => org.id === auth.selectedOrganizationId) || auth.user?.organization || null)
const currentOrgLogo = computed(() => currentOrg.value?.logo_url || null)
const currentAccentColor = computed(() => currentOrg.value?.secondary_color || '#d9b15d')
const sidebarStyle = computed(() => ({ background: `linear-gradient(180deg, ${currentOrg.value?.primary_color || '#0d1d2d'} 0%, ${currentOrg.value?.primary_color || '#0d1d2d'} 100%)` }))
const activeOrganizationName = computed(() => auth.isSuperAdmin && auth.selectedOrganizationId
	? organizations.value.find((org) => org.id === auth.selectedOrganizationId)?.name || null
	: auth.user?.organization?.name || null)
const roleLabel = computed(() => ({ super_admin: 'COTIA Platform Admin', ed: 'Executive Director', meo: 'M&E Officer', po: 'Project Officer', fo: 'Field Officer', customer_service: 'Customer Service', reader_manager: 'Reader Manager' }[auth.role] || auth.role))
const nav = computed(() => {
	if (auth.isSuperAdmin) return [
			{ to: '/app/organizations', label: 'Organizations' }, { to: '/app/projects', label: 'Projects' },
		{ to: '/app/users', label: 'Users' }, { to: '/app/reports', label: 'Reports' },
		{ to: '/app/admin/publications', label: 'Publications' }, { to: '/app/finance', label: 'Finance' }, { to: '/app/knowledge', label: 'Knowledge' }, { to: '/app/payments', label: 'Payments' }, { to: '/app/support', label: 'Help inbox' },
	]
	if (auth.role === 'reader_manager') return [{ to: '/app', label: 'Dashboard' }, { to: '/app/payments', label: 'Payments' }, { to: '/app/support', label: 'Help inbox' }]
	if (auth.role === 'customer_service') return [{ to: '/app', label: 'Dashboard' }, { to: '/app/support', label: 'Help inbox' }]
	const items = [
		{ to: '/app', label: 'Dashboard' }, { to: '/app/projects', label: 'Projects' }, { to: '/app/submissions', label: 'Data' },
		{ to: '/app/attendance', label: 'Attendance' }, { to: '/app/reports', label: 'Reports' },
	]
	if (auth.isEd || auth.role === 'meo') items.push({ to: '/app/admin/publications', label: 'Publications' }, { to: '/app/finance', label: 'Finance' }, { to: '/app/knowledge', label: 'Knowledge' })
	if (auth.isEd) items.push({ to: '/app/users', label: 'Users' })
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
watch(() => router.currentRoute.value.fullPath, () => { mobileNavOpen.value = false })
async function logout() { await auth.logout(); router.push({ name: 'login' }) }
onMounted(loadOrganizations)
</script>
