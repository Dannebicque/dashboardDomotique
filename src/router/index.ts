import { createRouter, createWebHistory } from 'vue-router'
import DashboardView from '../views/DashboardView.vue'
import HomeControlView from '../views/HomeControlView.vue'
import LightingView from '../views/LightingView.vue'
import ShuttersView from '../views/ShuttersView.vue'
import MusicView from '../views/MusicView.vue'
import SettingsView from '../views/SettingsView.vue'
import ClimateView from '../views/ClimateView.vue'

export default createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'dashboard', component: DashboardView },
    { path: '/maison', name: 'home', component: HomeControlView },
    { path: '/eclairage', name: 'lighting', component: LightingView },
    { path: '/volets', name: 'shutters', component: ShuttersView },
    { path: '/musique', name: 'music', component: MusicView },
    { path: '/climat', name: 'climate', component: ClimateView },
    { path: '/reglages', name: 'settings', component: SettingsView },
  ],
})
