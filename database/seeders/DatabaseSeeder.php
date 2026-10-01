<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Certificate;
use App\Models\Education;
use App\Models\PortfolioProfile;
use App\Models\Project;
use App\Models\Skill;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Fadhil',
            'email' => 'admin@example.com',
            'password' => 'password',
            'is_admin' => true,
        ]);

        PortfolioProfile::create([
            'name' => 'Fadhil',
            'headline' => 'Pelajar, pembuat web, penggemar game',
            'about' => 'Saya pelajar kelahiran 28 Oktober 2008 yang bersekolah di SMK Negeri 1 Surabaya, jurusan Rekayasa Perangkat Lunak. Saya suka menggabungkan ketertarikan pada game, desain, dan teknologi ke dalam tugas-tugas kecil yang bisa dipelajari lagi.',
            'currently' => 'pelajar',
            'hero_image' => 'JOSUKE.png',
            'about_image' => 'pink1.png',
        ]);

        foreach ([
            ['name' => 'SMK Negeri 1 Surabaya', 'period' => '2024 sampai sekarang', 'image' => 'smeas.png', 'url' => 'https://www.smkn1-sby.sch.id'],
            ['name' => 'SMP Negeri 35 Surabaya', 'period' => '2021 sampai 2024', 'image' => 'gama.png', 'url' => 'https://profilsekolahdispendik.surabaya.go.id/umum/sekolah/detail_sekolah_enc?q=MjA1MzI1Nzc='],
            ['name' => 'SDN Penjaringan Sari 2 Surabaya', 'period' => '2015 sampai 2021', 'image' => 'pensada.png', 'url' => 'https://dapo.kemdikbud.go.id/sekolah/3616A543756AFAF140EF'],
        ] as $education) {
            Education::create($education);
        }

        foreach ([
            ['name' => 'HTML5', 'detail' => 'Struktur halaman web', 'mark' => '</>'],
            ['name' => 'CSS3', 'detail' => 'Tata visual dan layout', 'mark' => '{ }'],
            ['name' => 'Illustrator', 'detail' => 'Eksplorasi desain grafis', 'mark' => 'Ai'],
            ['name' => 'Photoshop', 'detail' => 'Eksplorasi olah gambar', 'mark' => 'Ps'],
            ['name' => 'Need for Speed', 'detail' => 'Game balap favorit', 'image' => 'nfs.png'],
            ['name' => 'Gran Turismo', 'detail' => 'Simulator balap favorit', 'image' => 'gt.png'],
        ] as $skill) {
            Skill::create($skill);
        }

        Project::create([
            'name' => 'Way Back Home',
            'description' => 'Game indie berbasis Scratch 3 buatan K.O.N.Z. Ceritanya memadukan dunia fantasi, era dinosaurus, dan era magis.',
            'image' => 'wbh12.png',
            'url' => 'https://scratch.mit.edu/projects/1119146146',
            'label' => 'Buka project di Scratch',
        ]);
    }
}
