<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    public function test_empty_payload_returns_validation_errors(): void
    {
        $response = $this->postJson('/contact', []);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'Nama wajib diisi.')
            ->assertJsonPath('errors.email.0', 'Email wajib diisi.')
            ->assertJsonPath('errors.message.0', 'Pesan wajib diisi.');
    }

    public function test_missing_discord_webhook_returns_service_unavailable(): void
    {
        Http::preventStrayRequests();
        config([
            'services.discord.webhook_url' => null,
        ]);

        $response = $this->postJson('/contact', [
            'name' => 'Ayu',
            'email' => 'ayu@example.com',
            'message' => 'Halo.',
        ]);

        $response->assertServiceUnavailable()
            ->assertJsonPath('message', 'Layanan kontak belum dikonfigurasi. Silakan hubungi saya melalui media sosial.');
        Http::assertNothingSent();
    }

    public function test_non_discord_webhook_url_returns_service_unavailable(): void
    {
        Http::preventStrayRequests();
        config([
            'services.discord.webhook_url' => 'https://example.com/api/webhooks/123456789012345678/token',
        ]);

        $response = $this->postJson('/contact', [
            'name' => 'Ayu',
            'email' => 'ayu@example.com',
            'message' => 'Halo.',
        ]);

        $response->assertServiceUnavailable();
        Http::assertNothingSent();
    }

    public function test_valid_message_is_sent_to_the_discord_webhook(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'discord.com/api/webhooks/*' => Http::response(null, 204),
        ]);
        config([
            'services.discord.webhook_url' => 'https://discord.com/api/webhooks/123456789012345678/test-webhook-token',
        ]);

        $response = $this->postJson('/contact', [
            'name' => 'Ayu',
            'email' => 'ayu@example.com',
            'message' => 'Halo, saya ingin berdiskusi.',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Pesanmu berhasil dikirim. Terima kasih sudah menghubungi saya.');
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://discord.com/api/webhooks/123456789012345678/test-webhook-token'
            && $request['embeds'][0]['fields'][0]['value'] === 'Ayu'
            && $request['embeds'][0]['fields'][1]['value'] === 'ayu@example.com'
            && $request['embeds'][0]['description'] === 'Halo, saya ingin berdiskusi.'
            && $request['allowed_mentions']['parse'] === []);
    }

    public function test_discord_webhook_rejection_returns_bad_gateway(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'discord.com/api/webhooks/*' => Http::response(['message' => 'Unknown Webhook'], 404),
        ]);
        config([
            'services.discord.webhook_url' => 'https://discord.com/api/webhooks/123456789012345678/test-webhook-token',
        ]);

        $response = $this->postJson('/contact', [
            'name' => 'Ayu',
            'email' => 'ayu@example.com',
            'message' => 'Halo.',
        ]);

        $response->assertStatus(502)
            ->assertJsonPath('message', 'Pesan belum dapat dikirim ke Discord. Silakan coba lagi nanti.');
        Http::assertSentCount(1);
    }

    public function test_discord_webhook_timeout_returns_bad_gateway(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'discord.com/api/webhooks/*' => Http::failedConnection(),
        ]);
        config([
            'services.discord.webhook_url' => 'https://discord.com/api/webhooks/123456789012345678/test-webhook-token',
        ]);

        $response = $this->postJson('/contact', [
            'name' => 'Ayu',
            'email' => 'ayu@example.com',
            'message' => 'Halo.',
        ]);

        $response->assertStatus(502)
            ->assertJsonPath('message', 'Pesan belum dapat dikirim ke Discord. Silakan coba lagi nanti.');
        Http::assertSentCount(1);
    }
}
