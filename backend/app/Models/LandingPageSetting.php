<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingPageSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'hero_title',
        'hero_subtitle',
        'video_url',
        'stats_cidades',
        'stats_atletas',
        'stats_jogos',
        'stats_papel',
        'section_benefits_title',
        'section_benefits_subtitle',
        'section_how_it_works_title',
        'section_how_it_works_subtitle',
        'section_features_title',
        'section_sumula_title',
        'section_sumula_text',
        'footer_about_text',
        'contact_email',
        'contact_phone',
        'social_instagram',
        'social_facebook',
    ];
}
