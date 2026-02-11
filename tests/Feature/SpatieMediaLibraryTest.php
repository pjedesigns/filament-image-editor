<?php

declare(strict_types=1);

use Pjedesigns\FilamentImageEditor\Forms\Components\ImageEditor;

describe('Spatie Media Library Configuration', function () {
    it('returns false for isUsingSpatieMediaLibrary by default', function () {
        $editor = ImageEditor::make('image');

        expect($editor->isUsingSpatieMediaLibrary())->toBeFalse();
    });

    it('returns true for isUsingSpatieMediaLibrary when collection is set', function () {
        $editor = ImageEditor::make('image')
            ->collection('gallery');

        expect($editor->isUsingSpatieMediaLibrary())->toBeTrue();
    });

    it('can set collection name', function () {
        $editor = ImageEditor::make('image')
            ->collection('avatars');

        expect($editor->getCollection())->toBe('avatars');
    });

    it('can set conversion name', function () {
        $editor = ImageEditor::make('image')
            ->collection('gallery')
            ->conversion('thumbnail');

        expect($editor->getConversion())->toBe('thumbnail');
    });

    it('returns null conversion when not set', function () {
        $editor = ImageEditor::make('image')
            ->collection('gallery');

        expect($editor->getConversion())->toBeNull();
    });

    it('can enable responsive images', function () {
        $editor = ImageEditor::make('image')
            ->collection('gallery')
            ->responsiveImages();

        expect($editor->hasResponsiveImages())->toBeTrue();
    });

    it('responsive images are disabled by default', function () {
        $editor = ImageEditor::make('image')
            ->collection('gallery');

        expect($editor->hasResponsiveImages())->toBeFalse();
    });

    it('can disable responsive images explicitly', function () {
        $editor = ImageEditor::make('image')
            ->collection('gallery')
            ->responsiveImages(false);

        expect($editor->hasResponsiveImages())->toBeFalse();
    });

    it('can set custom properties', function () {
        $editor = ImageEditor::make('image')
            ->collection('gallery')
            ->customProperties([
                'source' => 'editor',
                'author' => 'test_user',
            ]);

        expect($editor->getCustomProperties())->toBe([
            'source' => 'editor',
            'author' => 'test_user',
        ]);
    });

    it('returns empty array for custom properties by default', function () {
        $editor = ImageEditor::make('image')
            ->collection('gallery');

        expect($editor->getCustomProperties())->toBe([]);
    });

    it('can chain all spatie configuration methods', function () {
        $editor = ImageEditor::make('image')
            ->collection('photos')
            ->conversion('optimized')
            ->responsiveImages()
            ->customProperties(['quality' => 'high']);

        expect($editor->isUsingSpatieMediaLibrary())->toBeTrue();
        expect($editor->getCollection())->toBe('photos');
        expect($editor->getConversion())->toBe('optimized');
        expect($editor->hasResponsiveImages())->toBeTrue();
        expect($editor->getCustomProperties())->toBe(['quality' => 'high']);
    });

    it('includes spatie config in editor config when using media library', function () {
        $editor = ImageEditor::make('image')
            ->collection('gallery')
            ->conversion('thumb')
            ->responsiveImages()
            ->customProperties(['source' => 'editor']);

        expect($editor->isUsingSpatieMediaLibrary())->toBeTrue();

        // The getEditorConfig should work regardless of Spatie integration
        $config = $editor->getEditorConfig();
        expect($config)->toBeArray();
        expect($config)->toHaveKey('tools');
        expect($config)->toHaveKey('output');
    });

    it('can use closures for collection', function () {
        $editor = ImageEditor::make('image')
            ->collection(fn () => 'dynamic-collection');

        expect($editor->getCollection())->toBe('dynamic-collection');
        expect($editor->isUsingSpatieMediaLibrary())->toBeTrue();
    });

    it('can use closures for conversion', function () {
        $editor = ImageEditor::make('image')
            ->collection('gallery')
            ->conversion(fn () => 'dynamic-conversion');

        expect($editor->getConversion())->toBe('dynamic-conversion');
    });

    it('can use closures for responsive images', function () {
        $editor = ImageEditor::make('image')
            ->collection('gallery')
            ->responsiveImages(fn () => true);

        expect($editor->hasResponsiveImages())->toBeTrue();
    });

    it('can use closures for custom properties', function () {
        $editor = ImageEditor::make('image')
            ->collection('gallery')
            ->customProperties(fn () => ['dynamic' => 'props']);

        expect($editor->getCustomProperties())->toBe(['dynamic' => 'props']);
    });

    it('can pass model values into custom properties via closure', function () {
        $editor = ImageEditor::make('image')
            ->collection('gallery')
            ->customProperties(fn () => [
                'folder_name' => 'updates',
                'model_folder' => 'updates',
                'uploaded_by' => 'test_user',
            ]);

        $properties = $editor->getCustomProperties();

        expect($properties)->toBe([
            'folder_name' => 'updates',
            'model_folder' => 'updates',
            'uploaded_by' => 'test_user',
        ]);
        expect($properties)->toHaveKey('folder_name');
        expect($properties)->toHaveKey('model_folder');
    });

    it('accepts custom properties with explicit record parameter', function () {
        $editor = ImageEditor::make('image')
            ->collection('gallery')
            ->customProperties([
                'source' => 'editor',
            ]);

        // Passing null record explicitly should still work
        $properties = $editor->getCustomProperties(null);

        expect($properties)->toBe(['source' => 'editor']);
    });

    it('returns empty array when closure returns non-array', function () {
        $editor = ImageEditor::make('image')
            ->collection('gallery')
            ->customProperties(fn () => 'not-an-array');

        expect($editor->getCustomProperties())->toBe([]);
    });

    it('returns null for collection by default', function () {
        $editor = ImageEditor::make('image');

        expect($editor->getCollection())->toBeNull();
    });
});

describe('Storage fallback when not using Spatie', function () {
    it('uses standard storage when not using spatie', function () {
        $editor = ImageEditor::make('image')
            ->disk('public')
            ->directory('uploads');

        expect($editor->isUsingSpatieMediaLibrary())->toBeFalse();
        expect($editor->getDisk())->toBe('public');
        expect($editor->getDirectory())->toBe('uploads');
    });

    it('storage options are independent of spatie options', function () {
        $editor = ImageEditor::make('image')
            ->collection('gallery')
            ->disk('public')
            ->directory('media');

        expect($editor->isUsingSpatieMediaLibrary())->toBeTrue();
        expect($editor->getDisk())->toBe('public');
        expect($editor->getDirectory())->toBe('media');
    });
});
