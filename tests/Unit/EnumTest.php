<?php

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

    it('has correct ratios', function () {
        expect(AspectRatio::Free->getRatio())->toBeNull();
        expect(AspectRatio::Square->getRatio())->toBe(1.0);
        expect(AspectRatio::Widescreen->getRatio())->toBe(16 / 9);
    });

    it('has labels', function () {
        expect(AspectRatio::Free->getLabel())->toBe('Free');
        expect(AspectRatio::Square->getLabel())->toBe('1:1');
        expect(AspectRatio::Widescreen->getLabel())->toBe('16:9');
    });

    it('can create custom ratio', function () {
        $custom = AspectRatio::custom(2.35);

        expect($custom)->toBeArray();
        expect($custom['value'])->toBe('custom');
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

    it('has labels', function () {
        expect(FilterPreset::Original->getLabel())->toBe('Original');
        expect(FilterPreset::Grayscale->getLabel())->toBe('Grayscale');
        expect(FilterPreset::Sepia->getLabel())->toBe('Sepia');
        expect(FilterPreset::HighContrast->getLabel())->toBe('High Contrast');
    });
});

describe('DrawingTool enum', function () {
    it('has expected cases', function () {
        expect(DrawingTool::cases())->toHaveCount(8);
    });

    it('has labels', function () {
        expect(DrawingTool::Select->getLabel())->toBe('Select');
        expect(DrawingTool::Freehand->getLabel())->toBe('Freehand');
        expect(DrawingTool::Text->getLabel())->toBe('Text');
    });

    it('has icons', function () {
        expect(DrawingTool::Select->getIcon())->toBe('heroicon-o-cursor-arrow-rays');
        expect(DrawingTool::Freehand->getIcon())->toBe('heroicon-o-pencil');
        expect(DrawingTool::Text->getIcon())->toBe('heroicon-o-document-text');
    });
});
