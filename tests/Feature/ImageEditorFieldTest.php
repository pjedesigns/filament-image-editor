<?php

declare(strict_types=1);

use Pjedesigns\FilamentImageEditor\Enums\Tool;
use Pjedesigns\FilamentImageEditor\Forms\Components\ImageEditor;

it('can be instantiated', function () {
    $editor = ImageEditor::make('image');

    expect($editor)->toBeInstanceOf(ImageEditor::class);
    expect($editor->getName())->toBe('image');
});

it('has correct view', function () {
    $editor = ImageEditor::make('image');

    expect($editor->getView())->toBe('filament-image-editor::forms.components.image-editor');
});

it('spans full column by default', function () {
    $editor = ImageEditor::make('image');

    // Filament 4 returns column span as an array with 'default' key
    expect($editor->getColumnSpan())->toBe(['default' => 'full']);
});

it('can chain configuration methods', function () {
    $editor = ImageEditor::make('image')
        ->tools(['crop', 'filter'])
        ->openOnSelect(true)
        ->modalSize('7xl')
        ->disk('public')
        ->directory('photos')
        ->outputFormat('webp')
        ->outputQuality(0.9)
        ->cropAspectRatios(['1:1' => 1])
        ->filterPresets(['original', 'grayscale'])
        ->drawingTools(['freehand', 'text']);

    expect($editor)->toBeInstanceOf(ImageEditor::class);
    expect($editor->getTools())->toBe(['crop', 'filter']);
    expect($editor->getModalSize())->toBe('7xl');
    expect($editor->getDisk())->toBe('public');
    expect($editor->getOutputFormat())->toBe('webp');
});

// Note: getImageUrl() requires a Filament form container context
// and is tested through integration tests. Direct testing without
// a container causes initialization errors in Filament 4.

it('accepts closures for configuration', function () {
    $editor = ImageEditor::make('image')
        ->tools(fn () => ['crop'])
        ->openOnSelect(fn () => false)
        ->modalSize(fn () => '5xl');

    expect($editor->getTools())->toBe(['crop']);
    expect($editor->shouldOpenOnSelect())->toBeFalse();
    expect($editor->getModalSize())->toBe('5xl');
});

it('can configure spatie media library options', function () {
    $editor = ImageEditor::make('image')
        ->collection('gallery')
        ->conversion('thumb')
        ->responsiveImages()
        ->customProperties(['source' => 'editor']);

    expect($editor->isUsingSpatieMediaLibrary())->toBeTrue();
    expect($editor->getCollection())->toBe('gallery');
    expect($editor->getConversion())->toBe('thumb');
    expect($editor->hasResponsiveImages())->toBeTrue();
    expect($editor->getCustomProperties())->toBe(['source' => 'editor']);
});

it('generates storage path correctly', function () {
    $editor = ImageEditor::make('image')
        ->directory('uploads/images');

    expect($editor->getStoragePath('test.jpg'))->toBe('uploads/images/test.jpg');
});

it('calculates max file size in bytes', function () {
    $editor = ImageEditor::make('image')
        ->maxFileSize(5 * 1024); // 5MB in KB

    expect($editor->getMaxFileSizeBytes())->toBe(5 * 1024 * 1024);
});

it('generates accepted file types string', function () {
    $editor = ImageEditor::make('image')
        ->acceptedFileTypes(['image/jpeg', 'image/png']);

    expect($editor->getAcceptedFileTypesString())->toBe('image/jpeg,image/png');
});

it('passes openOnSelect to editor config', function () {
    $editorTrue = ImageEditor::make('image')
        ->openOnSelect(true);

    $editorFalse = ImageEditor::make('image')
        ->openOnSelect(false);

    expect($editorTrue->shouldOpenOnSelect())->toBeTrue();
    expect($editorTrue->getEditorConfig()['openOnSelect'])->toBeTrue();

    expect($editorFalse->shouldOpenOnSelect())->toBeFalse();
    expect($editorFalse->getEditorConfig()['openOnSelect'])->toBeFalse();
});

it('defaults openOnSelect to true', function () {
    $editor = ImageEditor::make('image');

    expect($editor->shouldOpenOnSelect())->toBeTrue();
    expect($editor->getEditorConfig()['openOnSelect'])->toBeTrue();
});

it('accepts Tool enum values for tools configuration', function () {
    $editor = ImageEditor::make('image')
        ->tools([Tool::Crop, Tool::Filter]);

    expect($editor->getTools())->toBe(['crop', 'filter']);
});

it('mixes Tool enum and string values for tools', function () {
    $editor = ImageEditor::make('image')
        ->tools([Tool::Crop, 'draw']);

    expect($editor->getTools())->toBe(['crop', 'draw']);
});

it('returns config defaults when no tools specified', function () {
    $editor = ImageEditor::make('image');

    expect($editor->getTools())->toBe(['crop', 'filter', 'draw']);
});

