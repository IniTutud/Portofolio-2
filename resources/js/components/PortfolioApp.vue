<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { SplitText } from 'gsap/SplitText';
import Loading from './Loading.vue';
import defaultPortfolioContent from '../portfolio-content.json';
import { portfolioAsset } from '../portfolio-asset.js';
import { getSupabase, supabaseConfigured } from '../supabase.js';

gsap.registerPlugin(ScrollTrigger, SplitText);

const asset = portfolioAsset;

const DEFAULT_THEME = {
    canvas: '#278d4e',
    paper: '#f4f0e5',
    paperAlt: '#f8f6ef',
    paperTint: '#e9e6d7',
    paperInput: '#fffdf6',
    ink: '#172331',
    copy: '#334247',
    copyAlt: '#344344',
    muted: '#53605b',
    green: '#176b49',
    greenDark: '#0b503b',
    blue: '#2052b5',
    blueDark: '#153f91',
    yellow: '#f0c33c',
    onAccent: '#ffffff',
    rule: '#c8c7c1',
    shadow: '#043425',
    adminBackground: '#050506',
    adminPanel: '#15151c',
    adminPanelBorder: '#44444a',
    adminInput: '#0e0e12',
    adminInputBorder: '#505057',
    adminText: '#ffffff',
    adminMuted: '#dedee0',
    adminRule: '#393941',
    adminAccent: '#d8d9f3',
    adminAccentMuted: '#9498cc',
    successBg: '#e2eee3',
    successText: '#0b503b',
    dangerBg: '#f7e5d7',
    dangerText: '#8d2525',
    danger: '#991b1b',
    dangerLight: '#fecaca',
};

const applyTheme = (theme = DEFAULT_THEME) => {
    const resolvedTheme = { ...DEFAULT_THEME, ...theme };

    Object.entries(resolvedTheme).forEach(([key, value]) => {
        if (typeof value !== 'string' || !/^#[0-9a-fA-F]{6}$/.test(value)) {
            return;
        }

        const cssVar = `--color-poster-${key.replace(/[A-Z]/g, (match) => `-${match.toLowerCase()}`)}`;
        document.documentElement.style.setProperty(cssVar, value);
    });
};

const navItems = [
    { label: 'Tentang', href: '#about' },
    { label: 'Pendidikan', href: '#education' },
    { label: 'Keahlian', href: '#skills' },
    { label: 'Sertifikat', href: '#certificates' },
    { label: 'Proyek', href: '#project' },
    { label: 'Kontak', href: '#contact' },
];

const toArray = (value) => Array.isArray(value) ? value : [];

const socialAccounts = computed(() => [
    { label: 'Instagram', icon: 'instagram', url: portfolio.social_links?.instagram ?? '' },
    { label: 'LinkedIn', icon: 'linkedin', url: portfolio.social_links?.linkedin ?? '' },
    { label: 'GitHub', icon: 'github', url: portfolio.social_links?.github ?? '' },
].filter((social) => social.url.trim()));

const portfolio = reactive({
    ...structuredClone(defaultPortfolioContent),
    social_links: { ...(structuredClone(defaultPortfolioContent).social_links ?? {}) },
    educations: toArray(structuredClone(defaultPortfolioContent).educations),
    skills: toArray(structuredClone(defaultPortfolioContent).skills),
    certificates: toArray(structuredClone(defaultPortfolioContent).certificates),
    projects: toArray(structuredClone(defaultPortfolioContent).projects),
});

const normalizePortfolioContent = (content = {}) => {
    const normalized = { ...content };

    normalized.social_links = normalized.social_links ?? {};
    normalized.educations = toArray(normalized.educations);
    normalized.skills = toArray(normalized.skills);
    normalized.certificates = toArray(normalized.certificates);
    normalized.projects = toArray(normalized.projects);

    Object.assign(portfolio, normalized);
};

const portfolioState = ref('loading');
const portfolioError = ref('');
const portfolioNotice = ref('');
const contactForm = reactive({
    name: '',
    email: '',
    message: '',
});
const contactState = ref('idle');
const contactFeedback = ref('');
const contactErrors = ref({});
const mobileMenuOpen = ref(false);
const activeSection = ref('home');
let portfolioMotionContext;

const schools = computed(() => toArray(portfolio.educations));
const skills = computed(() => toArray(portfolio.skills));
const projects = computed(() => toArray(portfolio.projects));
const initials = computed(() => (
    portfolio.profile.name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase() || 'P'
));

const preloadPortfolioAssets = async () => {
    const urls = new Set([
        asset(portfolio.profile.about_image || 'pink1.png'),
        asset(portfolio.profile.hero_image || 'hero_section.png'),
        portfolio.profile.logo_image ? asset(portfolio.profile.logo_image) : '',
        ...portfolio.educations.map((education) => education.image ? asset(education.image) : ''),
        ...portfolio.skills.map((skill) => skill.image ? asset(skill.image) : ''),
        ...portfolio.certificates.map((certificate) => certificate.image ? asset(certificate.image) : ''),
        ...portfolio.projects.map((project) => project.image ? asset(project.image) : ''),
    ].filter(Boolean));

    await Promise.all([...urls].map((url) => new Promise((resolve) => {
        const img = new Image();
        img.onload = resolve;
        img.onerror = resolve;
        img.src = url;
    })));
};

const loadPortfolio = async () => {
    portfolioError.value = '';
    portfolioState.value = 'loading';

    try {
        const { data, error } = await getSupabase()
            .from('portfolio_content')
            .select('content')
            .eq('id', 1)
            .maybeSingle();

        if (error) {
            throw error;
        }

        if (data?.content) {
            normalizePortfolioContent(data.content);
        } else {
            portfolioNotice.value = 'Konten awal masih ditampilkan. Masuk ke dashboard dan simpan agar tersimpan di Supabase.';
        }

        applyTheme(portfolio.theme ?? DEFAULT_THEME);
        await preloadPortfolioAssets();
        portfolioState.value = 'ready';
        await nextTick();
        animatePortfolio();
    } catch (error) {
        portfolioState.value = 'error';
        portfolioError.value = !supabaseConfigured
            ? 'Supabase belum dikonfigurasi. Tambahkan URL project dan publishable key di environment Vercel, lalu deploy ulang.'
            : error instanceof Error
                ? error.message
                : 'Konten portofolio belum dapat dimuat.';
        await nextTick();
        animatePortfolio();
    }
};

const closeMenu = () => {
    mobileMenuOpen.value = false;
};

const submitContact = async () => {
    contactState.value = 'sending';
    contactFeedback.value = '';
    contactErrors.value = {};

    try {
        const response = await fetch('/contact', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(contactForm),
        });
        const result = await response.json();

        if (response.status === 422 && result?.errors) {
            contactErrors.value = result.errors ?? {};
            contactState.value = 'error';
            contactFeedback.value = 'Periksa kembali data yang kamu isi.';

            return;
        }

        if (!response.ok) {
            throw new Error(result.message || 'Pesan belum dapat dikirim. Silakan coba lagi.');
        }

        contactState.value = 'success';
        contactFeedback.value = result.message;
        contactForm.name = '';
        contactForm.email = '';
        contactForm.message = '';
    } catch (error) {
        contactState.value = 'error';
        contactFeedback.value = error instanceof Error
            ? error.message
            : 'Pesan belum dapat dikirim. Silakan coba lagi.';
    }
};

const setActiveSection = (id) => {
    activeSection.value = id;
};

