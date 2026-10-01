<script setup>
import { onBeforeUnmount, onMounted } from 'vue';

const portraitUrl = '/images/portfolio/loading-head.png';
let previousBodyOverflow = '';

onMounted(() => {
    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
});

onBeforeUnmount(() => {
    document.body.style.overflow = previousBodyOverflow;
});
</script>

<template>
    <div class="poster-table loading-screen" role="status" aria-live="polite" aria-label="Memuat portofolio">
        <div class="loading-screen__sheet">
            <img
                class="loading-screen__portrait"
                :src="portraitUrl"
                alt=""
                width="768"
                height="952"
            />
            <p class="loading-screen__label">bzzzzzz...</p>
            <p class="loading-screen__message">sek cak, lek gupuh pencet en wingi</p>
        </div>
    </div>
</template>

<style scoped>
.loading-screen {
    position: fixed;
    inset: 0;
    z-index: 60;
    display: grid;
    place-items: center;
    min-height: 100vh;
    min-height: 100dvh;
    padding: max(1.25rem, env(safe-area-inset-top)) max(1.25rem, env(safe-area-inset-right))
        max(1.25rem, env(safe-area-inset-bottom)) max(1.25rem, env(safe-area-inset-left));
}

.loading-screen__sheet {
    display: grid;
    justify-items: center;
    width: min(100%, 26rem);
    padding: clamp(1.25rem, 5vw, 2rem) 1rem 1.5rem;
    border: 2px solid var(--color-poster-ink);
    background: var(--color-poster-paper);
    box-shadow: 8px 8px 0 var(--color-poster-yellow), 11px 11px 0 var(--color-poster-ink);
    transform: rotate(-1deg);
}

.loading-screen__portrait {
    display: block;
    width: min(52vw, 15rem);
    height: auto;
    object-fit: contain;
    animation: loading-head-spin 1.7s linear infinite;
    transform-origin: center;
}

.loading-screen__label {
    margin: 0;
    padding: 0.35rem 0.65rem;
    background: var(--color-poster-yellow);
    color: var(--color-poster-ink);
    font-size: 0.7rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.loading-screen__message {
    margin: 0.9rem 0 0;
    color: var(--color-poster-ink);
    font-size: clamp(1rem, 4vw, 1.2rem);
    font-weight: 800;
    text-align: center;
}

@keyframes loading-head-spin {
    to {
        transform: rotate(360deg);
    }
}

@media (prefers-reduced-motion: reduce) {
    .loading-screen__portrait {
        animation: none;
    }
}
</style>
