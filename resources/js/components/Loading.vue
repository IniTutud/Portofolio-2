<script setup>
import { onBeforeUnmount, onMounted } from 'vue';
import gsap from 'gsap';
import { SplitText } from 'gsap/SplitText';

const portraitUrl = '/images/portfolio/loading-head.png';
let previousBodyOverflow = '';

let entranceTl;
let headSpin;

onMounted(() => {
    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    // 1. Entrance Animation Timeline
    entranceTl = gsap.timeline({ defaults: { ease: 'power4.out' } });

    // Animasi 'Kertas' loading jatuh ke meja
    entranceTl.fromTo('.loader-card',
        { autoAlpha: 0, scale: 0.85, y: 60, rotationX: 10, rotation: -6 },
        { autoAlpha: 1, scale: 1, y: 0, rotationX: 0, rotation: -2, duration: 1.2, ease: 'expo.out' }
    );

    // Lingkaran belakang kepala pop-in
    entranceTl.fromTo('#loader-circle',
        { scale: 0, rotation: -45 },
        { scale: 1, rotation: 0, duration: 1, ease: 'elastic.out(1, 0.5)' },
        '-=0.8'
    );

    // Putaran kepala berkelanjutan (Langsung berputar sejak awal, tanpa animasi masuk/muncul)
    headSpin = gsap.to('.spinning-head', {
        rotation: 360,
        duration: 3,
        repeat: -1,
        ease: 'none',
    });



  

    // Animasi fake progress bar
    entranceTl.to('.loader-bar-fill', {
        width: '100%',
        duration: 1.5,
        ease: 'power2.inOut'
    }, '-=0.8');
});

onBeforeUnmount(() => {
    document.body.style.overflow = previousBodyOverflow;
    if (entranceTl) entranceTl.kill();
    if (headSpin) headSpin.kill();
});
</script>

<template>
    <div class="fixed inset-0 z-[60] flex items-center justify-center poster-table overflow-hidden loading-screen p-4 sm:p-8"
        role="status">

        <!-- Wrapper Loading bergaya Kertas Poster -->
        <div class="loader-card relative z-10 flex flex-col items-center bg-poster-paper border-2 border-poster-ink p-8 sm:p-12 max-w-sm w-full"
            style="box-shadow: 12px 14px 0 var(--color-poster-shadow)">

            <!-- Aksen "Selotip" -->
            <div class="absolute -top-4 left-1/2 -translate-x-1/2 w-16 h-8 bg-poster-yellow border-2 border-poster-ink rotate-[-3deg]"
                style="box-shadow: 3px 3px 0 var(--color-poster-ink)"></div>

            <!-- Spinning Head Avatar (Diperbesar) -->
            <div class="relative w-40 h-40 md:w-52 md:h-52 mb-7 flex items-center justify-center shrink-0">
                <div id="loader-circle"
                    class="absolute inset-0 bg-poster-blue rounded-full border-[3px] border-poster-ink"
                    style="box-shadow: 6px 6px 0 var(--color-poster-ink)"></div>
                <!-- Skala gambar dipertahankan w-[95%] agar penuh, animasi masuk ditiadakan -->
                <img :src="portraitUrl"
                    class="relative z-10 w-[95%] h-[95%] object-contain spinning-head drop-shadow-xl" alt="Loading" />
            </div>

            <!-- Title -->
            <div class="overflow-hidden pb-3 mb-1 text-center">
                <h2 class="text-4xl md:text-5xl font-black uppercase text-poster-green loader-title tracking-widest leading-none"
                    style="text-shadow: 3px 3px 0 var(--color-poster-yellow), 5px 5px 0 var(--color-poster-ink)">
                    SABAR
                </h2>
            </div>

            <!-- Message Badge -->
            <div class="mt-2 px-5 py-2 border-2 border-poster-ink bg-poster-yellow text-poster-ink font-black text-sm md:text-base loader-badge text-center"
                style="box-shadow: 4px 4px 0 var(--color-poster-ink)">
                sek cak, lek gupuh pencet en wingi
            </div>

            <!-- Fake Progress Bar -->
           
        </div>
    </div>
</template>

<style scoped>
.loading-screen {
    touch-action: none;
}

.spinning-head {
    will-change: transform;
}

.loader-card {
    will-change: transform, opacity;
    perspective: 1000px;
}
</style>