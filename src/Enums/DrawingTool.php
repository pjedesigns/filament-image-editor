<?php

namespace Pjedesigns\FilamentImageEditor\Enums;

use Filament\Support\Contracts\HasLabel;

enum DrawingTool: string implements HasLabel
{
    case Select = 'select';
    case Freehand = 'freehand';
    case Eraser = 'eraser';
    case Line = 'line';
    case Arrow = 'arrow';
    case Rectangle = 'rectangle';
    case Ellipse = 'ellipse';
    case Text = 'text';

    public function getLabel(): string
    {
        return match ($this) {
            self::Select => 'Select',
            self::Freehand => 'Freehand',
            self::Eraser => 'Eraser',
            self::Line => 'Line',
            self::Arrow => 'Arrow',
            self::Rectangle => 'Rectangle',
            self::Ellipse => 'Ellipse',
            self::Text => 'Text',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Select => 'heroicon-o-cursor-arrow-rays',
            self::Freehand => 'heroicon-o-pencil',
            self::Eraser => 'heroicon-o-backspace',
            self::Line => 'heroicon-o-minus',
            self::Arrow => 'heroicon-o-arrow-long-right',
            self::Rectangle => 'heroicon-o-stop',
            self::Ellipse => 'heroicon-o-ellipsis-horizontal-circle',
            self::Text => 'heroicon-o-document-text',
        };
    }
}
