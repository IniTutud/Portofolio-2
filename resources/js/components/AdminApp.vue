<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import defaultPortfolioContent from '../portfolio-content.json';
import { portfolioAsset } from '../portfolio-asset.js';
import { getSupabase, supabaseConfigured } from '../supabase.js';

const asset = (name) => `/images/portfolio/${name}`;
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
const themeFields = Object.entries(DEFAULT_THEME).map(([key, value]) => ({
    key,
    label: key.replace(/([A-Z])/g, ' $1').replace(/^./, (char) => char.toUpperCase()).trim(),
    value,
}));

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

const loggedIn = ref(false);
const checkingSession = ref(true);
const loading = ref(false);
const message = ref('');
const error = ref('');
const credentials = reactive({ email: '', password: '' });
const content = reactive(structuredClone(defaultPortfolioContent));
let authSubscription;

const imageExtensions = {
    'image/gif': 'gif',
    'image/jpeg': 'jpg',
    'image/png': 'png',
    'image/webp': 'webp',
};

const load = async () => {
    const { data, error: loadError } = await getSupabase()
        .from('portfolio_content')
        .select('content')
        .eq('id', 1)
        .maybeSingle();

    if (loadError) {
        throw loadError;
    }

    if (data?.content) {
        Object.assign(content, data.content);
    }

    applyTheme(content.theme ?? DEFAULT_THEME);
};

const verifyAdmin = async (userId) => {
    const { data, error: adminError } = await getSupabase()
        .from('portfolio_admins')
        .select('user_id')
        .eq('user_id', userId)
        .maybeSingle();

    if (adminError) {
        throw adminError;
    }

    return Boolean(data);
};

const initializeSession = async () => {
    try {
        if (!supabaseConfigured) {
            return;
        }

        const { data, error: sessionError } = await getSupabase().auth.getSession();

        if (sessionError) {
            throw sessionError;
        }

        if (!data.session) {
            return;
        }

        if (!await verifyAdmin(data.session.user.id)) {
            await getSupabase().auth.signOut();
            return;
        }

        loggedIn.value = true;
        await load();
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : 'Sesi admin tidak dapat diperiksa.';
    } finally {
        checkingSession.value = false;
    }
};

onMounted(() => {
    if (supabaseConfigured) {
        const { data } = getSupabase().auth.onAuthStateChange((_event, session) => {
            if (!session) {
                loggedIn.value = false;
            }
        });
        authSubscription = data.subscription;
    }

    initializeSession();
});

onBeforeUnmount(() => {
    authSubscription?.unsubscribe();
});

const uploadImage = async (event, setImageUrl) => {
    const input = event.target;
    const file = input.files?.[0];
    if (!file) {
        return;
    }

    try {
        const extension = imageExtensions[file.type];
        if (!extension) {
            throw new Error('Pilih file gambar JPG, PNG, WebP, atau GIF.');
        }

        if (file.size > 10 * 1024 * 1024) {
            throw new Error('Ukuran gambar maksimal 10 MB.');
        }

        loading.value = true;
        error.value = '';
        const objectPath = `uploads/${crypto.randomUUID()}.${extension}`;
        const supabase = getSupabase();
        const { error: uploadError } = await supabase.storage
            .from('portfolio-media')
            .upload(objectPath, file, {
                cacheControl: '31536000',
                contentType: file.type,
                upsert: false,
            });

        if (uploadError) {
            throw uploadError;
        }

        const { data } = supabase.storage.from('portfolio-media').getPublicUrl(objectPath);
        setImageUrl(data.publicUrl);
        message.value = 'Foto berhasil diunggah.';
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : 'Upload foto gagal.';
    } finally {
        loading.value = false;
        input.value = '';
    }
};

const uploadProfileImage = (event, target, key) => uploadImage(event, (url) => {
    target[key] = url;
});

const uploadArrayImage = async (event, collection, index) => {
    await uploadImage(event, (url) => {
        content[collection][index].image = url;
    });
};

