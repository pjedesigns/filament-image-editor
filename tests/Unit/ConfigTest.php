<?php

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
