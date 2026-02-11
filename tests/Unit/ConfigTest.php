<?php

declare(strict_types=1);

use Pjedesigns\FilamentImageEditor\Enums\OutputFormat;
use Pjedesigns\FilamentImageEditor\Forms\Components\ImageEditor;

it('has default configuration', function () {
    $editor = ImageEditor::make('test');

    expect($editor->getTools())->toContain('crop', 'filter', 'draw');
    expect($editor->shouldOpenOnSelect())->toBeTrue();
    expect($editor->getModalSize())->toBe('6xl');
});

it('can configure tools', function () {
    $editor = ImageEditor::make('test')
        ->tools(['crop', 'filter']);

    expect($editor->getTools())->toBe(['crop', 'filter']);
});

it('can configure crop aspect ratios', function () {
    $editor = ImageEditor::make('test')
        ->cropAspectRatios([
            'free' => null,
            '1:1' => 1,
            'custom' => 2.35,
        ]);

    $ratios = $editor->getCropAspectRatios();

    expect($ratios)->toHaveKey('free');
    expect($ratios)->toHaveKey('1:1');
    expect($ratios)->toHaveKey('custom');
    expect($ratios['custom'])->toBe(2.35);
});

it('can configure output format and quality', function () {
    $editor = ImageEditor::make('test')
        ->outputFormat('webp')
        ->outputQuality(0.85);

    expect($editor->getOutputFormat())->toBe('webp');
    expect($editor->getOutputQuality())->toBe(0.85);
});

it('can configure storage options', function () {
    $editor = ImageEditor::make('test')
        ->disk('s3')
        ->directory('uploads/images')
        ->visibility('private');

    expect($editor->getDisk())->toBe('s3');
    expect($editor->getDirectory())->toBe('uploads/images');
    expect($editor->getVisibility())->toBe('private');
});

it('can configure crop min/max size', function () {
    $editor = ImageEditor::make('test')
        ->cropMinSize(100, 100)
        ->cropMaxSize(4000, 4000);

    expect($editor->getCropMinWidth())->toBe(100);
    expect($editor->getCropMinHeight())->toBe(100);
    expect($editor->getCropMaxWidth())->toBe(4000);
    expect($editor->getCropMaxHeight())->toBe(4000);
});

it('can enable/disable rotation and flip', function () {
    $editor = ImageEditor::make('test')
        ->enableRotation(false)
        ->enableFlip(false);

    expect($editor->isRotationEnabled())->toBeFalse();
    expect($editor->isFlipEnabled())->toBeFalse();
});

it('can configure filter presets', function () {
    $editor = ImageEditor::make('test')
        ->filterPresets(['original', 'grayscale', 'sepia']);

    expect($editor->getFilterPresets())->toBe(['original', 'grayscale', 'sepia']);
});

it('can configure adjustments', function () {
    $editor = ImageEditor::make('test')
        ->adjustments(['brightness', 'contrast']);

    expect($editor->getAdjustments())->toBe(['brightness', 'contrast']);
});

it('can disable filters and adjustments', function () {
    $editor = ImageEditor::make('test')
        ->disableFilters()
        ->disableAdjustments();

    expect($editor->areFiltersDisabled())->toBeTrue();
    expect($editor->areAdjustmentsDisabled())->toBeTrue();
});

it('can configure drawing tools', function () {
    $editor = ImageEditor::make('test')
        ->drawingTools(['freehand', 'text', 'rectangle']);

    expect($editor->getDrawingTools())->toBe(['freehand', 'text', 'rectangle']);
});

it('can configure drawing defaults', function () {
    $editor = ImageEditor::make('test')
        ->defaultStrokeColor('#FF0000')
        ->defaultStrokeWidth(8)
        ->defaultFillColor('#00FF00');

    expect($editor->getDefaultStrokeColor())->toBe('#FF0000');
    expect($editor->getDefaultStrokeWidth())->toBe(8);
    expect($editor->getDefaultFillColor())->toBe('#00FF00');
});

it('can configure color palette', function () {
    $palette = ['#000000', '#FFFFFF', '#FF0000'];

    $editor = ImageEditor::make('test')
        ->colorPalette($palette);

    expect($editor->getColorPalette())->toBe($palette);
});

it('can configure fonts', function () {
    $fonts = ['Arial', 'Helvetica', 'Georgia'];

    $editor = ImageEditor::make('test')
        ->fonts($fonts);

    expect($editor->getFonts())->toBe($fonts);
});

it('can disable drawing', function () {
    $editor = ImageEditor::make('test')
        ->disableDrawing();

    expect($editor->isDrawingDisabled())->toBeTrue();
});

it('can configure validation', function () {
    $editor = ImageEditor::make('test')
        ->acceptedFileTypes(['image/jpeg', 'image/png'])
        ->maxFileSize(5 * 1024)
        ->minDimensions(400, 300);

    expect($editor->getAcceptedFileTypes())->toBe(['image/jpeg', 'image/png']);
    expect($editor->getMaxFileSize())->toBe(5 * 1024);
    expect($editor->getMinWidth())->toBe(400);
    expect($editor->getMinHeight())->toBe(300);
});