const getPortfolioImage = (filename) => filename ? portfolioAsset(filename) : '';
const imageInputClasses = 'h-12 w-full min-w-0 cursor-pointer overflow-hidden !rounded-none !border-2 !border-poster-ink !bg-poster-paper-input !p-1 !font-bold !text-poster-ink file:mr-3 file:min-h-10 file:cursor-pointer file:border-0 file:border-r-2 file:border-poster-ink file:bg-poster-yellow file:px-4 file:py-2.5 file:font-black file:text-poster-ink hover:file:bg-poster-blue hover:file:text-white focus-visible:!outline-3 focus-visible:!outline-offset-2 focus-visible:!outline-poster-blue';

const setThemeColor = (key, value) => {
    if (!content.theme) {
        content.theme = { ...DEFAULT_THEME };
    }

    content.theme[key] = value;
    applyTheme(content.theme);
};

const resetTheme = () => {
    content.theme = { ...DEFAULT_THEME };
    applyTheme(content.theme);
};

const login = async () => {
    loading.value = true;
    error.value = '';
    try {
        const { data, error: loginError } = await getSupabase().auth.signInWithPassword(credentials);

        if (loginError) {
            throw loginError;
        }

        if (!data.user || !await verifyAdmin(data.user.id)) {
            await getSupabase().auth.signOut();
            throw new Error('Akun ini belum diberi akses admin di Supabase.');
        }

        loggedIn.value = true;
        await load();
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : 'Login gagal.';
    } finally {
        loading.value = false;
    }
};

const save = async () => {
    loading.value = true;
    message.value = '';
    error.value = '';
    try {
        const { error: saveError } = await getSupabase()
            .from('portfolio_content')
            .upsert({
                id: 1,
                content: JSON.parse(JSON.stringify(content)),
                updated_at: new Date().toISOString(),
            });

        if (saveError) {
            throw saveError;
        }

        message.value = 'Portfolio berhasil disimpan ke Supabase.';
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : 'Portfolio belum tersimpan.';
    } finally {
        loading.value = false;
    }
};

const add = (key, value) => content[key].push({ ...value });
const remove = (key, index) => content[key].splice(index, 1);
const logout = async () => {
    try {
        const { error: logoutError } = await getSupabase().auth.signOut();

        if (logoutError) {
            throw logoutError;
        }

        loggedIn.value = false;
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : 'Logout gagal.';
    }
};
</script>

