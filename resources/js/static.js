import { createApp } from 'vue';
import '../css/app.css';
import PortfolioApp from './components/PortfolioApp.vue';
import AdminApp from './components/AdminApp.vue';
import { supabaseConfigured } from './supabase.js';

const app = window.location.pathname.startsWith('/admin') ? AdminApp : PortfolioApp;

if (!supabaseConfigured) {
    document.documentElement.dataset.supabaseConfigured = 'false';
}

createApp(app).mount('#app');
