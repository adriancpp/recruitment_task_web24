import { createRouter, createWebHistory } from 'vue-router'
import ImportUploadView from '../views/ImportUploadView.vue'
import ImportListView from '../views/ImportListView.vue'
import ImportDetailView from '../views/ImportDetailView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'upload', component: ImportUploadView },
    { path: '/imports', name: 'imports', component: ImportListView },
    { path: '/imports/:id', name: 'import-detail', component: ImportDetailView, props: true },
  ],
})

export default router