<template>
    <div class="min-h-screen bg-poster-canvas p-3 text-poster-ink sm:p-6 lg:p-10">
        <div v-if="checkingSession" class="mx-auto flex min-h-[calc(100vh-6rem)] max-w-7xl items-center justify-center border-2 border-poster-ink bg-poster-paper p-4 shadow-[12px_12px_0_rgb(4_52_37_/_30%)] sm:p-8">
            <p class="text-lg font-bold text-poster-ink">Memeriksa sesi admin...</p>
        </div>

        <div v-else-if="!loggedIn" class="mx-auto flex min-h-[calc(100vh-6rem)] max-w-7xl items-center justify-center border-2 border-poster-ink bg-poster-paper p-4 shadow-[12px_12px_0_rgb(4_52_37_/_30%)] sm:p-8">
            <form class="w-full max-w-md border-2 border-poster-ink bg-poster-paper p-6 sm:p-8" @submit.prevent="login">
                <a href="/" class="inline-flex min-h-11 items-center text-sm font-bold text-poster-green-dark underline-offset-4 hover:underline focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-poster-blue">Kembali ke portfolio</a>
                <p class="mt-8 text-xs font-black uppercase tracking-[0.1em] text-poster-green-dark">Admin dashboard</p>
                <h1 class="mt-3 text-5xl font-black leading-none tracking-[-0.06em] sm:text-6xl">Edit<br /><span class="text-poster-green">portfolio.</span></h1>
                <div class="mt-8 grid gap-5">
                    <p v-if="!supabaseConfigured" role="alert" class="text-sm font-bold text-red-800">Supabase belum dikonfigurasi. Isi URL project dan publishable key di environment Vercel.</p>
                    <label class="grid gap-2 text-sm font-bold text-poster-ink">Email<input v-model="credentials.email" type="email" required autocomplete="email" placeholder="email@contoh.com" class="min-h-12 rounded-none border border-poster-ink bg-poster-paper-input px-4 font-normal text-poster-ink focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-poster-blue" /></label>
                    <label class="grid gap-2 text-sm font-bold text-poster-ink">Password<input v-model="credentials.password" type="password" required autocomplete="current-password" class="min-h-12 rounded-none border border-poster-ink bg-poster-paper-input px-4 font-normal text-poster-ink focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-poster-blue" /></label>
                    <p v-if="error" role="alert" class="text-sm font-bold text-red-800">{{ error }}</p>
                    <button class="min-h-12 border-2 border-poster-ink bg-poster-green px-5 font-black text-white hover:bg-poster-green-dark focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-poster-blue disabled:opacity-60" :disabled="loading || !supabaseConfigured">{{ loading ? 'Membuka dashboard...' : 'Masuk ke dashboard' }}</button>
                </div>
            </form>
        </div>

        <div v-else class="mx-auto max-w-7xl border-2 border-poster-ink bg-poster-paper p-4 shadow-[12px_12px_0_rgb(4_52_37_/_30%)] sm:p-8">
            <div class="mx-auto max-w-6xl [&_section]:border-2 [&_section]:border-poster-ink [&_section]:bg-poster-paper [&_section_h2]:text-poster-green [&_section_label]:text-poster-ink [&_section_input]:border-poster-ink [&_section_input]:bg-poster-paper-input [&_section_input]:text-poster-ink [&_section_input]:focus-visible:outline-poster-blue [&_section_textarea]:border-poster-ink [&_section_textarea]:bg-poster-paper-input [&_section_textarea]:text-poster-ink [&_section_textarea]:focus-visible:outline-poster-blue [&_section>div:first-child_button]:border-2 [&_section>div:first-child_button]:border-poster-ink [&_section>div:first-child_button]:bg-poster-yellow [&_section>div:first-child_button]:text-poster-ink [&_section>div:first-child_button]:hover:bg-poster-blue [&_section>div:first-child_button]:hover:text-white [&_section>p]:text-poster-muted [&_section_.border-t]:border-poster-rule">
            <header class="mb-8 flex flex-col gap-5 border-b border-poster-rule pb-6 sm:flex-row sm:items-end sm:justify-between">
                <div><a href="/" class="inline-flex min-h-11 items-center text-sm font-bold text-poster-green-dark underline-offset-4 hover:underline focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-poster-blue">Lihat portfolio</a><h1 class="mt-3 text-5xl font-black leading-none tracking-[-0.06em] sm:text-6xl">Content<br /><span class="text-poster-green">studio.</span></h1></div>
                <div class="flex flex-wrap gap-3"><button type="button" class="min-h-11 border-2 border-poster-ink bg-poster-paper px-4 text-sm font-bold hover:bg-poster-yellow focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-poster-blue" @click="logout">Keluar</button><button type="button" class="min-h-11 border-2 border-poster-ink bg-poster-green px-5 text-sm font-black text-white hover:bg-poster-green-dark focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-poster-blue disabled:opacity-60" :disabled="loading" @click="save">{{ loading ? 'Menyimpan...' : 'Simpan perubahan' }}</button></div>
            </header>

            <p v-if="message" role="status" class="mb-6 border border-poster-green bg-poster-success-bg p-4 text-sm font-bold text-poster-success-text">{{ message }}</p>
            <p v-if="error" role="alert" class="mb-6 border border-red-800/50 bg-poster-danger-bg p-4 text-sm font-bold text-poster-danger-text">{{ error }}</p>

            <section class="mb-6 border border-white/20 bg-poster-admin-panel p-5 sm:p-8 [&_input]:min-h-11 [&_input]:w-full [&_input]:rounded-lg [&_input]:border [&_input]:border-white/25 [&_input]:bg-black/35 [&_input]:px-3 [&_input]:py-2 [&_input]:font-normal [&_input]:text-white [&_label]:grid [&_label]:min-w-0 [&_label]:gap-2 [&_label]:text-sm [&_label]:font-bold [&_label]:text-white/80 [&_textarea]:w-full [&_textarea]:rounded-lg [&_textarea]:border [&_textarea]:border-white/25 [&_textarea]:bg-black/35 [&_textarea]:px-3 [&_textarea]:py-2 [&_textarea]:font-normal [&_textarea]:text-white [&_input]:focus-visible:outline-3 [&_input]:focus-visible:outline-offset-2 [&_input]:focus-visible:outline-lavender-light [&_textarea]:focus-visible:outline-3 [&_textarea]:focus-visible:outline-offset-2 [&_textarea]:focus-visible:outline-lavender-light">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-2xl font-bold text-lavender-light">Warna tampilan</h2>
                    <button type="button" class="min-h-11 border-2 border-poster-ink bg-poster-yellow px-3 text-sm font-black text-poster-ink hover:bg-poster-blue hover:text-white focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-poster-blue" @click="resetTheme">Reset warna</button>
                </div>
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <label v-for="field in themeFields" :key="field.key" class="gap-2 text-sm font-bold text-white/80">
                        <span class="flex items-center justify-between gap-3">
                            <span>{{ field.label }}</span>
                            <span class="inline-block h-4 w-4 border border-white/50" :style="{ backgroundColor: content.theme?.[field.key] || field.value }" aria-hidden="true"></span>
                        </span>
                        <input :value="content.theme?.[field.key] || field.value" type="color" @input="setThemeColor(field.key, $event.target.value)" />
                    </label>
                </div>
            </section>

            <section class="mb-6 border border-white/20 bg-poster-admin-panel p-5 sm:p-8 [&_input]:min-h-11 [&_input]:w-full [&_input]:rounded-lg [&_input]:border [&_input]:border-white/25 [&_input]:bg-black/35 [&_input]:px-3 [&_input]:py-2 [&_input]:font-normal [&_input]:text-white [&_label]:grid [&_label]:min-w-0 [&_label]:gap-2 [&_label]:text-sm [&_label]:font-bold [&_label]:text-white/80 [&_textarea]:w-full [&_textarea]:rounded-lg [&_textarea]:border [&_textarea]:border-white/25 [&_textarea]:bg-black/35 [&_textarea]:px-3 [&_textarea]:py-2 [&_textarea]:font-normal [&_textarea]:text-white [&_input]:focus-visible:outline-3 [&_input]:focus-visible:outline-offset-2 [&_input]:focus-visible:outline-lavender-light [&_textarea]:focus-visible:outline-3 [&_textarea]:focus-visible:outline-offset-2 [&_textarea]:focus-visible:outline-lavender-light">
                <h2 class="text-2xl font-bold text-lavender-light">About me</h2>
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label>Nama<input v-model="content.profile.name" required /></label><label>Headline<input v-model="content.profile.headline" required /></label><label>Sedang menjadi<input v-model="content.profile.currently" /></label><label>Archive photo (About section)<input type="file" accept="image/*" :class="imageInputClasses" @change="uploadProfileImage($event, content.profile, 'hero_image')" /><img v-if="content.profile.hero_image" :src="getPortfolioImage(content.profile.hero_image)" alt="Preview archive photo" class="mt-3 h-24 w-24 rotate-[-2deg] border-[6px] border-poster-paper object-cover shadow-[3px_3px_0_var(--color-poster-yellow)] outline outline-1 outline-poster-ink" /><span v-if="content.profile.hero_image" class="mt-2 block text-xs text-poster-ink">{{ content.profile.hero_image }}</span></label><label>Main portrait (Hero section)<input type="file" accept="image/*" :class="imageInputClasses" @change="uploadProfileImage($event, content.profile, 'about_image')" /><img v-if="content.profile.about_image" :src="getPortfolioImage(content.profile.about_image)" alt="Preview main portrait" class="mt-3 h-24 w-24 rotate-[2deg] border-[6px] border-poster-paper object-cover shadow-[3px_3px_0_var(--color-poster-yellow)] outline outline-1 outline-poster-ink" /><span v-if="content.profile.about_image" class="mt-2 block text-xs text-poster-ink">{{ content.profile.about_image }}</span></label>
                    <div class="grid min-w-0 gap-2">
                        <label for="logo-image-upload">Foto tab logo</label>
                        <input id="logo-image-upload" type="file" accept="image/*" :class="imageInputClasses" @change="uploadProfileImage($event, content.profile, 'logo_image')" />
                        <p class="text-xs font-normal text-poster-muted">Kalau belum diatur, foto utama akan dipakai.</p>
                        <img v-if="content.profile.logo_image || content.profile.about_image" :src="getPortfolioImage(content.profile.logo_image || content.profile.about_image)" alt="" class="mt-2 size-24 rotate-[-2deg] border-[6px] border-poster-paper object-cover shadow-[3px_3px_0_var(--color-poster-yellow)] outline outline-1 outline-poster-ink" />
                        <span class="break-all text-xs font-normal text-white/80">{{ content.profile.logo_image || 'Menggunakan foto utama' }}</span>
                        <button v-if="content.profile.logo_image" type="button" class="min-h-11 w-fit border-2 border-poster-ink bg-poster-paper px-3 text-sm font-bold text-poster-ink hover:bg-poster-yellow focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-poster-blue" @click="content.profile.logo_image = ''">Gunakan foto utama</button>
                    </div>
                    <label class="sm:col-span-2">Cerita tentang saya<textarea v-model="content.profile.about" rows="5" required /></label>
                </div>
            </section>

            <section class="mb-6 border border-white/20 bg-poster-admin-panel p-5 sm:p-8 [&_input]:min-h-11 [&_input]:w-full [&_input]:rounded-lg [&_input]:border [&_input]:border-white/25 [&_input]:bg-black/35 [&_input]:px-3 [&_input]:py-2 [&_input]:font-normal [&_input]:text-white [&_label]:grid [&_label]:min-w-0 [&_label]:gap-2 [&_label]:text-sm [&_label]:font-bold [&_label]:text-white/80 [&_input]:focus-visible:outline-3 [&_input]:focus-visible:outline-offset-2 [&_input]:focus-visible:outline-lavender-light">
                <h2 class="text-2xl font-bold text-lavender-light">Social account</h2>
                <p class="mt-2 text-sm text-white/80">Masukkan URL lengkap, misalnya https://instagram.com/username.</p>
                <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <label>Instagram<input v-model="content.social_links.instagram" type="url" placeholder="https://instagram.com/username" autocomplete="url" /></label>
                    <label>LinkedIn<input v-model="content.social_links.linkedin" type="url" placeholder="https://linkedin.com/in/username" autocomplete="url" /></label>
                    <label>GitHub<input v-model="content.social_links.github" type="url" placeholder="https://github.com/username" autocomplete="url" /></label>
                </div>
            </section>

            <section class="mb-6 border border-white/20 bg-poster-admin-panel p-5 sm:p-8 [&_input]:min-h-11 [&_input]:w-full [&_input]:rounded-lg [&_input]:border [&_input]:border-white/25 [&_input]:bg-black/35 [&_input]:px-3 [&_input]:py-2 [&_input]:font-normal [&_input]:text-white [&_label]:grid [&_label]:min-w-0 [&_label]:gap-2 [&_label]:text-sm [&_label]:font-bold [&_label]:text-white/80 [&_input]:focus-visible:outline-3 [&_input]:focus-visible:outline-offset-2 [&_input]:focus-visible:outline-lavender-light">
                <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"><h2 class="text-2xl font-bold text-lavender-light">Education history</h2><button type="button" class="min-h-11 border border-lavender-light/50 px-3 text-sm font-bold text-lavender-light hover:bg-lavender/15 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-lavender-light" @click="add('educations', { name: '', period: '', image: '', url: '' })">Tambah pendidikan</button></div>
                <div v-for="(item, index) in content.educations" :key="item.id || index" class="mt-5 grid grid-cols-1 items-end gap-4 border-t border-white/15 pt-5 sm:grid-cols-2 lg:grid-cols-3"><label>Nama<input v-model="item.name" required /></label><label>Periode<input v-model="item.period" required /></label><label>Image<input type="file" accept="image/*" :class="imageInputClasses" @change="uploadArrayImage($event, 'educations', index)" /><img v-if="item.image" :src="getPortfolioImage(item.image)" alt="Preview education" class="mt-3 h-24 w-24 rotate-[-2deg] border-[6px] border-poster-paper object-cover shadow-[3px_3px_0_var(--color-poster-yellow)] outline outline-1 outline-poster-ink" /><span v-if="item.image" class="mt-2 block text-xs text-poster-ink">{{ item.image }}</span></label><label>Link<input v-model="item.url" type="url" /></label><button type="button" class="min-h-11 border border-red-300/50 px-3 text-sm font-bold text-red-200 hover:bg-red-300/10 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-red-200" @click="remove('educations', index)">Hapus</button></div>
                <p v-if="!content.educations.length" class="mt-4 text-sm text-white/70">Belum ada pendidikan. Tambahkan dari tombol di atas.</p>
            </section>

            <section class="mb-6 border border-white/20 bg-panel p-5 sm:p-8 [&_input]:min-h-11 [&_input]:w-full [&_input]:rounded-lg [&_input]:border [&_input]:border-white/25 [&_input]:bg-black/35 [&_input]:px-3 [&_input]:py-2 [&_input]:font-normal [&_input]:text-white [&_label]:grid [&_label]:min-w-0 [&_label]:gap-2 [&_label]:text-sm [&_label]:font-bold [&_label]:text-white/80 [&_input]:focus-visible:outline-3 [&_input]:focus-visible:outline-offset-2 [&_input]:focus-visible:outline-lavender-light">
                <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"><h2 class="text-2xl font-bold text-lavender-light">Skills</h2><button type="button" class="min-h-11 border border-lavender-light/50 px-3 text-sm font-bold text-lavender-light hover:bg-lavender/15 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-lavender-light" @click="add('skills', { name: '', detail: '', mark: '', image: '' })">Tambah skill</button></div>
                <div v-for="(item, index) in content.skills" :key="item.id || index" class="mt-5 grid grid-cols-1 items-end gap-4 border-t border-white/15 pt-5 sm:grid-cols-2 lg:grid-cols-3"><label>Nama<input v-model="item.name" required /></label><label>Detail<input v-model="item.detail" required /></label><label>Mark<input v-model="item.mark" /></label><label>Image<input type="file" accept="image/*" :class="imageInputClasses" @change="uploadArrayImage($event, 'skills', index)" /><img v-if="item.image" :src="getPortfolioImage(item.image)" alt="Preview skill" class="mt-3 h-24 w-24 rotate-[2deg] border-[6px] border-poster-paper object-cover shadow-[3px_3px_0_var(--color-poster-yellow)] outline outline-1 outline-poster-ink" /><span v-if="item.image" class="mt-2 block text-xs text-poster-ink">{{ item.image }}</span></label><button type="button" class="min-h-11 border border-red-300/50 px-3 text-sm font-bold text-red-200 hover:bg-red-300/10 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-red-200" @click="remove('skills', index)">Hapus</button></div>
                <p v-if="!content.skills.length" class="mt-4 text-sm text-white/70">Belum ada keahlian. Tambahkan dari tombol di atas.</p>
            </section>

            <section class="mb-6 border border-white/20 bg-panel p-5 sm:p-8 [&_input]:min-h-11 [&_input]:w-full [&_input]:rounded-lg [&_input]:border [&_input]:border-white/25 [&_input]:bg-black/35 [&_input]:px-3 [&_input]:py-2 [&_input]:font-normal [&_input]:text-white [&_label]:grid [&_label]:min-w-0 [&_label]:gap-2 [&_label]:text-sm [&_label]:font-bold [&_label]:text-white/80 [&_input]:focus-visible:outline-3 [&_input]:focus-visible:outline-offset-2 [&_input]:focus-visible:outline-lavender-light">
                <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"><h2 class="text-2xl font-bold text-lavender-light">Certificates</h2><button type="button" class="min-h-11 border border-lavender-light/50 px-3 text-sm font-bold text-lavender-light hover:bg-lavender/15 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-lavender-light" @click="add('certificates', { name: '', issuer: '', issued_at: '', url: '', image: '' })">Tambah sertifikat</button></div>
                <div v-for="(item, index) in content.certificates" :key="item.id || index" class="mt-5 grid grid-cols-1 items-end gap-4 border-t border-white/15 pt-5 sm:grid-cols-2 lg:grid-cols-3"><label>Nama<input v-model="item.name" required /></label><label>Penerbit<input v-model="item.issuer" required /></label><label>Tahun<input v-model="item.issued_at" /></label><label>Link<input v-model="item.url" type="url" /></label><label>Image<input type="file" accept="image/*" :class="imageInputClasses" @change="uploadArrayImage($event, 'certificates', index)" /><img v-if="item.image" :src="getPortfolioImage(item.image)" alt="Preview certificate" class="mt-3 h-24 w-24 rotate-[-2deg] border-[6px] border-poster-paper object-cover shadow-[3px_3px_0_var(--color-poster-yellow)] outline outline-1 outline-poster-ink" /><span v-if="item.image" class="mt-2 block text-xs text-poster-ink">{{ item.image }}</span></label><button type="button" class="min-h-11 border border-red-300/50 px-3 text-sm font-bold text-red-200 hover:bg-red-300/10 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-red-200" @click="remove('certificates', index)">Hapus</button></div>
                <p v-if="!content.certificates.length" class="mt-4 text-sm text-white/70">Belum ada sertifikat. Tambahkan dari tombol di atas.</p>
            </section>

            <section class="mb-6 border border-white/20 bg-panel p-5 sm:p-8 [&_input]:min-h-11 [&_input]:w-full [&_input]:rounded-lg [&_input]:border [&_input]:border-white/25 [&_input]:bg-black/35 [&_input]:px-3 [&_input]:py-2 [&_input]:font-normal [&_input]:text-white [&_label]:grid [&_label]:min-w-0 [&_label]:gap-2 [&_label]:text-sm [&_label]:font-bold [&_label]:text-white/80 [&_textarea]:w-full [&_textarea]:rounded-lg [&_textarea]:border [&_textarea]:border-white/25 [&_textarea]:bg-black/35 [&_textarea]:px-3 [&_textarea]:py-2 [&_textarea]:font-normal [&_textarea]:text-white [&_input]:focus-visible:outline-3 [&_input]:focus-visible:outline-offset-2 [&_input]:focus-visible:outline-lavender-light [&_textarea]:focus-visible:outline-3 [&_textarea]:focus-visible:outline-offset-2 [&_textarea]:focus-visible:outline-lavender-light">
                <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center"><h2 class="text-2xl font-bold text-lavender-light">Projects</h2><button type="button" class="min-h-11 border border-lavender-light/50 px-3 text-sm font-bold text-lavender-light hover:bg-lavender/15 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-lavender-light" @click="add('projects', { name: '', description: '', image: '', url: '', label: '' })">Tambah project</button></div>
                <div v-for="(item, index) in content.projects" :key="item.id || index" class="mt-5 grid grid-cols-1 items-end gap-4 border-t border-white/15 pt-5 sm:grid-cols-2 lg:grid-cols-3"><label>Nama<input v-model="item.name" required /></label><label>Image<input type="file" accept="image/*" :class="imageInputClasses" @change="uploadArrayImage($event, 'projects', index)" /><img v-if="item.image" :src="getPortfolioImage(item.image)" alt="Preview project" class="mt-3 h-24 w-24 rotate-[2deg] border-[6px] border-poster-paper object-cover shadow-[3px_3px_0_var(--color-poster-yellow)] outline outline-1 outline-poster-ink" /><span v-if="item.image" class="mt-2 block text-xs text-poster-ink">{{ item.image }}</span></label><label>Link<input v-model="item.url" type="url" /></label><label>Label link<input v-model="item.label" /></label><label class="sm:col-span-2">Deskripsi<textarea v-model="item.description" rows="3" required /></label><button type="button" class="min-h-11 border border-red-300/50 px-3 text-sm font-bold text-red-200 hover:bg-red-300/10 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-red-200" @click="remove('projects', index)">Hapus</button></div>
                <p v-if="!content.projects.length" class="mt-4 text-sm text-white/70">Belum ada proyek. Tambahkan dari tombol di atas.</p>
            </section>
            </div>
        </div>
    </div>
</template>
