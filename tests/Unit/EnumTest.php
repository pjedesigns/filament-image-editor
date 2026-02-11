<?php

declare(strict_types=1);

use Pjedesigns\FilamentImageEditor\Enums\AspectRatio;
use Pjedesigns\FilamentImageEditor\Enums\DrawingTool;
use Pjedesigns\FilamentImageEditor\Enums\FilterPreset;
use Pjedesigns\FilamentImageEditor\Enums\OutputFormat;
use Pjedesigns\FilamentImageEditor\Enums\Tool;

describe('Tool enum', function () {
    it('has expected cases', function () {
        expect(Tool::cases())->toHaveCount(3);
        expect(Tool::Crop->value)->toBe('crop');
        expect(Tool::Filter->value)->toBe('filter');
        expect(Tool::Draw->value)->toBe('draw');
    });

    it('has labels', function () {
        expect(Tool::Crop->getLabel())->toBe('Crop & Transform');
        expect(Tool::Filter->getLabel())->toBe('Filters & Adjustments');
        expect(Tool::Draw->getLabel())->toBe('Draw & Annotate');
    });

    it('has icons', function () {
        expect(Tool::Crop->getIcon())->toBe('heroicon-o-scissors');
        expect(Tool::Filter->getIcon())->toBe('heroicon-o-adjustments-horizontal');
        expect(Tool::Draw->getIcon())->toBe('heroicon-o-paint-brush');
    });
});

describe('AspectRatio enum', function () {
    it('has expected cases', function () {
        expect(AspectRatio::cases())->toHaveCount(8);
    });

    it('has correct ratios for all cases', function () {
        expect(AspectRatio::Free->getRatio())->toBeNull();
        expect(AspectRatio::Square->getRatio())->toBe(1.0);
        expect(AspectRatio::Standard->getRatio())->toBe(4 / 3);
        expect(AspectRatio::Photo->getRatio())->toBe(3 / 2);
        expect(AspectRatio::Widescreen->getRatio())->toBe(16 / 9);
        expect(AspectRatio::Portrait->getRatio())->toBe(9 / 16);
        expect(AspectRatio::PortraitPhoto->getRatio())->toBe(2 / 3);
        expect(AspectRatio::PortraitStandard->getRatio())->toBe(3 / 4);
    });

    it('has labels for all cases', function () {
        expect(AspectRatio::Free->getLabel())->toBe('Free');
        expect(AspectRatio::Square->getLabel())->toBe('1:1');
        expect(AspectRatio::Standard->getLabel())->toBe('4:3');
        expect(AspectRatio::Photo->getLabel())->toBe('3:2');
        expect(AspectRatio::Widescreen->getLabel())->toBe('16:9');
        expect(AspectRatio::Portrait->getLabel())->toBe('9:16');
        expect(AspectRatio::PortraitPhoto->getLabel())->toBe('2:3');
        expect(AspectRatio::PortraitStandard->getLabel())->toBe('3:4');
    });

    it('has correct string values', function () {
        expect(AspectRatio::Free->value)->toBe('free');
        expect(AspectRatio::Square->value)->toBe('1:1');
        expect(AspectRatio::Standard->value)->toBe('4:3');
        expect(AspectRatio::Photo->value)->toBe('3:2');
        expect(AspectRatio::Widescreen->value)->toBe('16:9');
        expect(AspectRatio::Portrait->value)->toBe('9:16');
        expect(AspectRatio::PortraitPhoto->value)->toBe('2:3');
        expect(AspectRatio::PortraitStandard->value)->toBe('3:4');
    });

    it('can create custom ratio', function () {
        $custom = AspectRatio::custom(2.35);

        expect($custom)->toBeArray();
        expect($custom['value'])->toBe('custom');
        expect($custom['label'])->toBe('Custom');
        expect($custom['ratio'])->toBe(2.35);
    });
});

describe('OutputFormat enum', function () {
    it('has expected cases', function () {
        expect(OutputFormat::cases())->toHaveCount(3);
        expect(OutputFormat::Jpeg->value)->toBe('jpeg');
        expect(OutputFormat::Png->value)->toBe('png');
        expect(OutputFormat::Webp->value)->toBe('webp');
    });

    it('has labels', function () {
        expect(OutputFormat::Jpeg->getLabel())->toBe('JPEG');
        expect(OutputFormat::Png->getLabel())->toBe('PNG');
        expect(OutputFormat::Webp->getLabel())->toBe('WebP');
    });

    it('has correct mime types', function () {
        expect(OutputFormat::Jpeg->getMimeType())->toBe('image/jpeg');
        expect(OutputFormat::Png->getMimeType())->toBe('image/png');
        expect(OutputFormat::Webp->getMimeType())->toBe('image/webp');
    });

    it('has correct extensions', function () {
        expect(OutputFormat::Jpeg->getExtension())->toBe('jpg');
        expect(OutputFormat::Png->getExtension())->toBe('png');
        expect(OutputFormat::Webp->getExtension())->toBe('webp');
    });

    it('knows which formats support quality', function () {
        expect(OutputFormat::Jpeg->supportsQuality())->toBeTrue();
        expect(OutputFormat::Webp->supportsQuality())->toBeTrue();
        expect(OutputFormat::Png->supportsQuality())->toBeFalse();
    });
});

