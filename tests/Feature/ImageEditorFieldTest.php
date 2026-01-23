<?php

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
