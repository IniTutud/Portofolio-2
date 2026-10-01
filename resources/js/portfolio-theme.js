export const themeGroups = [
    {
        label: 'Portofolio',
        fields: [
            { key: 'canvas', label: 'Latar luar' },
            { key: 'paper', label: 'Kertas utama' },
            { key: 'paperAlt', label: 'Kertas terang' },
            { key: 'paperTint', label: 'Kertas beraksen' },
            { key: 'paperInput', label: 'Latar isian' },
            { key: 'ink', label: 'Teks utama' },
            { key: 'copy', label: 'Teks headline' },
            { key: 'copyAlt', label: 'Teks deskripsi' },
            { key: 'muted', label: 'Teks sekunder' },
            { key: 'rule', label: 'Garis dan batas' },
            { key: 'shadow', label: 'Bayangan' },
            { key: 'green', label: 'Hijau utama' },
            { key: 'greenDark', label: 'Hijau gelap' },
            { key: 'blue', label: 'Biru utama' },
            { key: 'blueDark', label: 'Biru gelap' },
            { key: 'yellow', label: 'Kuning aksen' },
            { key: 'onAccent', label: 'Teks di atas aksen' },
        ],
    },
    {
        label: 'Dashboard admin',
        fields: [
            { key: 'adminBackground', label: 'Latar dashboard' },
            { key: 'adminPanel', label: 'Panel' },
            { key: 'adminPanelBorder', label: 'Batas panel' },
            { key: 'adminInput', label: 'Latar kolom isian' },
            { key: 'adminInputBorder', label: 'Batas kolom isian' },
            { key: 'adminText', label: 'Teks panel' },
            { key: 'adminMuted', label: 'Teks panel sekunder' },
            { key: 'adminRule', label: 'Garis panel' },
            { key: 'adminAccent', label: 'Aksen panel' },
            { key: 'adminAccentMuted', label: 'Aksen panel sekunder' },
        ],
    },
    {
        label: 'Pesan status',
        fields: [
            { key: 'successBg', label: 'Latar sukses' },
            { key: 'successText', label: 'Teks sukses' },
            { key: 'dangerBg', label: 'Latar peringatan' },
            { key: 'dangerText', label: 'Teks peringatan' },
            { key: 'danger', label: 'Aksen bahaya' },
            { key: 'dangerLight', label: 'Aksen bahaya terang' },
        ],
    },
];

const themeProperties = {
    canvas: ['--color-poster-canvas'],
    paper: ['--color-poster-paper'],
    paperAlt: ['--color-poster-paper-alt'],
    paperTint: ['--color-poster-paper-tint'],
    paperInput: ['--color-poster-paper-input'],
    ink: ['--color-poster-ink', '--color-ink'],
    copy: ['--color-poster-copy'],
    copyAlt: ['--color-poster-copy-alt'],
    muted: ['--color-poster-muted'],
    green: ['--color-poster-green'],
    greenDark: ['--color-poster-green-dark'],
    blue: ['--color-poster-blue'],
    blueDark: ['--color-poster-blue-dark'],
    yellow: ['--color-poster-yellow'],
    onAccent: ['--color-poster-on-accent'],
    rule: ['--color-poster-rule'],
    shadow: ['--color-poster-shadow'],
    adminBackground: ['--color-poster-admin-background', '--color-night'],
    adminPanel: ['--color-poster-admin-panel', '--color-panel'],
    adminPanelBorder: ['--color-poster-admin-panel-border'],
    adminInput: ['--color-poster-admin-input'],
    adminInputBorder: ['--color-poster-admin-input-border'],
    adminText: ['--color-poster-admin-text'],
    adminMuted: ['--color-poster-admin-muted'],
    adminRule: ['--color-poster-admin-rule'],
    adminAccent: ['--color-poster-admin-accent', '--color-lavender-light'],
    adminAccentMuted: ['--color-poster-admin-accent-muted', '--color-lavender'],
    successBg: ['--color-poster-success-bg'],
    successText: ['--color-poster-success-text'],
    dangerBg: ['--color-poster-danger-bg'],
    dangerText: ['--color-poster-danger-text'],
    danger: ['--color-poster-danger'],
    dangerLight: ['--color-poster-danger-light'],
};

export const createThemeStyle = (theme) => Object.fromEntries(
    Object.entries(themeProperties).flatMap(([key, properties]) => {
        const color = theme[key];

        return typeof color === 'string' && /^#[\da-f]{6}$/i.test(color)
            ? properties.map((property) => [property, color])
            : [];
    }),
);
