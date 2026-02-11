<?php

declare(strict_types=1);

namespace Pjedesigns\FilamentImageEditor\Enums;

use Filament\Support\Contracts\HasLabel;

enum AspectRatio: string implements HasLabel
{
    case Free = 'free';
    case Square = '1:1';
    case Standard = '4:3';
    case Photo = '3:2';
    case Widescreen = '16:9';
    case Portrait = '9:16';
    case PortraitPhoto = '2:3';
    case PortraitStandard = '3:4';

    public function getLabel(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Square => '1:1',
            self::Standard => '4:3',
            self::Photo => '3:2',
            self::Widescreen => '16:9',
            self::Portrait => '9:16',
            self::PortraitPhoto => '2:3',
            self::PortraitStandard => '3:4',
        };
    }

    public function getRatio(): ?float
    {
        return match ($this) {
            self::Free => null,
            self::Square => 1.0,
            self::Standard => 4 / 3,
            self::Photo => 3 / 2,
            self::Widescreen => 16 / 9,
            self::Portrait => 9 / 16,
            self::PortraitPhoto => 2 / 3,
            self::PortraitStandard => 3 / 4,
        };
    }

    /**
     * Create a custom aspect ratio.
     */
    public static function custom(float $ratio): array
    {
        return [
            'value' => 'custom',
            'label' => 'Custom',
            'ratio' => $ratio,
        ];
    }
}