describe('FilterPreset enum', function () {
    it('has expected cases', function () {
        expect(FilterPreset::cases())->toHaveCount(10);
    });

    it('has correct string values', function () {
        expect(FilterPreset::Original->value)->toBe('original');
        expect(FilterPreset::Grayscale->value)->toBe('grayscale');
        expect(FilterPreset::Sepia->value)->toBe('sepia');
        expect(FilterPreset::Vintage->value)->toBe('vintage');
        expect(FilterPreset::Warm->value)->toBe('warm');
        expect(FilterPreset::Cool->value)->toBe('cool');
        expect(FilterPreset::HighContrast->value)->toBe('high-contrast');
        expect(FilterPreset::Fade->value)->toBe('fade');
        expect(FilterPreset::Dramatic->value)->toBe('dramatic');
        expect(FilterPreset::Vivid->value)->toBe('vivid');
    });

    it('has labels for all cases', function () {
        expect(FilterPreset::Original->getLabel())->toBe('Original');
        expect(FilterPreset::Grayscale->getLabel())->toBe('Grayscale');
        expect(FilterPreset::Sepia->getLabel())->toBe('Sepia');
        expect(FilterPreset::Vintage->getLabel())->toBe('Vintage');
        expect(FilterPreset::Warm->getLabel())->toBe('Warm');
        expect(FilterPreset::Cool->getLabel())->toBe('Cool');
        expect(FilterPreset::HighContrast->getLabel())->toBe('High Contrast');
        expect(FilterPreset::Fade->getLabel())->toBe('Fade');
        expect(FilterPreset::Dramatic->getLabel())->toBe('Dramatic');
        expect(FilterPreset::Vivid->getLabel())->toBe('Vivid');
    });
});

describe('DrawingTool enum', function () {
    it('has expected cases', function () {
        expect(DrawingTool::cases())->toHaveCount(8);
    });

    it('has correct string values', function () {
        expect(DrawingTool::Select->value)->toBe('select');
        expect(DrawingTool::Freehand->value)->toBe('freehand');
        expect(DrawingTool::Eraser->value)->toBe('eraser');
        expect(DrawingTool::Line->value)->toBe('line');
        expect(DrawingTool::Arrow->value)->toBe('arrow');
        expect(DrawingTool::Rectangle->value)->toBe('rectangle');
        expect(DrawingTool::Ellipse->value)->toBe('ellipse');
        expect(DrawingTool::Text->value)->toBe('text');
    });

    it('has labels for all cases', function () {
        expect(DrawingTool::Select->getLabel())->toBe('Select');
        expect(DrawingTool::Freehand->getLabel())->toBe('Freehand');
        expect(DrawingTool::Eraser->getLabel())->toBe('Eraser');
        expect(DrawingTool::Line->getLabel())->toBe('Line');
        expect(DrawingTool::Arrow->getLabel())->toBe('Arrow');
        expect(DrawingTool::Rectangle->getLabel())->toBe('Rectangle');
        expect(DrawingTool::Ellipse->getLabel())->toBe('Ellipse');
        expect(DrawingTool::Text->getLabel())->toBe('Text');
    });

    it('has icons for all cases', function () {
        expect(DrawingTool::Select->getIcon())->toBe('heroicon-o-cursor-arrow-rays');
        expect(DrawingTool::Freehand->getIcon())->toBe('heroicon-o-pencil');
        expect(DrawingTool::Eraser->getIcon())->toBe('heroicon-o-backspace');
        expect(DrawingTool::Line->getIcon())->toBe('heroicon-o-minus');
        expect(DrawingTool::Arrow->getIcon())->toBe('heroicon-o-arrow-long-right');
        expect(DrawingTool::Rectangle->getIcon())->toBe('heroicon-o-stop');
        expect(DrawingTool::Ellipse->getIcon())->toBe('heroicon-o-ellipsis-horizontal-circle');
        expect(DrawingTool::Text->getIcon())->toBe('heroicon-o-document-text');
    });
});
