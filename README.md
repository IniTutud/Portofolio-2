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

Website satu halaman ini dibuat dengan Laravel dan Vue. Pengunjung dapat melihat informasi portofolio dan mengirim pesan melalui formulir kontak. Panel admin digunakan untuk mengelola konten, foto, tautan media sosial, dan warna tampilan.

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
| [Laravel 13](https://laravel.com/) | Backend dan API |
| [Vue 3](https://vuejs.org/) | Antarmuka interaktif |
| [Tailwind CSS 4](https://tailwindcss.com/) | Styling |
| [Vite 8](https://vite.dev/) | Build aset frontend |
| [SQLite](https://www.sqlite.org/) | Database default lokal |
| PHP 8.3 | Runtime backend |

## Menjalankan secara lokal

### Persyaratan

- PHP 8.3 atau lebih baru
- Composer
- Node.js dan npm

### Instalasi

Jalankan dari direktori proyek:

```bash
composer run setup
```

Perintah ini memasang dependensi, menyiapkan `.env`, membuat application key, menjalankan migrasi, memasang paket frontend, dan membangun aset.

### Menjalankan aplikasi

```bash
composer run dev
```

Buka alamat lokal yang ditampilkan oleh perintah tersebut.

## Perintah pengembangan

```bash
# Jalankan pengujian
php artisan test

# Bangun aset frontend
npm run build
```


