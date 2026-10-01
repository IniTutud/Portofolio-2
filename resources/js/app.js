import { createApp } from 'vue';
import '../css/app.css';
import PortfolioApp from './components/PortfolioApp.vue';
import AdminApp from './components/AdminApp.vue';

createApp(window.location.pathname.startsWith('/admin') ? AdminApp : PortfolioApp).mount('#app');
