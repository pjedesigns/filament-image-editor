<?php

use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Pjedesigns\FilamentImageEditor\Livewire\ImageEditorModal;

/**
 * These tests require a full Laravel application context to properly test
 * Livewire components. When running standalone via `composer test`, these
 * tests will be skipped. Run via the parent Laravel application for full coverage:
 *
 * php artisan test packages/pjedesigns/filament-image-editor/tests
 */
beforeEach(function () {
    // Skip Livewire tests when running in standalone package mode
    // Orchestra Testbench doesn't fully support Livewire view rendering
    if (! class_exists(\Tests\TestCase::class)) {
        $this->markTestSkipped('Livewire integration tests require full Laravel application context.');
    }

    Storage::fake('public');
    Storage::fake('local');
});

it('saves edited image to storage', function () {
    $imageData = createTestImageBase64();

    Livewire::test(ImageEditorModal::class, [
        'config' => [
            'storage' => [
                'disk' => 'public',
                'directory' => 'images',
                'visibility' => 'public',
            ],
            'output' => [
                'format' => 'jpeg',
            ],
        ],
    ])
        ->call('save', $imageData)
        ->assertDispatched('image-edited');

    $files = Storage::disk('public')->files('images');

    expect($files)->toHaveCount(1);
    expect($files[0])->toMatch('/^images\/[a-f0-9\-]+\.jpg$/');
});

it('dispatches image-edited event with correct payload', function () {
    $imageData = createTestImageBase64();

    Livewire::test(ImageEditorModal::class, [
        'config' => [
            'storage' => [
                'disk' => 'public',
                'directory' => 'uploads',
                'visibility' => 'public',
            ],
            'output' => [
                'format' => 'png',
            ],
        ],
    ])
        ->call('save', $imageData)
        ->assertDispatched('image-edited', function ($eventName, $payload) {
            $data = $payload[0];

            return isset($data['path'])
                && isset($data['url'])
                && isset($data['disk'])
                && $data['disk'] === 'public'
                && str_starts_with($data['path'], 'uploads/');
        });
});

it('saves image to configured directory', function () {
    $imageData = createTestImageBase64();

    Livewire::test(ImageEditorModal::class, [
        'config' => [
            'storage' => [
                'disk' => 'public',
                'directory' => 'custom/nested/path',
                'visibility' => 'public',
            ],
            'output' => [
                'format' => 'jpeg',
            ],
        ],
    ])
        ->call('save', $imageData);

    $files = Storage::disk('public')->files('custom/nested/path');

    expect($files)->toHaveCount(1);
    expect($files[0])->toStartWith('custom/nested/path/');
});

it('saves image to configured disk', function () {
    $imageData = createTestImageBase64();

    Livewire::test(ImageEditorModal::class, [
        'config' => [
            'storage' => [
                'disk' => 'local',
                'directory' => 'images',
                'visibility' => 'private',
            ],
            'output' => [
                'format' => 'jpeg',
            ],
        ],
    ])
        ->call('save', $imageData);

    $publicFiles = Storage::disk('public')->files('images');
    $localFiles = Storage::disk('local')->files('images');

    expect($publicFiles)->toBeEmpty();
    expect($localFiles)->toHaveCount(1);
});

it('generates correct file extension for jpeg format', function () {
    $imageData = createTestImageBase64();

    Livewire::test(ImageEditorModal::class, [
        'config' => [
            'storage' => [
                'disk' => 'public',
                'directory' => 'images',
            ],
            'output' => [
                'format' => 'jpeg',
            ],
        ],
    ])
        ->call('save', $imageData);

    $files = Storage::disk('public')->files('images');

    expect($files[0])->toEndWith('.jpg');
});

it('generates correct file extension for png format', function () {
    $imageData = createTestImageBase64();

    Livewire::test(ImageEditorModal::class, [
        'config' => [
            'storage' => [
                'disk' => 'public',
                'directory' => 'images',
            ],
            'output' => [
                'format' => 'png',
            ],
        ],
    ])
        ->call('save', $imageData);

    $files = Storage::disk('public')->files('images');

    expect($files[0])->toEndWith('.png');
});

it('generates correct file extension for webp format', function () {
    $imageData = createTestImageBase64();

    Livewire::test(ImageEditorModal::class, [
        'config' => [
            'storage' => [
                'disk' => 'public',
                'directory' => 'images',
            ],
            'output' => [
                'format' => 'webp',
            ],
        ],
    ])
        ->call('save', $imageData);

    $files = Storage::disk('public')->files('images');

    expect($files[0])->toEndWith('.webp');
});

