import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../store/auth'

const routes = [
  { path: '/login', name: 'login', component: () => import('../views/Login.vue'), meta: { guest: true } },
  {
    path: '/', component: () => import('../views/AppLayout.vue'),
    children: [
      { path: '', name: 'dashboard', component: () => import('../views/Dashboard.vue') },
      // COTIA platform level — super_admin only, sits above every organisation.
      { path: 'organizations', name: 'organizations', component: () => import('../views/Organizations.vue'), meta: { roles: ['super_admin'] } },
      // Everything below belongs to one organisation and is off-limits to super_admin.
      { path: 'projects', name: 'projects', component: () => import('../views/Projects.vue'), meta: { roles: ['super_admin', 'ed', 'meo', 'po', 'fo'] } },
      { path: 'projects/:id', name: 'project-detail', component: () => import('../views/ProjectDetail.vue'), meta: { roles: ['super_admin', 'ed', 'meo', 'po', 'fo'] } },
      { path: 'collect/:formId', name: 'collect', component: () => import('../views/DataCollection.vue'), meta: { roles: ['super_admin', 'ed', 'meo', 'po', 'fo'] } },
      { path: 'submissions', name: 'submissions', component: () => import('../views/Submissions.vue'), meta: { roles: ['super_admin', 'ed', 'meo', 'po', 'fo'] } },
      { path: 'submissions/:id', name: 'submission-detail', component: () => import('../views/SubmissionDetail.vue'), meta: { roles: ['super_admin', 'ed', 'meo', 'po', 'fo'] } },
      { path: 'attendance', name: 'attendance', component: () => import('../views/Attendance.vue'), meta: { roles: ['super_admin', 'ed', 'meo', 'po', 'fo'] } },
      { path: 'reports', name: 'reports', component: () => import('../views/Reports.vue'), meta: { roles: ['super_admin', 'ed', 'meo', 'po', 'fo'] } },
      { path: 'users', name: 'users', component: () => import('../views/Users.vue'), meta: { roles: ['super_admin', 'ed'] } },
    ],
  },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.guest) return true
  if (!auth.isAuthenticated) return { name: 'login' }

  // super_admin belongs to no organisation, so send it straight to
  // Organizations rather than the org-oriented Dashboard.
  if (to.name === 'dashboard' && auth.isSuperAdmin) return { name: 'organizations' }

  if (to.meta.roles && !to.meta.roles.includes(auth.role)) {
    return auth.isSuperAdmin ? { name: 'organizations' } : { name: 'dashboard' }
  }
  return true
})

export default router
