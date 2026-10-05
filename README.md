<div align="center">

# Porto nya acu

Portofolio personal untuk menampilkan profil, pendidikan, keahlian, sertifikat, dan proyek.

[![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/)
[![Vue 3](https://img.shields.io/badge/Vue.js-3-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white)](https://vuejs.org/)
[![Tailwind CSS 4](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)
[![Vite 8](https://img.shields.io/badge/Vite-8-646CFF?style=for-the-badge&logo=vite&logoColor=white)](https://vite.dev/)
[![SQLite](https://img.shields.io/badge/SQLite-database-003B57?style=for-the-badge&logo=sqlite&logoColor=white)](https://www.sqlite.org/)

</div>

## Tentang proyek

Website portofolio satu halaman ini dibuat dengan Vue. Situs publik dan dashboard CMS di-host di Vercel, sementara Supabase menangani login admin, penyimpanan data portofolio, dan file gambar.

> Formulir kontak saat ini hanya menampilkan konfirmasi demo. Aplikasi belum mengirim email.

## Fitur

- Menampilkan profil, riwayat pendidikan, keahlian, sertifikat, proyek, dan informasi kontak.
- Mengelola konten portofolio melalui dashboard admin.
- Mengunggah foto profil, foto tab logo, dan gambar konten.
- Mengatur warna tampilan dari dashboard.
- Menambahkan tautan Instagram, LinkedIn, dan GitHub.
- Menggunakan tata letak responsif dengan dukungan navigasi keyboard.

## Teknologi

| Teknologi | Kegunaan |
| --- | --- |
| [Vue 3](https://vuejs.org/) | Antarmuka interaktif |
| [Tailwind CSS 4](https://tailwindcss.com/) | Styling |
| [Vite 8](https://vite.dev/) | Build aset frontend |
| [Supabase](https://supabase.com/) | Auth, PostgreSQL, dan Storage |
| [Vercel](https://vercel.com/) | Hosting situs statis |

## Menjalankan secara lokal

### Persyaratan

- Node.js dan npm

### Supabase dan environment

1. Buat project Supabase, lalu buka **Project Settings → API Keys**.
2. Salin **Project URL** dan **Publishable key** ke `.env` lokal:

```dotenv
VITE_SUPABASE_URL=https://<project-ref>.supabase.co
VITE_SUPABASE_PUBLISHABLE_KEY=<publishable-key>
```

3. Jalankan seluruh isi [`supabase/setup.sql`](./supabase/setup.sql) lewat **SQL Editor → New query** di dashboard Supabase.
4. Di Supabase, buka **Authentication → Users → Add user**, buat akun CMS dan pastikan alamat emailnya confirmed.
5. Ambil UUID user tersebut, lalu jalankan query grant admin yang ada di bagian akhir `supabase/setup.sql`, dengan mengganti placeholder UUID sebelum query dijalankan.
6. Jalankan `npm run dev`.

Project URL dan publishable key memang digunakan di browser. **Jangan gunakan atau menaruh `service_role`/secret key di `.env` frontend atau Vercel.** Tabel dan bucket dilindungi Row Level Security.

### Deploy ke Vercel

1. Commit dan push perubahan ke GitHub.
2. Di Vercel, import repository ini.
3. Sebelum deploy, buka **Project Settings → Environment Variables** dan tambahkan:

```text
VITE_SUPABASE_URL=https://<project-ref>.supabase.co
VITE_SUPABASE_PUBLISHABLE_KEY=<publishable-key>
```

4. Pilih Production, dan Preview juga jika ingin menguji preview deployment.
5. Deploy dengan build command `npm run build:vercel` dan output directory `dist`; pengaturan tersebut sudah ada di [`vercel.json`](./vercel.json).
6. Buka `/admin`, login menggunakan akun Auth yang diberi akses admin, lalu klik **Simpan perubahan** satu kali. Ini menulis konten awal portofolio dari file JSON ke Supabase. Perubahan sesudahnya disimpan lewat tombol yang sama.

CMS menyimpan satu dokumen JSON berisi profil, pendidikan, keahlian, sertifikat, proyek, tautan sosial, dan tema pada tabel `portfolio_content`. Foto yang diunggah disimpan sebagai objek di bucket publik `portfolio-media`, lalu URL-nya disimpan di dokumen tersebut.

## Perintah pengembangan

```bash
# Jalankan pengujian Laravel lokal, jika memakai bagian Laravel proyek
php artisan test

# Bangun situs untuk Vercel
npm run build:vercel
```