it('can configure history', function () {
    $editor = ImageEditor::make('test')
        ->historyLimit(100)
        ->enableKeyboardShortcuts(false);

    expect($editor->getHistoryLimit())->toBe(100);
    expect($editor->hasKeyboardShortcuts())->toBeFalse();
});

it('generates complete editor config', function () {
    $editor = ImageEditor::make('test')
        ->tools(['crop', 'filter'])
        ->cropAspectRatios(['1:1' => 1])
        ->filterPresets(['original', 'grayscale'])
        ->outputFormat('webp');

    $config = $editor->getEditorConfig();

    expect($config)->toHaveKey('tools');
    expect($config)->toHaveKey('crop');
    expect($config)->toHaveKey('filters');
    expect($config)->toHaveKey('draw');
    expect($config)->toHaveKey('output');
    expect($config)->toHaveKey('validation');
    expect($config)->toHaveKey('history');

    expect($config['tools'])->toBe(['crop', 'filter']);
    expect($config['output']['format'])->toBe('webp');
});

it('can set default aspect ratio', function () {
    $editor = ImageEditor::make('test')
        ->defaultAspectRatio('1:1');

    expect($editor->getDefaultAspectRatio())->toBe('1:1');
});

it('returns free as default aspect ratio', function () {
    $editor = ImageEditor::make('test');

    expect($editor->getDefaultAspectRatio())->toBe('free');
});

it('returns null for crop max dimensions by default', function () {
    $editor = ImageEditor::make('test');

    expect($editor->getCropMaxWidth())->toBeNull();
    expect($editor->getCropMaxHeight())->toBeNull();
});

it('returns config defaults for crop min dimensions', function () {
    $editor = ImageEditor::make('test');

    expect($editor->getCropMinWidth())->toBe(10);
    expect($editor->getCropMinHeight())->toBe(10);
});

it('rotation and flip are enabled by default', function () {
    $editor = ImageEditor::make('test');

    expect($editor->isRotationEnabled())->toBeTrue();
    expect($editor->isFlipEnabled())->toBeTrue();
});

it('can use closures for crop options', function () {
    $editor = ImageEditor::make('test')
        ->cropAspectRatios(fn () => ['16:9' => 16 / 9])
        ->defaultAspectRatio(fn () => '16:9')
        ->cropMinSize(fn () => 200, fn () => 150)
        ->cropMaxSize(fn () => 3000, fn () => 2000)
        ->enableRotation(fn () => false)
        ->enableFlip(fn () => false);

    expect($editor->getCropAspectRatios())->toBe(['16:9' => 16 / 9]);
    expect($editor->getDefaultAspectRatio())->toBe('16:9');
    expect($editor->getCropMinWidth())->toBe(200);
    expect($editor->getCropMinHeight())->toBe(150);
    expect($editor->getCropMaxWidth())->toBe(3000);
    expect($editor->getCropMaxHeight())->toBe(2000);
    expect($editor->isRotationEnabled())->toBeFalse();
    expect($editor->isFlipEnabled())->toBeFalse();
});

it('can get output mime type', function () {
    expect(ImageEditor::make('test')->outputFormat('jpeg')->getOutputMimeType())->toBe('image/jpeg');
    expect(ImageEditor::make('test')->outputFormat('jpg')->getOutputMimeType())->toBe('image/jpeg');
    expect(ImageEditor::make('test')->outputFormat('png')->getOutputMimeType())->toBe('image/png');
    expect(ImageEditor::make('test')->outputFormat('webp')->getOutputMimeType())->toBe('image/webp');
});

it('returns image/jpeg for unknown output format mime type', function () {
    $editor = ImageEditor::make('test')
        ->outputFormat('bmp');

    expect($editor->getOutputMimeType())->toBe('image/jpeg');
});

it('can configure max output size', function () {
    $editor = ImageEditor::make('test')
        ->maxOutputSize(1920, 1080);

    expect($editor->getMaxOutputWidth())->toBe(1920);
    expect($editor->getMaxOutputHeight())->toBe(1080);
});

it('returns null for max output dimensions by default', function () {
    $editor = ImageEditor::make('test');

    expect($editor->getMaxOutputWidth())->toBeNull();
    expect($editor->getMaxOutputHeight())->toBeNull();
});

it('can use OutputFormat enum for output format', function () {
    $editor = ImageEditor::make('test')
        ->outputFormat(OutputFormat::Webp);

    expect($editor->getOutputFormat())->toBe('webp');
});

it('can use closures for output options', function () {
    $editor = ImageEditor::make('test')
        ->outputFormat(fn () => 'png')
        ->outputQuality(fn () => 0.75)
        ->maxOutputSize(fn () => 800, fn () => 600);

    expect($editor->getOutputFormat())->toBe('png');
    expect($editor->getOutputQuality())->toBe(0.75);
    expect($editor->getMaxOutputWidth())->toBe(800);
    expect($editor->getMaxOutputHeight())->toBe(600);
});

