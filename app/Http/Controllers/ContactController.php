<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:180'],
                'message' => ['required', 'string', 'max:2000'],
            ],
            [
                'name.required' => 'Nama wajib diisi.',
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Gunakan format email yang valid.',
                'message.required' => 'Pesan wajib diisi.',
            ],
        );

        $webhookUrl = config('services.discord.webhook_url');

        if (! is_string($webhookUrl) || ! preg_match('#^https://discord\.com/api/webhooks/\d{17,20}/[A-Za-z0-9._-]+$#D', $webhookUrl)) {
            return response()->json([
                'message' => 'Layanan kontak belum dikonfigurasi. Silakan hubungi saya melalui media sosial.',
            ], 503);
        }

        try {
            $discordResponse = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(5)
                ->post($webhookUrl, [
                    'embeds' => [
                        [
                            'title' => 'Pesan baru dari portofolio',
                            'color' => 0x176B49,
                            'fields' => [
                                [
                                    'name' => 'Nama',
                                    'value' => $validated['name'],
                                    'inline' => true,
                                ],
                                [
                                    'name' => 'Email',
                                    'value' => $validated['email'],
                                    'inline' => true,
                                ],
                            ],
                            'description' => $validated['message'],
                        ],
                    ],
                    'allowed_mentions' => [
                        'parse' => [],
                    ],
                ]);
        } catch (ConnectionException $exception) {
            Log::error('Discord contact webhook request failed.', [
                'exception' => $exception::class,
            ]);

            return response()->json([
                'message' => 'Pesan belum dapat dikirim ke Discord. Silakan coba lagi nanti.',
            ], 502);
        }

        if (! $discordResponse->successful()) {
            Log::error('Discord contact webhook request failed.', [
                'status' => $discordResponse->status(),
            ]);

            return response()->json([
                'message' => 'Pesan belum dapat dikirim ke Discord. Silakan coba lagi nanti.',
            ], 502);
        }

        return response()->json([
            'message' => 'Pesanmu berhasil dikirim. Terima kasih sudah menghubungi saya.',
        ]);
    }
}
