<?php

namespace Pjedesigns\FilamentImageEditor\Enums;

use Filament\Support\Contracts\HasLabel;

enum FilterPreset: string implements HasLabel
{
    case Original = 'original';
    case Grayscale = 'grayscale';
    case Sepia = 'sepia';
    case Vintage = 'vintage';
    case Warm = 'warm';
    case Cool = 'cool';
    case HighContrast = 'high-contrast';
    case Fade = 'fade';
    case Dramatic = 'dramatic';
    case Vivid = 'vivid';

    public function getLabel(): string
    {
        return match ($this) {
            self::Original => 'Original',
            self::Grayscale => 'Grayscale',
            self::Sepia => 'Sepia',
            self::Vintage => 'Vintage',
            self::Warm => 'Warm',
            self::Cool => 'Cool',
            self::HighContrast => 'High Contrast',
            self::Fade => 'Fade',
            self::Dramatic => 'Dramatic',
            self::Vivid => 'Vivid',
        };
    }
}