const handleScroll = () => {
    const sections = document.querySelectorAll('main section[id]');
    const marker = window.scrollY + 180;

    sections.forEach((section) => {
        if (marker >= section.offsetTop && marker < section.offsetTop + section.offsetHeight) {
            activeSection.value = section.id;
        }
    });
};

const animatePortfolio = () => {
    portfolioMotionContext?.revert();

    const folio = document.querySelector('.poster-folio');

    if (!folio || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    portfolioMotionContext = gsap.context(() => {
        // ═══════════════════════════════════════════════════════════════
        // ACT I — CINEMATIC HERO ENTRANCE
        // The entire folio drops in like a poster being slapped onto a board,
        // then the hero image and text cascade in with staggered drama.
        // ═══════════════════════════════════════════════════════════════

        const master = gsap.timeline({ defaults: { ease: 'power4.out' } });

        // 1. The folio itself — dramatic scale-in with slight rotation, like
        //    a paper being pinned to a corkboard
        master.fromTo(folio,
            { autoAlpha: 0, scale: 0.92, y: 60, rotationX: 4 },
            {
                autoAlpha: 1,
                scale: 1,
                y: 0,
                rotationX: 0,
                duration: 1.1,
                ease: 'expo.out',
                onComplete: () => gsap.set(folio, { clearProps: 'opacity,transform,visibility' }),
            },
        );

        // 2. Hero stage — slides in from the left with a paper-swish feel,
        //    the ::before pseudo (paper bg) is revealed by the element itself
        master.fromTo('.poster-hero-stage',
            { autoAlpha: 0, x: -80, rotation: -5, scale: 0.88 },
            {
                autoAlpha: 1,
                x: 0,
                rotation: 0,
                scale: 1,
                duration: 1.2,
                ease: 'back.out(1.4)',
                onComplete: () => gsap.set('.poster-hero-stage', { clearProps: 'opacity,transform,visibility' }),
            },
            '-=0.6',
        );

        // 3. Hero art (profile image) — bouncy drop-in with overshoot rotation,
        //    feels like a photo being tossed onto the desk
        master.fromTo('.poster-hero-art',
            { autoAlpha: 0, y: 120, rotation: 12, scale: 0.7 },
            {
                autoAlpha: 1,
                y: 0,
                rotation: -3,
                scale: 1.04,
                duration: 1.3,
                ease: 'elastic.out(0.8, 0.4)',
                onComplete: () => gsap.set('.poster-hero-art', { clearProps: 'opacity,transform,visibility' }),
            },
            '-=0.8',
        );

        // 4. Photo note badge — pops in with a stamping effect
        master.fromTo('.poster-photo-note',
            { autoAlpha: 0, scale: 0, rotation: -20 },
            {
                autoAlpha: 1,
                scale: 1,
                rotation: -3,
                duration: 0.5,
                ease: 'back.out(3)',
                onComplete: () => gsap.set('.poster-photo-note', { clearProps: 'opacity,transform,visibility' }),
            },
            '-=0.5',
        );

        // 5. Hero copy lines — each line slides up with staggered timing
        //    like text being typed onto a poster
        const heroCopyLines = gsap.utils.toArray('.poster-hero-copy > .poster-copy-line');
        if (heroCopyLines.length) {
            master.fromTo(heroCopyLines,
                { autoAlpha: 0, y: 40, x: -20 },
                {
                    autoAlpha: 1,
                    y: 0,
                    x: 0,
                    duration: 0.7,
                    stagger: { each: 0.1, from: 'start' },
                    ease: 'power3.out',
                    onComplete: () => gsap.set(heroCopyLines, { clearProps: 'opacity,transform,visibility' }),
                },
                '-=0.9',
            );
        }

        // 6. Name — SplitText character-by-character reveal with clip-path wipe
        const nameEl = folio.querySelector('.poster-name-print > span:last-child');
        if (nameEl) {
            const nameSplit = SplitText.create(nameEl, { type: 'chars', charsClass: 'poster-name-char' });
            master.fromTo(nameSplit.chars,
                { autoAlpha: 0, y: 50, rotationX: -90, scale: 0.6 },
                {
                    autoAlpha: 1,
                    y: 0,
                    rotationX: 0,
                    scale: 1,
                    duration: 0.6,
                    stagger: { each: 0.04, from: 'start' },
                    ease: 'back.out(1.7)',
                    onComplete: () => {
                        gsap.set(nameSplit.chars, { clearProps: 'opacity,transform,visibility' });
                    },
                },
                '-=0.6',
            );
        }

        // 7. "Hi, I'm" label — clip-path reveal from left
        master.fromTo('.poster-name-print > span:first-child',
            { clipPath: 'inset(0 100% 0 0)' },
            {
                clipPath: 'inset(0 0% 0 0)',
                duration: 0.8,
                ease: 'power2.out',
                onComplete: () => gsap.set('.poster-name-print > span:first-child', { clearProps: 'clipPath' }),
            },
            '-=1.0',
        );

        // 8. Hero note (education card) — slides in from bottom-right with tilt
        master.fromTo('.poster-hero-note',
            { autoAlpha: 0, y: 50, x: 30, rotation: 8 },
            {
                autoAlpha: 1,
                y: 0,
                x: 0,
                rotation: 2,
                duration: 0.7,
                ease: 'back.out(1.5)',
                onComplete: () => gsap.set('.poster-hero-note', { clearProps: 'opacity,transform,visibility' }),
            },
            '-=0.4',
        );

        // 9. Margin note — fades in with vertical writing-mode slide
        master.from('.poster-margin-note', {
            autoAlpha: 0,
            y: 30,
            duration: 0.6,
            ease: 'power2.out',
            onComplete: () => gsap.set('.poster-margin-note', { clearProps: 'opacity,transform,visibility' }),
        }, '-=0.3');

        // ═══════════════════════════════════════════════════════════════
        // ACT II — SCROLL-TRIGGERED SECTION REVEALS
        // Each section has its own dramatic entrance choreography.
        // ═══════════════════════════════════════════════════════════════

        // ---------- ABOUT SECTION ----------
        // The section title text gets a SplitText word-by-word reveal
        const aboutTitle = folio.querySelector('#about h2');
        if (aboutTitle) {
            const aboutSplit = SplitText.create(aboutTitle, { type: 'words', mask: 'words' });
            gsap.fromTo(aboutSplit.words,
                { y: '100%' },
                {
                    y: '0%',
                    duration: 0.8,
                    stagger: { each: 0.04 },
                    ease: 'power4.out',
                    scrollTrigger: { trigger: aboutTitle, start: 'top 85%', once: true },
                },
            );
        }

        // About photo sheet — slides and rotates in like a photo being placed
        const aboutSheet = folio.querySelector('.poster-about-sheet');
        if (aboutSheet) {
            gsap.fromTo(aboutSheet,
                { autoAlpha: 0, x: -60, rotation: -8, scale: 0.85 },
                {
                    autoAlpha: 1,
                    x: 0,
                    rotation: -2,
                    scale: 1,
                    duration: 1,
                    ease: 'elastic.out(0.6, 0.5)',
                    scrollTrigger: { trigger: aboutSheet, start: 'top 85%', once: true },
                    onComplete: () => gsap.set(aboutSheet, { clearProps: 'opacity,transform,visibility' }),
                },
            );
        }

        // About text block — slides in from right side
        const aboutText = folio.querySelector('#about .max-w-2xl');
        if (aboutText) {
            gsap.fromTo(aboutText,
                { autoAlpha: 0, x: 50, y: 20 },
                {
                    autoAlpha: 1,
                    x: 0,
                    y: 0,
                    duration: 0.9,
                    ease: 'power3.out',
                    scrollTrigger: { trigger: aboutText, start: 'top 85%', once: true },
                    onComplete: () => gsap.set(aboutText, { clearProps: 'opacity,transform,visibility' }),
                },
            );
        }

        // ---------- SECTION HEADERS (Number + Label badges) ----------
        // All section badges stamp in with a pop
        const sectionBadges = folio.querySelectorAll('.poster-section-number, .poster-section-label');
        sectionBadges.forEach((badge) => {
            gsap.fromTo(badge,
                { autoAlpha: 0, scale: 0, rotation: gsap.utils.random(-15, 15) },
                {
                    autoAlpha: 1,
                    scale: 1,
                    rotation: badge.classList.contains('poster-section-label')
                        ? (badge.classList.contains('poster-section-label--blue') ? 2 : -2)
                        : 0,
                    duration: 0.5,
                    ease: 'back.out(2.5)',
                    scrollTrigger: { trigger: badge, start: 'top 90%', once: true },
                    onComplete: () => gsap.set(badge, { clearProps: 'opacity,transform,visibility' }),
                },
            );
        });

        // ---------- EDUCATION SECTION ----------
        // Education title — dramatic word reveal
        const eduTitle = folio.querySelector('#education h2');
        if (eduTitle) {
            const eduSplit = SplitText.create(eduTitle, { type: 'words', mask: 'words' });
            gsap.fromTo(eduSplit.words,
                { y: '100%' },
                {
                    y: '0%',
                    duration: 0.7,
                    stagger: 0.06,
                    ease: 'power4.out',
                    scrollTrigger: { trigger: eduTitle, start: 'top 85%', once: true },
                },
            );
        }

        // Education slips — slide in from alternating sides like shuffled papers
        const eduSlips = folio.querySelectorAll('.poster-education-slip');
        eduSlips.forEach((slip, index) => {
            const fromLeft = index % 2 === 0;
            gsap.fromTo(slip,
                {
                    autoAlpha: 0,
                    x: fromLeft ? -60 : 60,
                    y: 15,
                    rotation: fromLeft ? -3 : 3,
                },
                {
                    autoAlpha: 1,
                    x: 0,
                    y: 0,
                    rotation: 0,
                    duration: 0.8,
                    ease: 'power3.out',
                    scrollTrigger: { trigger: slip, start: 'top 88%', once: true },
                    onComplete: () => gsap.set(slip, { clearProps: 'opacity,transform,visibility' }),
                },
            );
        });

        // ---------- SKILLS SECTION ----------
        // Skills title — word-by-word mask reveal
        const skillsTitle = folio.querySelector('#skills h2');
        if (skillsTitle) {
            const skillsSplit = SplitText.create(skillsTitle, { type: 'words', mask: 'words' });
            gsap.fromTo(skillsSplit.words,
                { y: '100%' },
                {
                    y: '0%',
                    duration: 0.7,
                    stagger: 0.05,
                    ease: 'power4.out',
                    scrollTrigger: { trigger: skillsTitle, start: 'top 85%', once: true },
                },
            );
        }

        // Skill slips — fan out like a deck of cards being dealt!
        const skillSlips = folio.querySelectorAll('.poster-skill-slip');
        if (skillSlips.length) {
            ScrollTrigger.batch(skillSlips, {
                start: 'top 90%',
                once: true,
                onEnter: (batch) => {
                    gsap.fromTo(batch,
                        {
                            autoAlpha: 0,
                            y: 50,
                            rotation: (i) => gsap.utils.random(-6, 6),
                            scale: 0.8,
                        },
                        {
                            autoAlpha: 1,
                            y: 0,
                            rotation: (i, target) => {
                                // Restore the CSS rotation classes
                                const idx = Array.from(skillSlips).indexOf(target);
                                if (idx % 3 === 0) return -1;
                                if (idx % 3 === 2) return 1;
                                return 0;
                            },
                            scale: 1,
                            duration: 0.7,
                            stagger: { each: 0.08, from: 'random' },
                            ease: 'back.out(1.4)',
                            overwrite: true,
                            onComplete: () => gsap.set(batch, { clearProps: 'opacity,visibility' }),
                        },
                    );
                },
            });
        }

        // ---------- CERTIFICATES SECTION ----------
        // Certificates title
        const certTitle = folio.querySelector('#certificates h2');
        if (certTitle) {
            const certSplit = SplitText.create(certTitle, { type: 'words', mask: 'words' });
            gsap.fromTo(certSplit.words,
                { y: '100%' },
                {
                    y: '0%',
                    duration: 0.7,
                    stagger: 0.05,
                    ease: 'power4.out',
                    scrollTrigger: { trigger: certTitle, start: 'top 85%', once: true },
                },
            );
        }

        // Certificate slips — slide in with alternating x offsets
        const certSlips = folio.querySelectorAll('.poster-certificate-slip');
        if (certSlips.length) {
            ScrollTrigger.batch(certSlips, {
                start: 'top 88%',
                once: true,
                onEnter: (batch) => {
                    gsap.fromTo(batch,
                        { autoAlpha: 0, y: 40, x: (i) => (i % 2 === 0 ? -30 : 30), rotation: (i) => (i % 2 === 0 ? -2 : 2) },
                        {
                            autoAlpha: 1,
                            y: 0,
                            x: 0,
                            rotation: 0,
                            duration: 0.7,
                            stagger: { each: 0.1, from: 'start' },
                            ease: 'power3.out',
                            overwrite: true,
                            onComplete: () => gsap.set(batch, { clearProps: 'opacity,transform,visibility' }),
                        },
                    );
                },
            });
        }

        // Certificate empty state — bouncy appear
        const certEmpty = folio.querySelector('.poster-certificate-empty');
        if (certEmpty) {
            gsap.fromTo(certEmpty,
                { autoAlpha: 0, scale: 0.85, rotation: -3 },
                {
                    autoAlpha: 1,
                    scale: 1,
                    rotation: -0.5,
                    duration: 0.8,
                    ease: 'elastic.out(0.7, 0.5)',
                    scrollTrigger: { trigger: certEmpty, start: 'top 88%', once: true },
                    onComplete: () => gsap.set(certEmpty, { clearProps: 'opacity,transform,visibility' }),
                },
            );
        }

        // ---------- PROJECTS SECTION ----------
        // Project title — dramatic entrance
        const projTitle = folio.querySelector('#project h2');
        if (projTitle) {
            const projSplit = SplitText.create(projTitle, { type: 'words', mask: 'words' });
            gsap.fromTo(projSplit.words,
                { y: '100%' },
                {
                    y: '0%',
                    duration: 0.7,
                    stagger: 0.06,
                    ease: 'power4.out',
                    scrollTrigger: { trigger: projTitle, start: 'top 85%', once: true },
                },
            );
        }

        // Project sheets — each project unfolds like a paper being laid down
        const projectSheets = folio.querySelectorAll('.poster-project-sheet');
        projectSheets.forEach((sheet, index) => {
            const tl = gsap.timeline({
                scrollTrigger: { trigger: sheet, start: 'top 85%', once: true },
            });

            // The whole card
            tl.fromTo(sheet,
                {
                    autoAlpha: 0,
                    y: 70,
                    rotationX: index % 2 === 0 ? -8 : 8,
                    scale: 0.9,
                    transformPerspective: 800,
                },
                {
                    autoAlpha: 1,
                    y: 0,
                    rotationX: 0,
                    scale: 1,
                    duration: 1,
                    ease: 'power3.out',
                    onComplete: () => gsap.set(sheet, { clearProps: 'opacity,transform,visibility,perspective' }),
                },
            );

            // The image inside scales up slightly
            const img = sheet.querySelector('img');
            if (img) {
                tl.fromTo(img,
                    { scale: 1.15, autoAlpha: 0.7 },
                    {
                        scale: 1,
                        autoAlpha: 1,
                        duration: 0.9,
                        ease: 'power2.out',
                        onComplete: () => gsap.set(img, { clearProps: 'opacity,transform,visibility' }),
                    },
                    '-=0.7',
                );
            }

            // The project number badge pops in
            const badge = sheet.querySelector('span[aria-hidden]');
            if (badge) {
                tl.fromTo(badge,
                    { scale: 0, rotation: -45 },
                    {
                        scale: 1,
                        rotation: 0,
                        duration: 0.4,
                        ease: 'back.out(3)',
                        onComplete: () => gsap.set(badge, { clearProps: 'transform' }),
                    },
                    '-=0.5',
                );
            }

            // Text content slides up
            const textBlock = sheet.querySelector('.flex.min-w-0.flex-col');
            if (textBlock) {
                tl.fromTo(textBlock,
                    { autoAlpha: 0, y: 25 },
                    {
                        autoAlpha: 1,
                        y: 0,
                        duration: 0.6,
                        ease: 'power2.out',
                        onComplete: () => gsap.set(textBlock, { clearProps: 'opacity,transform,visibility' }),
                    },
                    '-=0.6',
                );
            }
        });

        // ---------- CONTACT SECTION ----------
        // Contact title — words revealed dramatically
        const contactTitle = folio.querySelector('#contact h2');
        if (contactTitle) {
            const contactSplit = SplitText.create(contactTitle, { type: 'words', mask: 'words' });
            gsap.fromTo(contactSplit.words,
                { y: '100%' },
                {
                    y: '0%',
                    duration: 0.7,
                    stagger: 0.04,
                    ease: 'power4.out',
                    scrollTrigger: { trigger: contactTitle, start: 'top 85%', once: true },
                },
            );
        }

        // Contact copy (left side) — sweeps in from left
        const contactCopy = folio.querySelector('.poster-contact-copy');
        if (contactCopy) {
            gsap.fromTo(contactCopy,
                { autoAlpha: 0, x: -60, y: 20 },
                {
                    autoAlpha: 1,
                    x: 0,
                    y: 0,
                    duration: 0.9,
                    ease: 'power3.out',
                    scrollTrigger: { trigger: contactCopy, start: 'top 85%', once: true },
                    onComplete: () => gsap.set(contactCopy, { clearProps: 'opacity,transform,visibility' }),
                },
            );
        }

        // Contact paper (form card) — flips in like a card being turned over
        const contactPaper = folio.querySelector('.poster-contact-paper');
        if (contactPaper) {
            const contactFormTl = gsap.timeline({
                scrollTrigger: { trigger: contactPaper, start: 'top 85%', once: true },
            });

            contactFormTl.fromTo(contactPaper,
                { autoAlpha: 0, y: 60, rotationY: -15, scale: 0.9, transformPerspective: 1000 },
                {
                    autoAlpha: 1,
                    y: 0,
                    rotationY: 0,
                    scale: 1,
                    duration: 1,
                    ease: 'power3.out',
                    onComplete: () => gsap.set(contactPaper, { clearProps: 'opacity,transform,visibility,perspective' }),
                },
            );

            // Form fields appear one by one
            const formFields = contactPaper.querySelectorAll('.grid.gap-2, button[type="submit"]');
            if (formFields.length) {
                contactFormTl.fromTo(formFields,
                    { autoAlpha: 0, y: 15 },
                    {
                        autoAlpha: 1,
                        y: 0,
                        duration: 0.4,
                        stagger: 0.08,
                        ease: 'power2.out',
                        onComplete: () => gsap.set(formFields, { clearProps: 'opacity,transform,visibility' }),
                    },
                    '-=0.5',
                );
            }
        }

        // ---------- FOOTER ----------
        const footer = folio.querySelector('footer');
        if (footer) {
            const footerCols = footer.querySelectorAll('.grid.gap-10 > div, .grid.gap-10 > nav');
            if (footerCols.length) {
                gsap.fromTo(footerCols,
                    { autoAlpha: 0, y: 25 },
                    {
                        autoAlpha: 1,
                        y: 0,
                        duration: 0.6,
                        stagger: 0.1,
                        ease: 'power2.out',
                        scrollTrigger: { trigger: footer, start: 'top 90%', once: true },
                        onComplete: () => gsap.set(footerCols, { clearProps: 'opacity,transform,visibility' }),
                    },
                );
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // ACT III — PARALLAX & CONTINUOUS MICRO-INTERACTIONS
        // Subtle scroll-driven parallax on hero elements to add depth.
        // ═══════════════════════════════════════════════════════════════

        // Hero image — slight parallax depth effect on scroll
        const heroArt = folio.querySelector('.poster-hero-art');
        if (heroArt) {
            gsap.to(heroArt, {
                y: -30,
                ease: 'none',
                scrollTrigger: {
                    trigger: '.poster-hero',
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 1,
                },
            });
        }

        // Hero copy — moves at a different rate for parallax
        const heroCopy = folio.querySelector('.poster-hero-copy');
        if (heroCopy) {
            gsap.to(heroCopy, {
                y: -15,
                ease: 'none',
                scrollTrigger: {
                    trigger: '.poster-hero',
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 1,
                },
            });
        }

        // Section numbers — subtle rotation on scroll for playfulness
        const sectionNumbers = folio.querySelectorAll('.poster-section-number');
        sectionNumbers.forEach((num) => {
            gsap.to(num, {
                rotation: '+=360',
                ease: 'none',
                scrollTrigger: {
                    trigger: num,
                    start: 'top bottom',
                    end: 'top top',
                    scrub: 2,
                },
            });
        });

    }, folio);
};

onMounted(() => {
    loadPortfolio();
    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();
});

onBeforeUnmount(() => {
    portfolioMotionContext?.revert();
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <div class="poster-table min-h-screen bg-poster-canvas p-2 text-poster-ink sm:p-5 lg:p-8">
        <a href="#main-content"
            class="fixed left-3 top-3 z-50 -translate-y-[160%] border-2 border-poster-ink bg-poster-yellow px-4 py-3 font-bold text-poster-ink focus:translate-y-0 focus-visible:outline-3 focus-visible:outline-offset-4 focus-visible:outline-poster-blue">Lewati
            ke konten utama</a>

        <div class="poster-book">
            <div class="poster-book-binding" aria-hidden="true">
                <span v-for="clip in 44" :key="clip" class="poster-book-clip"></span>
            </div>
            <header class="poster-book-header relative z-10 flex justify-between gap-4 px-4 sm:px-8">
                <a href="#home"
                    class="poster-brand-tab relative flex min-h-11 min-w-0 shrink items-center gap-3 self-start px-2 py-3"
                    @click="closeMenu">
                    <img v-if="portfolio.profile.logo_image || portfolio.profile.about_image"
                        :src="asset(portfolio.profile.logo_image || portfolio.profile.about_image)" alt=""
                        aria-hidden="true" class="size-[42px] shrink-0 rotate-[-7deg]" />
                    <span v-else
                        class="grid size-[42px] shrink-0 rotate-[-7deg] place-items-center border-2 border-poster-ink bg-poster-yellow text-sm font-black shadow-[3px_3px_0_#2052b5]"
                        aria-hidden="true">{{ initials }}</span>

                </a>

                <nav class="poster-paper-tabs self-end -mb-[13px] flex items-end gap-1 max-[1050px]:hidden"
                    aria-label="Navigasi utama">
                    <a v-for="item in navItems" :key="item.href" :href="item.href"
                        class="poster-paper-tab inline-flex min-h-11 items-center justify-center px-3 text-sm font-extrabold text-poster-ink no-underline transition-transform hover:-translate-y-1 focus-visible:z-10 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-poster-blue"
                        :aria-current="activeSection === item.href.slice(1) ? 'location' : undefined"
                        @click="setActiveSection(item.href.slice(1)); closeMenu()">
                        {{ item.label }}
                    </a>
                </nav>

                <button type="button"
                    class="hidden min-h-11 shrink-0 items-center gap-2 border-2 border-poster-ink bg-poster-yellow px-3 text-sm font-black text-poster-ink focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-poster-blue max-[1050px]:inline-flex"
                    :aria-expanded="mobileMenuOpen" aria-controls="mobile-navigation"
                    @click="mobileMenuOpen = !mobileMenuOpen">
                    <span aria-hidden="true" class="text-lg leading-none">{{ mobileMenuOpen ? '×' : '+' }}</span>
                    {{ mobileMenuOpen ? 'Tutup' : 'Menu' }}
                </button>
            </header>

            <nav v-if="mobileMenuOpen" id="mobile-navigation"
                class="poster-paper-menu relative z-10 hidden grid-cols-3 gap-2 border-y-2 border-poster-ink px-4 py-3 max-[1050px]:grid max-[480px]:grid-cols-2 sm:px-8"
                aria-label="Navigasi mobile">
                <a v-for="item in navItems" :key="item.href" :href="item.href"
                    class="poster-paper-tab poster-paper-tab--mobile inline-flex min-h-11 items-center justify-start px-3 text-sm font-extrabold text-poster-ink no-underline transition-transform hover:-translate-y-0.5 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-poster-blue"
                    :aria-current="activeSection === item.href.slice(1) ? 'location' : undefined"
                    @click="setActiveSection(item.href.slice(1)); closeMenu()">
                    {{ item.label }}
                </a>
            </nav>

            <div class="poster-folio w-full overflow-hidden border-2 border-poster-ink bg-poster-paper">
                <Teleport to="body">
                    <Transition
                        @leave="(el, done) => {
                            gsap.to(el, {
                                y: '-100%',
                                rotation: 4,
                                duration: 1.2,
                                ease: 'expo.inOut',
                                onComplete: done
                            });
                        }"
                    >
                        <Loading v-if="portfolioState === 'loading'" />
                    </Transition>
                </Teleport>
                <div v-if="portfolioState === 'error'"
                    class="flex flex-wrap items-center justify-between gap-4 border-b border-poster-rule bg-[#f7e5d7] px-4 py-3 text-sm text-poster-ink sm:px-8"
                    role="alert">
                    <span>{{ portfolioError }}</span>
                    <button type="button"
                        class="min-h-11 border border-poster-ink px-3 font-bold text-poster-ink hover:bg-poster-ink hover:text-white"
                        @click="loadPortfolio">Coba lagi</button>
                </div>
                <div v-else-if="portfolioNotice"
                    class="border-b border-poster-rule bg-[#f7e5d7] px-4 py-3 text-sm text-poster-ink sm:px-8"
                    role="status">
                    {{ portfolioNotice }}
                </div>

                <main id="main-content">
                    <section id="home"
                        class="poster-hero relative grid grid-cols-1 items-center gap-5 px-4 py-7 sm:px-8 sm:py-10 md:grid-cols-[0.92fr_1.08fr] md:gap-6 lg:grid-cols-[1fr_1fr] lg:gap-12 lg:px-16 lg:py-16">
                        <div
                            class="poster-hero-stage relative grid min-h-[clamp(300px,82vw,460px)] min-w-0 place-items-end-center md:min-h-[clamp(370px,48vw,570px)]">
                            <img :src="asset(portfolio.profile.about_image || 'pink1.png')"
                                :alt="`Potret ${portfolio.profile.name}`"
                                class="poster-hero-art relative z-[1] block h-[clamp(340px,90vw,470px)] w-[112%] max-w-none object-contain object-bottom md:h-[clamp(390px,48vw,590px)] md:w-[117%]"
                                width="1200" height="1200" fetchpriority="high" decoding="async" />
                            <span
                                class="poster-photo-note absolute bottom-4 left-2 z-[2] -rotate-3 border-2 border-poster-ink bg-poster-yellow px-3 py-2 text-[0.65rem] font-black uppercase text-poster-ink sm:left-5">Solipism</span>
                        </div>

                        <div class="poster-hero-copy relative z-[1] min-w-0 py-3">
                            <p
                                class="poster-copy-line mb-4 flex items-center gap-2 text-xs font-black uppercase tracking-[0.1em] text-poster-green-dark">
                                <span class="poster-hero-mark" aria-hidden="true"></span> Software Engineering newbie
                            </p>
                            <h1
                                class="poster-copy-line poster-name-print m-0 grid font-sans font-black leading-[0.92] tracking-[-0.075em]">
                                <span class="mb-2 text-2xl tracking-[-0.045em] sm:text-3xl lg:text-4xl">Hi, I'm</span>
                                <span
                                    class="break-words text-[clamp(4rem,12vw,6rem)] text-poster-green md:text-[clamp(4.5rem,7vw,7rem)]">{{
                                        portfolio.profile.name }}</span>
                            </h1>
                            <p class="poster-copy-line mt-6 max-w-md text-lg font-bold leading-relaxed text-[#334247]">
                                {{ portfolio.profile.headline }}</p>
                            <div
                                class="poster-status-stamp poster-copy-line mt-5 inline-flex items-center gap-2 text-sm text-poster-ink">
                                <span class="size-2 rounded-full bg-poster-blue" aria-hidden="true"></span>
                                <span>career status: <strong class="text-poster-ink">{{ portfolio.profile.currently ||
                                    'a student' }}</strong></span>
                            </div>
                            <div class="poster-copy-line mt-8 flex flex-wrap items-center gap-4">
                                <a href="#project"
                                    class="inline-flex min-h-12 items-center justify-center border-2 border-poster-ink bg-poster-green px-4 py-3 text-sm font-black text-white no-underline transition hover:-translate-y-0.5 hover:bg-poster-green-dark hover:shadow-[3px_3px_0_#172331]">Contact
                                    Me</a>
                                <a href="#about"
                                    class="inline-flex min-h-11 items-center font-extrabold text-poster-blue underline decoration-2 underline-offset-4 hover:text-poster-green-dark">Get
                                    to know me</a>
                            </div>
                            <a v-if="schools.length && schools[0].url" :href="schools[0].url" target="_blank"
                                rel="noreferrer"
                                class="poster-hero-note mt-7 ml-auto block w-fit max-w-full border-2 border-poster-ink bg-poster-yellow px-4 py-3 text-poster-ink no-underline focus-visible:outline-3 focus-visible:outline-offset-4 focus-visible:outline-poster-blue">
                                <span
                                    class="block text-[0.62rem] font-black uppercase tracking-[0.1em] text-poster-green-dark">Current
                                    education</span>
                                <strong class="mt-1 block break-words text-sm">{{ schools[0].name }}</strong>
                                <span class="mt-1 block text-xs font-bold">{{ schools[0].period }}</span>
                            </a>
                        </div>

                        <span
                            class="poster-margin-note absolute bottom-8 right-5 hidden text-[0.58rem] font-black tracking-[0.16em] text-poster-muted [writing-mode:vertical-rl] lg:block"
                            aria-hidden="true">Learning · Designing · Experimenting</span>
                    </section>

                    <section id="about"
                        class="border-t border-poster-rule px-4 py-10 sm:px-8 sm:py-16 lg:px-16 lg:py-20">
                        <div class="mb-5 flex items-center gap-3">
                            <span class="poster-section-number poster-section-number--green">01</span>
                            <span class="poster-section-label poster-section-label--green">Profile</span>
                        </div>
                        <div
                            class="poster-about-layout grid grid-cols-1 items-center gap-6 sm:grid-cols-[0.7fr_1.3fr] sm:gap-10 lg:gap-20">
                            <div
                                class="poster-photo-sheet poster-about-sheet relative mx-auto grid min-h-64 w-4/5 place-items-end-center border-2 border-poster-ink bg-poster-yellow sm:w-full">
                                <img :src="asset(portfolio.profile.hero_image || 'hero_section.png')"
                                    :alt="`Foto arsip ${portfolio.profile.name}`"
                                    class="block max-h-[350px] w-full object-contain object-bottom" width="1200"
                                    height="1200" loading="lazy" decoding="async" />
                                <span
                                    class="absolute bottom-4 right-[-.3rem] rotate-3 border-2 border-poster-ink bg-poster-paper px-3 py-2 text-[0.65rem] font-black uppercase text-poster-ink">walk
                                    the talk</span>
                            </div>
                            <div class="max-w-2xl">
                                <h2
                                    class="m-0 break-words text-4xl font-black leading-[0.98] tracking-[-0.065em] text-poster-ink sm:text-6xl lg:text-7xl">
                                    Space for ideas, processes, and new things.</h2>
                                <p class="my-5 whitespace-pre-line text-base leading-8 text-[#344344] sm:text-lg">{{
                                    portfolio.profile.about }}</p>
                                <a href="#contact"
                                    class="inline-flex min-h-11 items-center font-extrabold text-poster-blue underline decoration-2 underline-offset-4 hover:text-poster-green-dark">Send
                                    Message</a>
                            </div>
                        </div>
                    </section>

                    <section id="education"
                        class="border-t border-poster-rule px-4 py-10 sm:px-8 sm:py-16 lg:px-16 lg:py-20">
                        <div class="mb-8 flex flex-col gap-2 sm:mb-10">
                            <div class="flex items-center gap-3">
                                <a href="/admin"
                                    class="poster-admin-trigger focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-poster-blue"
                                    aria-label="Buka login dashboard">
                                    <span class="poster-section-number poster-section-number--blue"
                                        aria-hidden="true">02</span>
                                </a>
                                <span class="poster-section-label poster-section-label--blue">Education History</span>
                            </div>
                            <h2
                                class="m-0 text-4xl font-black leading-none tracking-[-0.065em] text-poster-ink sm:text-6xl">
                                Education</h2>
                        </div>

                        <div v-if="schools.length" class="divide-y divide-poster-rule border-y-2 border-poster-ink">
                            <article v-for="(school, index) in schools" :key="school.id || school.name"
                                class="poster-education-slip grid grid-cols-[1.7rem_2.5rem_minmax(0,1fr)] items-center gap-x-3 gap-y-2 py-5 sm:grid-cols-[2rem_3.5rem_minmax(0,1fr)] sm:gap-x-5 sm:py-6">
                                <span class="text-xs font-black text-poster-blue">{{ String(index + 1).padStart(2, '0')
                                }}</span>
                                <img v-if="school.image" :src="asset(school.image)" :alt="`Logo ${school.name}`"
                                    class="size-10 object-contain sm:size-14" loading="lazy" />
                                <div class="min-w-0">
                                    <h3
                                        class="m-0 break-words text-lg font-black leading-snug text-poster-ink sm:text-xl">
                                        {{ school.name }}</h3>
                                    <p class="mt-1 text-sm font-bold text-poster-muted">{{ school.period }}</p>
                                    <a v-if="school.url" :href="school.url" target="_blank" rel="noreferrer"
                                        class="mt-2 inline-flex min-h-11 items-center text-sm font-extrabold text-poster-blue underline decoration-1 underline-offset-4 hover:text-poster-green-dark">Visit
                                        Website</a>
                                </div>
                            </article>
                        </div>
                        <p v-else
                            class="m-0 border border-dashed border-poster-rule p-5 text-sm leading-relaxed text-poster-muted">
                            Riwayat pendidikan belum ditambahkan.</p>
                    </section>

                    <section id="skills"
                        class="grid grid-cols-1 items-start gap-6 border-t border-poster-rule bg-[#e9e6d7] px-4 py-10 sm:px-8 sm:py-16 lg:grid-cols-[0.7fr_1.3fr] lg:gap-16 lg:px-16 lg:py-20">
                        <div class="max-w-md">
                            <div class="mb-5 flex items-center gap-3">
                                <span class="poster-section-number poster-section-number--yellow">03</span>
                                <span class="poster-section-label poster-section-label--green">Skills & Interests</span>
                            </div>
                            <h2
                                class="m-0 break-words text-4xl font-black leading-[0.98] tracking-[-0.065em] text-poster-ink sm:text-6xl">
                                Skills <span class="text-poster-green">&amp; Interests</span></h2>
                            <p class="mt-4 max-w-sm text-base leading-7 text-poster-muted">Some things that accompany my
                                learning and exploration process.</p>
                        </div>

                        <ul v-if="skills.length" class="m-0 grid list-none grid-cols-1 gap-3 p-0 sm:grid-cols-2">
                            <li v-for="(skill, index) in skills" :key="skill.id || skill.name"
                                class="poster-skill-slip flex min-h-24 min-w-0 items-center gap-3 border-2 border-poster-ink bg-poster-paper p-3 sm:gap-4 sm:p-4">
                                <span
                                    class="poster-skill-mark grid size-12 shrink-0 place-items-center bg-poster-blue text-sm font-black text-white sm:size-14"
                                    :class="{ 'bg-poster-green': index % 3 === 1, 'bg-poster-yellow text-poster-ink': index % 3 === 2 }">
                                    <img v-if="skill.image" :src="asset(skill.image)" :alt="`Logo ${skill.name}`"
                                        class="max-h-9 max-w-9 object-contain sm:max-h-10 sm:max-w-10" loading="lazy" />
                                    <span v-else aria-hidden="true">{{ skill.mark || skill.name.slice(0, 2) }}</span>
                                </span>
                                <span class="grid min-w-0 gap-1">
                                    <strong class="break-words text-sm leading-snug text-poster-ink">{{ skill.name
                                    }}</strong>
                                    <span v-if="skill.detail" class="text-xs leading-relaxed text-poster-muted">{{
                                        skill.detail }}</span>
                                </span>
                            </li>
                        </ul>
                        <p v-else
                            class="m-0 border border-dashed border-poster-rule p-5 text-sm leading-relaxed text-poster-muted">
                            Skills and interests not added yet.</p>
                    </section>

                    <section id="certificates"
                        class="border-t border-poster-rule px-4 py-10 sm:px-8 sm:py-16 lg:px-16 lg:py-20">
                        <div class="mb-8 flex items-center justify-between gap-5">
                            <div>
                                <div class="mb-4 flex items-center gap-3">
                                    <span class="poster-section-number poster-section-number--green">04</span>
                                    <span class="poster-section-label poster-section-label--blue">Learning
                                        History</span>
                                </div>
                                <h2
                                    class="m-0 text-4xl font-black leading-none tracking-[-0.065em] text-poster-ink sm:text-6xl">
                                    Learning History</h2>
                            </div>
                            <span
                                class="grid size-20 shrink-0 rotate-2 place-items-center rounded-full border-2 border-dashed border-poster-blue text-center text-[0.58rem] font-black leading-snug text-poster-blue"
                                aria-hidden="true">Learning<br />Archive</span>
                        </div>

                        <div v-if="portfolio.certificates.length" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <article v-for="certificate in portfolio.certificates"
                                :key="certificate.id || certificate.name"
                                class="poster-certificate-slip flex min-w-0 items-center gap-4 border border-poster-rule p-4">
                                <img v-if="certificate.image" :src="asset(certificate.image)"
                                    :alt="`Sertifikat ${certificate.name}`" class="size-16 shrink-0 object-contain"
                                    loading="lazy" />
                                <div class="min-w-0 flex-1">
                                    <h3 class="m-0 break-words text-base font-black leading-snug text-poster-ink">{{
                                        certificate.name }}</h3>
                                    <p class="mt-1 text-sm leading-relaxed text-poster-muted">{{ certificate.issuer }}
                                    </p>
                                    <p v-if="certificate.issued_at" class="mt-1 text-xs text-poster-muted">{{
                                        certificate.issued_at }}</p>
                                </div>
                                <a v-if="certificate.url" :href="certificate.url" target="_blank" rel="noreferrer"
                                    class="inline-flex min-h-11 shrink-0 items-center border border-poster-ink px-2 text-xs font-extrabold text-poster-blue no-underline hover:bg-poster-blue hover:text-white"
                                    :aria-label="`Lihat sertifikat ${certificate.name}`">Lihat ↗</a>
                            </article>
                        </div>
                        <div v-else
                            class="poster-certificate-empty flex min-h-36 flex-col items-start justify-center gap-4 border-2 border-poster-ink bg-poster-yellow p-5 sm:p-7">
                            <div class="max-w-lg">
                                <p class="mb-2 text-xs font-black uppercase tracking-[0.1em] text-poster-green-dark">
                                    Halaman berikutnya masih kosong</p>
                                <p class="m-0 text-sm font-bold leading-relaxed text-poster-ink">Sertifikat akan muncul
                                    di sini setelah ditambahkan ke portofolio.</p>
                            </div>
                        </div>
                    </section>

                    <section id="project"
                        class="border-t border-poster-rule bg-[#f8f6ef] px-4 py-10 sm:px-8 sm:py-16 lg:px-16 lg:py-20">
                        <div
                            class="mb-8 flex flex-col items-start justify-between gap-3 sm:mb-10 sm:flex-row sm:items-end sm:gap-8">
                            <div>
                                <div class="mb-4 flex items-center gap-3">
                                    <span class="poster-section-number poster-section-number--blue">05</span>
                                    <span class="poster-section-label poster-section-label--green">Projects</span>
                                </div>
                                <h2
                                    class="m-0 text-4xl font-black leading-none tracking-[-0.065em] text-poster-ink sm:text-6xl">
                                    Projects</h2>
                            </div>
                            <p class="m-0 max-w-sm text-sm leading-relaxed text-poster-muted">Collection of projects I
                                have created and documented.</p>
                        </div>

                        <div v-if="projects.length" class="grid gap-5">
                            <article v-for="(project, index) in projects" :key="project.id || project.name"
                                class="poster-project-sheet grid min-w-0 grid-cols-1 border-2 border-poster-ink bg-poster-paper md:grid-cols-[1.05fr_0.95fr]">
                                <div
                                    class="relative grid min-h-[clamp(210px,35vw,340px)] place-items-center overflow-hidden bg-poster-blue">
                                    <img v-if="project.image" :src="asset(project.image)"
                                        :alt="`Gambar ${project.name}`"
                                        class="block size-full min-h-[inherit] object-cover" loading="lazy" />
                                    <span
                                        class="absolute left-4 top-4 grid size-10 place-items-center bg-poster-yellow text-xs font-black text-poster-ink"
                                        aria-hidden="true">{{ String(index + 1).padStart(2, '0') }}</span>
                                </div>
                                <div class="flex min-w-0 flex-col items-start justify-between gap-8 p-5 sm:p-8">
                                    <div>
                                        <p class="mb-3 text-xs font-black uppercase tracking-[0.1em] text-poster-blue">
                                            Project {{ String(index + 1).padStart(2, '0') }}</p>
                                        <h3
                                            class="m-0 break-words text-3xl font-black leading-tight tracking-[-0.055em] text-poster-ink sm:text-5xl">
                                            {{ project.name }}</h3>
                                        <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-poster-muted">{{
                                            project.description }}</p>
                                    </div>
                                    <a v-if="project.url" :href="project.url" target="_blank" rel="noreferrer"
                                        class="inline-flex min-h-12 items-center justify-center gap-2 border-2 border-poster-ink bg-poster-blue px-4 py-3 text-sm font-black text-white no-underline transition hover:-translate-y-0.5 hover:bg-[#153f91] hover:shadow-[3px_3px_0_#172331]">{{
                                            project.label || 'Lihat proyek' }} <span aria-hidden="true">↗</span></a>
                                </div>
                            </article>
                        </div>
                        <p v-else
                            class="m-0 border border-dashed border-poster-rule p-5 text-sm leading-relaxed text-poster-muted">
                            No projects to display yet.</p>
                    </section>

                    <section id="contact"
                        class="grid grid-cols-1 gap-6 border-t-2 border-poster-ink bg-poster-green px-4 py-10 sm:px-8 sm:py-16 lg:grid-cols-[0.75fr_1.25fr] lg:gap-16 lg:px-16 lg:py-20">
                        <div class="poster-contact-copy text-white">
                            <div class="mb-5 flex items-center gap-3">
                                <span class="poster-section-number poster-section-number--yellow">06</span>
                                <span class="text-xs font-black uppercase tracking-[0.1em] text-white">Let's
                                    Connect</span>
                            </div>
                            <h2
                                class="m-0 max-w-[18ch] border-b border-white/60 pb-3 text-4xl font-black leading-[0.98] tracking-[-0.065em] text-white sm:text-6xl">
                                Let's Connect haha (pls baddie dm me).</h2>
                        </div>

                        <div
                            class="poster-contact-paper grid content-start gap-5 border-2 border-poster-ink bg-poster-paper p-5 shadow-[6px_6px_0_#f0c33c] sm:p-8">
                            <form class="grid gap-4" @submit.prevent="submitContact">
                                <div class="grid gap-2">
                                    <label for="contact-name" class="text-sm font-black text-poster-ink">Name</label>
                                    <input id="contact-name" v-model="contactForm.name" name="name" type="text"
                                        autocomplete="name" maxlength="120" required
                                        :aria-invalid="Boolean(contactErrors.name)"
                                        :aria-describedby="contactErrors.name ? 'contact-name-error' : undefined"
                                        class="min-h-12 w-full border-2 border-poster-ink bg-poster-paper-input px-3 py-2 text-base text-poster-ink placeholder:text-poster-muted focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-poster-blue"
                                        placeholder="Your name">
                                    <p v-if="contactErrors.name" id="contact-name-error"
                                        class="m-0 text-sm font-bold text-poster-danger" role="alert">
                                        {{ contactErrors.name[0] }}
                                    </p>
                                </div>

                                <div class="grid gap-2">
                                    <label for="contact-email" class="text-sm font-black text-poster-ink">Email</label>
                                    <input id="contact-email" v-model="contactForm.email" name="email" type="email"
                                        autocomplete="email" maxlength="180" required
                                        :aria-invalid="Boolean(contactErrors.email)"
                                        :aria-describedby="contactErrors.email ? 'contact-email-error' : undefined"
                                        class="min-h-12 w-full border-2 border-poster-ink bg-poster-paper-input px-3 py-2 text-base text-poster-ink placeholder:text-poster-muted focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-poster-blue"
                                        placeholder="email@example.com">
                                    <p v-if="contactErrors.email" id="contact-email-error"
                                        class="m-0 text-sm font-bold text-poster-danger" role="alert">
                                        {{ contactErrors.email[0] }}
                                    </p>
                                </div>

                                <div class="grid gap-2">
                                    <label for="contact-message"
                                        class="text-sm font-black text-poster-ink">Message</label>
                                    <textarea id="contact-message" v-model="contactForm.message" name="message"
                                        maxlength="2000" rows="5" required
                                        :aria-invalid="Boolean(contactErrors.message)"
                                        :aria-describedby="contactErrors.message ? 'contact-message-error' : undefined"
                                        class="w-full resize-y border-2 border-poster-ink bg-poster-paper-input px-3 py-2 text-base leading-relaxed text-poster-ink placeholder:text-poster-muted focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-poster-blue"
                                        placeholder="Write your message here"></textarea>
                                    <p v-if="contactErrors.message" id="contact-message-error"
                                        class="m-0 text-sm font-bold text-poster-danger" role="alert">
                                        {{ contactErrors.message[0] }}
                                    </p>
                                </div>

                                <p v-if="contactFeedback" class="m-0 text-sm font-bold leading-relaxed"
                                    :class="contactState === 'success' ? 'text-poster-green-dark' : 'text-poster-danger'"
                                    :role="contactState === 'success' ? 'status' : 'alert'">
                                    {{ contactFeedback }}
                                </p>

                                <button type="submit" :disabled="contactState === 'sending'"
                                    class="inline-flex min-h-12 w-full items-center justify-center border-2 border-poster-ink bg-poster-green px-4 py-3 text-base font-black text-white transition hover:bg-poster-green-dark focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-poster-blue disabled:cursor-wait disabled:opacity-70 sm:w-fit">
                                    {{ contactState === 'sending' ? 'Sending message...' : 'Send message' }}
                                </button>
                            </form>
                        </div>
                    </section>
                </main>

                <footer class="border-t border-poster-ink/15 bg-poster-paper-tint text-poster-copy">
                    <!-- Bagian Utama Footer -->
                    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-8 lg:px-16">
                        <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-12">

                            <!-- Kolom 1: Profil / Brand -->
                            <div class="grid content-start gap-3 lg:col-span-5">
                                <p class="m-0 text-xl font-bold tracking-tight text-poster-ink">
                                    {{ portfolio.profile.name }}<span class="text-poster-green"></span>
                                </p>
                                <p class="m-0 max-w-sm text-sm leading-relaxed text-poster-copy/80">
                                    Professional portfolio. Open to collaboration, project discussions, or just a quick
                                    greeting.
                                </p>
                            </div>

                            <!-- Kolom 2: Media Sosial -->
                            <div class="grid content-start gap-3 lg:col-span-4">
                                <h2 class="m-0 text-xs font-semibold uppercase tracking-wider text-poster-ink/70">
                                    Social Media
                                </h2>
                                <ul v-if="socialAccounts.length" class="m-0 flex flex-wrap gap-2 p-0 list-none">
                                    <li v-for="social in socialAccounts" :key="social.label">
                                        <a :href="social.url" target="_blank" rel="noopener noreferrer"
                                            class="inline-flex items-center gap-2 rounded-lg border border-poster-ink/10 bg-white/50 px-3 py-2 text-sm font-medium text-poster-ink transition-colors hover:border-poster-blue hover:bg-white hover:text-poster-blue focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-poster-blue">
                                            <svg v-if="social.icon === 'instagram'" aria-hidden="true"
                                                viewBox="0 0 24 24" class="size-4 shrink-0" fill="none"
                                                stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="3" width="18" height="18" rx="5" />
                                                <circle cx="12" cy="12" r="4" />
                                                <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" />
                                            </svg>
                                            <svg v-else-if="social.icon === 'linkedin'" aria-hidden="true"
                                                viewBox="0 0 24 24" class="size-4 shrink-0" fill="currentColor">
                                                <path
                                                    d="M20.45 20.45h-3.56v-5.57c0-1.33-.03-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29ZM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12ZM7.12 20.45H3.56V9h3.56v11.45ZM22.23 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.23 0Z" />
                                            </svg>
                                            <svg v-else aria-hidden="true" viewBox="0 0 24 24" class="size-4 shrink-0"
                                                fill="currentColor">
                                                <path
                                                    d="M12 .9a11.1 11.1 0 0 0-3.51 21.63c.56.1.76-.24.76-.54v-2.08c-3.1.68-3.75-1.32-3.75-1.32-.51-1.3-1.24-1.65-1.24-1.65-1.01-.69.08-.68.08-.68 1.12.08 1.71 1.14 1.71 1.14 1 .1.98 1.44 3.01 1.09.1-.71.39-1.2.7-1.47-2.47-.28-5.07-1.24-5.07-5.5 0-1.21.43-2.2 1.14-2.98-.11-.28-.5-1.41.11-2.94 0 0 .93-.3 3.05 1.14a10.6 10.6 0 0 1 5.55 0c2.12-1.44 3.04-1.14 3.04-1.14.61 1.53.23 2.66.12 2.94.71.78 1.13 1.77 1.13 2.98 0 4.27-2.61 5.21-5.1 5.49.4.34.75 1.02.75 2.06v3.06c0 .3.2.65.77.54A11.1 11.1 0 0 0 12 .9Z" />
                                            </svg>
                                            <span>{{ social.label }}</span>
                                        </a>
                                    </li>
                                </ul>
                                <p v-else class="m-0 text-sm text-poster-muted">No social media links available.</p>
                            </div>

                            <!-- Kolom 3: Navigasi Cepat -->
                            <nav class="grid content-start gap-2 sm:justify-items-start lg:col-span-3 lg:justify-items-end"
                                aria-label="Tautan halaman">
                                <h2 class="m-0 text-xs font-semibold uppercase tracking-wider text-poster-ink/70">
                                    Navigation
                                </h2>
                                <div class="flex flex-col gap-2 lg:items-end">
                                    <a href="#contact"
                                        class="text-sm font-medium text-poster-ink transition-colors hover:text-poster-blue focus-visible:outline-none focus-visible:underline">
                                        Send Message
                                    </a>
                                    <a href="#home"
                                        class="inline-flex items-center gap-1 text-sm font-medium text-poster-ink transition-colors hover:text-poster-blue focus-visible:outline-none focus-visible:underline">
                                        <span>Back to Top</span>
                                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                        </svg>
                                    </a>
                                </div>
                            </nav>

                        </div>
                    </div>

                    <!-- Bottom Bar: Hak Cipta -->
                    <div class="border-t border-poster-ink/10 py-4 text-center text-xs text-poster-copy/70">
                        &copy; {{ new Date().getFullYear() }} {{ portfolio.profile.name }} All rights reserved.
                    </div>
                </footer>
            </div>
        </div>
    </div>
</template>