it('generates unique filenames using uuid', function () {
    $imageData = createTestImageBase64();

    Livewire::test(ImageEditorModal::class, [
        'config' => [
            'storage' => [
                'disk' => 'public',
                'directory' => 'images',
            ],
            'output' => [
                'format' => 'jpeg',
            ],
        ],
    ])
        ->call('save', $imageData);

    Livewire::test(ImageEditorModal::class, [
        'config' => [
            'storage' => [
                'disk' => 'public',
                'directory' => 'images',
            ],
            'output' => [
                'format' => 'jpeg',
            ],
        ],
    ])
        ->call('save', $imageData);

    $files = Storage::disk('public')->files('images');

    expect($files)->toHaveCount(2);
    expect($files[0])->not->toBe($files[1]);
});

it('stores valid image content', function () {
    $imageData = createTestImageBase64();

    Livewire::test(ImageEditorModal::class, [
        'config' => [
            'storage' => [
                'disk' => 'public',
                'directory' => 'images',
            ],
            'output' => [
                'format' => 'jpeg',
            ],
        ],
    ])
        ->call('save', $imageData);

    $files = Storage::disk('public')->files('images');
    $content = Storage::disk('public')->get($files[0]);

    expect($content)->not->toBeEmpty();
    expect(strlen($content))->toBeGreaterThan(0);
});

it('closes modal after successful save', function () {
    $imageData = createTestImageBase64();

    Livewire::test(ImageEditorModal::class, [
        'config' => [
            'storage' => [
                'disk' => 'public',
                'directory' => 'images',
            ],
            'output' => [
                'format' => 'jpeg',
            ],
        ],
    ])
        ->set('isOpen', true)
        ->call('save', $imageData)
        ->assertSet('isOpen', false);
});

it('sets edited image url after save', function () {
    $imageData = createTestImageBase64();

    Livewire::test(ImageEditorModal::class, [
        'config' => [
            'storage' => [
                'disk' => 'public',
                'directory' => 'images',
            ],
            'output' => [
                'format' => 'jpeg',
            ],
        ],
    ])
        ->call('save', $imageData)
        ->assertNotSet('editedImage', null);
});

it('does not save file when base64 decode fails', function () {
    // Use a string with characters outside base64 alphabet to force decode failure
    $invalidImageData = '@@@@@@@@@@@@@@@@@@@@';

    Livewire::test(ImageEditorModal::class, [
        'config' => [
            'storage' => [
                'disk' => 'public',
                'directory' => 'images',
            ],
        ],
    ])
        ->call('save', $invalidImageData)
        ->assertDispatched('notify', function ($eventName, $payload) {
            $data = $payload[0];

            return isset($data['type'])
                && $data['type'] === 'error';
        });

    $files = Storage::disk('public')->files('images');

    expect($files)->toBeEmpty();
});

it('handles base64 data with data uri prefix', function () {
    $imageData = createTestImageBase64(includePrefix: true);

    Livewire::test(ImageEditorModal::class, [
        'config' => [
            'storage' => [
                'disk' => 'public',
                'directory' => 'images',
            ],
            'output' => [
                'format' => 'jpeg',
            ],
        ],
    ])
        ->call('save', $imageData)
        ->assertDispatched('image-edited');

    $files = Storage::disk('public')->files('images');

    expect($files)->toHaveCount(1);
});

it('uses default config values when not specified', function () {
    $imageData = createTestImageBase64();

    Livewire::test(ImageEditorModal::class)
        ->call('save', $imageData)
        ->assertDispatched('image-edited');

    $defaultDisk = config('filament-image-editor.storage.disk', 'public');
    $defaultDirectory = config('filament-image-editor.storage.directory', 'images');

    $files = Storage::disk($defaultDisk)->files($defaultDirectory);

    expect($files)->toHaveCount(1);
});

it('can open editor via event', function () {
    Livewire::test(ImageEditorModal::class)
        ->assertSet('isOpen', false)
        ->dispatch('open-image-editor', [
            'source' => '/path/to/image.jpg',
            'config' => ['tools' => ['crop']],
        ])
        ->assertSet('isOpen', true)
        ->assertSet('source', '/path/to/image.jpg');
});

it('can close editor', function () {
    Livewire::test(ImageEditorModal::class)
        ->set('isOpen', true)
        ->call('close')
        ->assertSet('isOpen', false);
});

it('merges config from open event with existing config', function () {
    Livewire::test(ImageEditorModal::class, [
        'config' => [
            'tools' => ['crop', 'filter'],
            'output' => ['format' => 'jpeg'],
        ],
    ])
        ->dispatch('open-image-editor', [
            'config' => ['tools' => ['crop']],
        ])
        ->assertSet('config.tools', ['crop'])
        ->assertSet('config.output.format', 'jpeg');
});

/**
 * Create a test base64 encoded image.
 */
function createTestImageBase64(bool $includePrefix = false): string
{
    $image = imagecreatetruecolor(100, 100);
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $white);

    ob_start();
    imagejpeg($image, null, 90);
    $imageData = ob_get_clean();
    imagedestroy($image);

    $base64 = base64_encode($imageData);

    if ($includePrefix) {
        return 'data:image/jpeg;base64,'.$base64;
    }

    return $base64;
}
