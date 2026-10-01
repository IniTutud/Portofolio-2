import { createApp } from 'vue';
import '../css/app.css';
import PortfolioApp from './components/PortfolioApp.vue';
import portfolioContent from './portfolio-content.json';

createApp(PortfolioApp, { staticContent: portfolioContent }).mount('#app');