it('generates editor config with all expected nested keys', function () {
    $editor = ImageEditor::make('image');
    $config = $editor->getEditorConfig();

    // Top-level keys
    expect($config)->toHaveKeys([
        'tools', 'openOnSelect', 'modalSize',
        'crop', 'filters', 'draw', 'output',
        'validation', 'history', 'previewMaxSize', 'preserveFilenames',
    ]);

    // Crop sub-keys
    expect($config['crop'])->toHaveKeys([
        'aspectRatios', 'defaultAspectRatio', 'minWidth', 'minHeight',
        'maxWidth', 'maxHeight', 'enableRotation', 'enableFlip',
    ]);

    // Filter sub-keys
    expect($config['filters'])->toHaveKeys([
        'presets', 'adjustments', 'disabled', 'adjustmentsDisabled',
    ]);

    // Draw sub-keys
    expect($config['draw'])->toHaveKeys([
        'tools', 'defaultStrokeColor', 'defaultStrokeWidth',
        'defaultFillColor', 'colorPalette', 'fonts', 'disabled',
    ]);

    // Output sub-keys
    expect($config['output'])->toHaveKeys([
        'format', 'quality', 'maxWidth', 'maxHeight',
    ]);

    // Validation sub-keys
    expect($config['validation'])->toHaveKeys([
        'acceptedTypes', 'maxFileSize', 'minWidth', 'minHeight',
    ]);

    // History sub-keys
    expect($config['history'])->toHaveKeys([
        'limit', 'keyboardShortcuts',
    ]);
});

it('reflects configured values in editor config', function () {
    $editor = ImageEditor::make('image')
        ->tools(['crop'])
        ->openOnSelect(false)
        ->modalSize('5xl')
        ->cropAspectRatios(['1:1' => 1.0])
        ->defaultAspectRatio('1:1')
        ->enableRotation(false)
        ->enableFlip(false)
        ->filterPresets(['grayscale'])
        ->adjustments(['brightness'])
        ->disableFilters()
        ->disableAdjustments()
        ->drawingTools(['freehand'])
        ->defaultStrokeColor('#FF0000')
        ->defaultStrokeWidth(8)
        ->defaultFillColor('#00FF00')
        ->disableDrawing()
        ->outputFormat('png')
        ->outputQuality(0.8)
        ->maxOutputSize(1920, 1080)
        ->maxFileSize(2048)
        ->minDimensions(100, 100)
        ->historyLimit(25)
        ->enableKeyboardShortcuts(false)
        ->shouldPreserveFilenames();

    $config = $editor->getEditorConfig();

    expect($config['tools'])->toBe(['crop']);
    expect($config['openOnSelect'])->toBeFalse();
    expect($config['modalSize'])->toBe('5xl');
    expect($config['crop']['aspectRatios'])->toBe(['1:1' => 1.0]);
    expect($config['crop']['defaultAspectRatio'])->toBe('1:1');
    expect($config['crop']['enableRotation'])->toBeFalse();
    expect($config['crop']['enableFlip'])->toBeFalse();
    expect($config['filters']['presets'])->toBe(['grayscale']);
    expect($config['filters']['adjustments'])->toBe(['brightness']);
    expect($config['filters']['disabled'])->toBeTrue();
    expect($config['filters']['adjustmentsDisabled'])->toBeTrue();
    expect($config['draw']['tools'])->toBe(['freehand']);
    expect($config['draw']['defaultStrokeColor'])->toBe('#FF0000');
    expect($config['draw']['defaultStrokeWidth'])->toBe(8);
    expect($config['draw']['defaultFillColor'])->toBe('#00FF00');
    expect($config['draw']['disabled'])->toBeTrue();
    expect($config['output']['format'])->toBe('png');
    expect($config['output']['quality'])->toBe(0.8);
    expect($config['output']['maxWidth'])->toBe(1920);
    expect($config['output']['maxHeight'])->toBe(1080);
    expect($config['validation']['maxFileSize'])->toBe(2048 * 1024);
    expect($config['validation']['minWidth'])->toBe(100);
    expect($config['validation']['minHeight'])->toBe(100);
    expect($config['history']['limit'])->toBe(25);
    expect($config['history']['keyboardShortcuts'])->toBeFalse();
    expect($config['preserveFilenames'])->toBeTrue();
});

it('can configure preview max height', function () {
    $editor = ImageEditor::make('image')
        ->previewMaxHeight(500);

    expect($editor->getPreviewMaxHeight())->toBe(500);
});

it('returns default preview max height', function () {
    $editor = ImageEditor::make('image');

    expect($editor->getPreviewMaxHeight())->toBe(400);
});

it('returns default preview max size', function () {
    $editor = ImageEditor::make('image');

    expect($editor->getPreviewMaxSize())->toBe(2000);
});

it('can use closure for preview max height', function () {
    $editor = ImageEditor::make('image')
        ->previewMaxHeight(fn () => 600);

    expect($editor->getPreviewMaxHeight())->toBe(600);
});

it('returns default accepted file types from config', function () {
    $editor = ImageEditor::make('image');

    $types = $editor->getAcceptedFileTypes();

    expect($types)->toContain('image/jpeg', 'image/png', 'image/webp');
});

it('returns default max file size from config', function () {
    $editor = ImageEditor::make('image');

    expect($editor->getMaxFileSize())->toBe(10 * 1024);
    expect($editor->getMaxFileSizeBytes())->toBe(10 * 1024 * 1024);
});

it('returns null for min dimensions by default', function () {
    $editor = ImageEditor::make('image');

    expect($editor->getMinWidth())->toBeNull();
    expect($editor->getMinHeight())->toBeNull();
});
