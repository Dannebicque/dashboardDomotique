import { createRouter, createWebHistory } from 'vue-router'
import DashboardView from '../views/DashboardView.vue'
import HomeControlView from '../views/HomeControlView.vue'

export default createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'dashboard', component: DashboardView },
    { path: '/maison', name: 'home', component: HomeControlView },
  ],
})
