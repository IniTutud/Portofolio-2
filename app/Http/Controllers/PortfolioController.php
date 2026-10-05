<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Education;
use App\Models\PortfolioProfile;
use App\Models\Project;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PortfolioController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json($this->content());
    }

    public function upload(Request $request): JsonResponse
    {
        abort_unless($request->user()?->is_admin, 403, 'Akses admin diperlukan.');

        $validated = $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:6144', 'dimensions:max_width=6000,max_height=6000'],
        ]);
        $file = $validated['file'];

        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return response()->json(['message' => 'File foto tidak valid.'], 422);
        }

        $image = $this->storeOptimizedImage($file);

        return response()->json([
            'message' => 'Foto berhasil diunggah.',
            'filename' => $image['filename'],
            'url' => $image['url'],
            'path' => $image['url'],
        ]);
    }

    /**
     * @return array{filename: string, url: string}
     */
    private function storeOptimizedImage(UploadedFile $file): array
    {
        $mimeType = $file->getMimeType();
        $format = match ($mimeType) {
            'image/png' => 'png',
            'image/webp' => function_exists('imagewebp') ? 'webp' : 'jpg',
            'image/gif' => 'jpg',
            default => 'jpg',
        };

        $source = file_get_contents($file->getRealPath());
        if ($source === false) {
            throw new RuntimeException('Foto tidak dapat dibaca.');
        }

        $image = @imagecreatefromstring($source);
        if ($image === false) {
            throw new RuntimeException('Format foto tidak didukung untuk kompresi otomatis.');
        }

        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        $maxDimension = 1400;
        $scale = min(1, $maxDimension / max($originalWidth, $originalHeight));
        $newWidth = max(1, (int) round($originalWidth * $scale));
        $newHeight = max(1, (int) round($originalHeight * $scale));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        if ($resized === false) {
            imagedestroy($image);
            throw new RuntimeException('Foto tidak dapat diproses.');
        }

        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        $baseName = substr(Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'portfolio-image'), 0, 120);
        $filename = sprintf('%s-%s.%s', $baseName, Str::random(12), $format);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'portfolio-');

        if ($temporaryPath === false) {
            imagedestroy($image);
            imagedestroy($resized);
            throw new RuntimeException('Temporary image file could not be created.');
        }

        try {
            $written = match ($format) {
                'png' => imagepng($resized, $temporaryPath, 8),
                'webp' => imagewebp($resized, $temporaryPath, 72),
                default => imagejpeg($resized, $temporaryPath, 72),
            };

            if (! $written) {
                throw new RuntimeException('Optimized image could not be written.');
            }

            $contents = file_get_contents($temporaryPath);

            if ($contents === false) {
                throw new RuntimeException('Optimized image could not be read.');
            }
        } finally {
            imagedestroy($image);
            imagedestroy($resized);
            unlink($temporaryPath);
        }

        $url = $this->storeImageLocally($filename, $contents);

        return ['filename' => $filename, 'url' => $url];
    }

    private function storeImageLocally(string $filename, string $contents): string
    {
        $destination = public_path('images/portfolio');

        if (! is_dir($destination) && ! mkdir($destination, 0755, true) && ! is_dir($destination)) {
            throw new RuntimeException('Portfolio image directory could not be created.');
        }

        if (file_put_contents($destination.DIRECTORY_SEPARATOR.$filename, $contents, LOCK_EX) === false) {
            throw new RuntimeException('Portfolio image could not be stored locally.');
        }

        return "/images/portfolio/{$filename}";
    }

    public function update(Request $request): JsonResponse
    {
        abort_unless($request->user()?->is_admin, 403, 'Akses admin diperlukan.');

        $themeKeys = array_keys(config('portfolio.theme'));
        $themeKeyList = implode(',', $themeKeys);

        $rules = [
            'profile.name' => ['required', 'string', 'max:120'],
            'profile.headline' => ['required', 'string', 'max:180'],
            'profile.about' => ['required', 'string', 'max:5000'],
            'profile.currently' => ['nullable', 'string', 'max:120'],
            'profile.hero_image' => ['nullable', 'string', 'max:255'],
            'profile.about_image' => ['nullable', 'string', 'max:255'],
            'profile.logo_image' => ['nullable', 'string', 'max:255'],
            'social_links' => ['required', 'array:instagram,linkedin,github'],
            'social_links.instagram' => ['nullable', 'url', 'max:500'],
            'social_links.linkedin' => ['nullable', 'url', 'max:500'],
            'social_links.github' => ['nullable', 'url', 'max:500'],
            'theme' => ['required', "array:{$themeKeyList}"],
            'educations' => ['array'],
            'educations.*.name' => ['required', 'string', 'max:160'],
            'educations.*.period' => ['required', 'string', 'max:80'],
            'educations.*.image' => ['nullable', 'string', 'max:255'],
            'educations.*.url' => ['nullable', 'url', 'max:500'],
            'skills' => ['array'],
            'skills.*.name' => ['required', 'string', 'max:100'],
            'skills.*.detail' => ['required', 'string', 'max:180'],
            'skills.*.mark' => ['nullable', 'string', 'max:20'],
            'skills.*.image' => ['nullable', 'string', 'max:255'],
            'certificates' => ['array'],
            'certificates.*.name' => ['required', 'string', 'max:160'],
            'certificates.*.issuer' => ['required', 'string', 'max:160'],
            'certificates.*.issued_at' => ['nullable', 'string', 'max:80'],
            'certificates.*.url' => ['nullable', 'url', 'max:500'],
            'certificates.*.image' => ['nullable', 'string', 'max:255'],
            'projects' => ['array'],
            'projects.*.name' => ['required', 'string', 'max:160'],
            'projects.*.description' => ['required', 'string', 'max:5000'],
            'projects.*.image' => ['nullable', 'string', 'max:255'],
            'projects.*.url' => ['nullable', 'url', 'max:500'],
            'projects.*.label' => ['nullable', 'string', 'max:180'],
        ];

        foreach ($themeKeys as $key) {
            $rules["theme.{$key}"] = ['required', 'string', 'regex:/\A#[a-fA-F0-9]{6}\z/'];
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($validated): void {
            PortfolioProfile::updateOrCreate(
                ['id' => 1],
                [
                    ...$validated['profile'],
                    'social_links' => $validated['social_links'],
                    'theme' => $validated['theme'],
                ],
            );

            $this->replaceItems(Education::class, $validated['educations'] ?? []);
            $this->replaceItems(Skill::class, $validated['skills'] ?? []);
            $this->replaceItems(Certificate::class, $validated['certificates'] ?? []);
            $this->replaceItems(Project::class, $validated['projects'] ?? []);
        });

        return response()->json([
            'message' => 'Portfolio berhasil disimpan.',
            ...$this->content(),
        ]);
    }

    private function replaceItems(string $model, array $items): void
    {
        $model::query()->delete();

        foreach (array_values($items) as $index => $item) {
            $model::create([...$item, 'sort_order' => $index]);
        }
    }

    private function content(): array
    {
        $profile = PortfolioProfile::query()->first();

        return [
            'profile' => $profile,
            'social_links' => array_replace(config('portfolio.social_links'), $profile?->social_links ?? []),
            'theme' => array_replace(config('portfolio.theme'), $profile?->theme ?? []),
            'theme_defaults' => config('portfolio.theme'),
            'educations' => Education::query()->orderBy('sort_order')->get(),
            'skills' => Skill::query()->orderBy('sort_order')->get(),
            'certificates' => Certificate::query()->orderBy('sort_order')->get(),
            'projects' => Project::query()->orderBy('sort_order')->get(),
        ];
    }
}
