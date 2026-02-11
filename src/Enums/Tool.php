<?php

declare(strict_types=1);

namespace Pjedesigns\FilamentImageEditor\Enums;

use Filament\Support\Contracts\HasLabel;

enum Tool: string implements HasLabel
{
    case Crop = 'crop';
    case Filter = 'filter';
    case Draw = 'draw';

    public function getLabel(): string
    {
        return match ($this) {
            self::Crop => 'Crop & Transform',
            self::Filter => 'Filters & Adjustments',
            self::Draw => 'Draw & Annotate',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Crop => 'heroicon-o-scissors',
            self::Filter => 'heroicon-o-adjustments-horizontal',
            self::Draw => 'heroicon-o-paint-brush',
        };
    }
}