it('can configure preserve filenames', function () {
    $editor = ImageEditor::make('test')
        ->shouldPreserveFilenames();

    expect($editor->getPreserveFilenames())->toBeTrue();
});

it('preserve filenames is disabled by default', function () {
    $editor = ImageEditor::make('test');

    expect($editor->getPreserveFilenames())->toBeFalse();
});

it('can set and get original filename', function () {
    $editor = ImageEditor::make('test')
        ->setOriginalFilename('photo.jpg');

    expect($editor->getOriginalFilename())->toBe('photo.jpg');
});

it('returns null for original filename by default', function () {
    $editor = ImageEditor::make('test');

    expect($editor->getOriginalFilename())->toBeNull();
});

it('generates storage path with empty directory', function () {
    $editor = ImageEditor::make('test')
        ->directory('');

    // With empty directory evaluated, getStoragePath trims leading slash
    expect($editor->getStoragePath('test.jpg'))->toBe('test.jpg');
});

it('has default storage options from config', function () {
    $editor = ImageEditor::make('test');

    expect($editor->getDisk())->toBe('public');
    expect($editor->getDirectory())->toBe('images');
    expect($editor->getVisibility())->toBe('public');
});

it('can use closures for storage options', function () {
    $editor = ImageEditor::make('test')
        ->disk(fn () => 's3')
        ->directory(fn () => 'dynamic/path')
        ->visibility(fn () => 'private')
        ->shouldPreserveFilenames(fn () => true);

    expect($editor->getDisk())->toBe('s3');
    expect($editor->getDirectory())->toBe('dynamic/path');
    expect($editor->getVisibility())->toBe('private');
    expect($editor->getPreserveFilenames())->toBeTrue();
});

it('has default drawing options from config', function () {
    $editor = ImageEditor::make('test');

    expect($editor->getDrawingTools())->toContain('freehand', 'text', 'rectangle');
    expect($editor->getDefaultStrokeColor())->toBe('#000000');
    expect($editor->getDefaultStrokeWidth())->toBe(4);
    expect($editor->getDefaultFillColor())->toBe('transparent');
    expect($editor->getColorPalette())->toBeArray();
    expect($editor->getColorPalette())->not->toBeEmpty();
    expect($editor->getFonts())->toContain('Arial');
    expect($editor->isDrawingDisabled())->toBeFalse();
});

it('can use closures for drawing options', function () {
    $editor = ImageEditor::make('test')
        ->drawingTools(fn () => ['freehand'])
        ->defaultStrokeColor(fn () => '#FF0000')
        ->defaultStrokeWidth(fn () => 10)
        ->defaultFillColor(fn () => '#0000FF')
        ->colorPalette(fn () => ['#000', '#FFF'])
        ->fonts(fn () => ['Monospace'])
        ->disableDrawing(fn () => true);

    expect($editor->getDrawingTools())->toBe(['freehand']);
    expect($editor->getDefaultStrokeColor())->toBe('#FF0000');
    expect($editor->getDefaultStrokeWidth())->toBe(10);
    expect($editor->getDefaultFillColor())->toBe('#0000FF');
    expect($editor->getColorPalette())->toBe(['#000', '#FFF']);
    expect($editor->getFonts())->toBe(['Monospace']);
    expect($editor->isDrawingDisabled())->toBeTrue();
});

it('has default filter options from config', function () {
    $editor = ImageEditor::make('test');

    expect($editor->getFilterPresets())->toContain('original', 'grayscale');
    expect($editor->getAdjustments())->toContain('brightness', 'contrast');
    expect($editor->areFiltersDisabled())->toBeFalse();
    expect($editor->areAdjustmentsDisabled())->toBeFalse();
});

it('can use closures for filter options', function () {
    $editor = ImageEditor::make('test')
        ->filterPresets(fn () => ['sepia', 'vivid'])
        ->adjustments(fn () => ['brightness'])
        ->disableFilters(fn () => true)
        ->disableAdjustments(fn () => true);

    expect($editor->getFilterPresets())->toBe(['sepia', 'vivid']);
    expect($editor->getAdjustments())->toBe(['brightness']);
    expect($editor->areFiltersDisabled())->toBeTrue();
    expect($editor->areAdjustmentsDisabled())->toBeTrue();
});

it('can re-enable filters after disabling', function () {
    $editor = ImageEditor::make('test')
        ->disableFilters()
        ->disableFilters(false);

    expect($editor->areFiltersDisabled())->toBeFalse();
});

it('can re-enable adjustments after disabling', function () {
    $editor = ImageEditor::make('test')
        ->disableAdjustments()
        ->disableAdjustments(false);

    expect($editor->areAdjustmentsDisabled())->toBeFalse();
});

it('can re-enable drawing after disabling', function () {
    $editor = ImageEditor::make('test')
        ->disableDrawing()
        ->disableDrawing(false);

    expect($editor->isDrawingDisabled())->toBeFalse();
});
