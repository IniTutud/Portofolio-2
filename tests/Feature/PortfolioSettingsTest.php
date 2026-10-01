<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PortfolioSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_admin_can_save_a_logo_image_and_social_account_url(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $theme = config('portfolio.theme');

        $payload = [
            'profile' => [
                'name' => 'Fadhil',
                'headline' => 'Pelajar dan developer',
                'about' => 'Tentang saya',
                'currently' => 'Pelajar',
                'hero_image' => 'hero.jpg',
                'about_image' => 'portrait.jpg',
                'logo_image' => 'brand-photo.jpg',
            ],
            'social_links' => [
                'instagram' => 'https://instagram.com/fadhil',
                'linkedin' => '',
                'github' => '',
            ],
            'theme' => $theme,
        ];

        $response = $this->actingAs($admin)->putJson('/api/admin/portfolio', $payload);

        $response->assertOk()
            ->assertJsonPath('profile.logo_image', 'brand-photo.jpg')
            ->assertJsonPath('social_links.instagram', 'https://instagram.com/fadhil');

        $this->getJson('/api/portfolio')
            ->assertOk()
            ->assertJsonPath('social_links.instagram', 'https://instagram.com/fadhil');

        $this->assertDatabaseHas('portfolio_profiles', [
            'id' => 1,
            'logo_image' => 'brand-photo.jpg',
        ]);
    }
}
