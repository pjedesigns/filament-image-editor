<?php

declare(strict_types=1);

namespace Pjedesigns\FilamentImageEditor\Enums;

use Filament\Support\Contracts\HasLabel;

enum OutputFormat: string implements HasLabel
{
    case Jpeg = 'jpeg';
    case Png = 'png';
    case Webp = 'webp';

    public function getLabel(): string
    {
        return match ($this) {
            self::Jpeg => 'JPEG',
            self::Png => 'PNG',
            self::Webp => 'WebP',
        };
    }

    public function getMimeType(): string
    {
        return match ($this) {
            self::Jpeg => 'image/jpeg',
            self::Png => 'image/png',
            self::Webp => 'image/webp',
        };
    }

    public function getExtension(): string
    {
        return match ($this) {
            self::Jpeg => 'jpg',
            self::Png => 'png',
            self::Webp => 'webp',
        };
    }

    public function supportsQuality(): bool
    {
        return match ($this) {
            self::Jpeg, self::Webp => true,
            self::Png => false,
        };
    }
}
