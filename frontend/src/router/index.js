import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../store/auth'

const routes = [
  { path: '/publications', name: 'publications-home', component: () => import('../views/PublicHome.vue'), meta: { public: true } },
  { path: '/publications/:slug', name: 'public-publication', component: () => import('../views/PublicPublication.vue'), meta: { public: true } },
  { path: '/login', name: 'login', component: () => import('../views/Login.vue'), meta: { guest: true } },
  {
    path: '/', component: () => import('../views/AppLayout.vue'),
    children: [
      { path: '', name: 'dashboard', component: () => import('../views/Dashboard.vue') },
      { path: 'organizations', name: 'organizations', component: () => import('../views/Organizations.vue'), meta: { roles: ['super_admin'] } },
      { path: 'projects', name: 'projects', component: () => import('../views/Projects.vue'), meta: { roles: ['super_admin', 'ed', 'meo', 'po', 'fo'] } },
      { path: 'projects/:id', name: 'project-detail', component: () => import('../views/ProjectDetail.vue'), meta: { roles: ['super_admin', 'ed', 'meo', 'po', 'fo'] } },
      { path: 'collect/:formId', name: 'collect', component: () => import('../views/DataCollection.vue'), meta: { roles: ['super_admin', 'ed', 'meo', 'po', 'fo'] } },
      { path: 'submissions', name: 'submissions', component: () => import('../views/Submissions.vue'), meta: { roles: ['super_admin', 'ed', 'meo', 'po', 'fo'] } },
      { path: 'submissions/:id', name: 'submission-detail', component: () => import('../views/SubmissionDetail.vue'), meta: { roles: ['super_admin', 'ed', 'meo', 'po', 'fo'] } },
      { path: 'attendance', name: 'attendance', component: () => import('../views/Attendance.vue'), meta: { roles: ['super_admin', 'ed', 'meo', 'po', 'fo'] } },
      { path: 'reports', name: 'reports', component: () => import('../views/Reports.vue'), meta: { roles: ['super_admin', 'ed', 'meo', 'po', 'fo'] } },
      { path: 'admin/publications', name: 'publications-admin', component: () => import('../views/PublicationsAdmin.vue'), meta: { roles: ['super_admin','ed','meo'] } },
      { path: 'finance', name: 'finance', component: () => import('../views/Finance.vue'), meta: { roles: ['super_admin','ed','meo'] } },
      { path: 'knowledge', name: 'knowledge', component: () => import('../views/KnowledgeRecords.vue'), meta: { roles: ['super_admin','ed','meo'] } },
      { path: 'payments', name: 'payments', component: () => import('../views/PaymentReview.vue'), meta: { roles: ['super_admin'] } },
      { path: 'users', name: 'users', component: () => import('../views/Users.vue'), meta: { roles: ['super_admin', 'ed'] } },
    ],
  },
]
const router = createRouter({ history: createWebHistory(), routes })
router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.public) return true
  if (to.meta.guest) return true
  if (!auth.isAuthenticated) return { name: 'login' }
  if (to.name === 'dashboard' && auth.isSuperAdmin) return { name: 'organizations' }
  if (to.meta.roles && !to.meta.roles.includes(auth.role)) return auth.isSuperAdmin ? { name: 'organizations' } : { name: 'dashboard' }
  return true
})
export default router
