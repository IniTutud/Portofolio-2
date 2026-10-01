<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioProfile extends Model
{
    protected $fillable = [
        'name',
        'headline',
        'about',
        'currently',
        'hero_image',
        'about_image',
        'logo_image',
        'social_links',
        'theme',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'theme' => 'array',
        ];
    }
}
