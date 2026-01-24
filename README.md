# Filament Image Editor

A powerful, client-side image editor plugin for Laravel Filament applications with crop, filter, and annotation tools.

## Features

- 🖼️ **Crop & Transform** - Crop with aspect ratio presets, rotate, and flip images
- 🎨 **Filters & Adjustments** - Apply preset filters and adjust brightness, contrast, saturation
- ✏️ **Draw & Annotate** - Freehand drawing, shapes, arrows, and text annotations
- ⏪ **Undo/Redo** - Full history support with keyboard shortcuts
- 📱 **Responsive** - Works on desktop and tablet devices
- 🌙 **Dark Mode** - Full dark mode support matching Filament's theme
- 💾 **Flexible Storage** - Works with Laravel Storage or Spatie Media Library

## Requirements

- PHP 8.2+
- Laravel 12+
- Filament v4/v5
- Livewire v3/v4

## Installation

Install the package via Composer:

```bash
composer require pjedesigns/filament-image-editor
```

Publish the config file (optional):

```bash
php artisan vendor:publish --tag="filament-image-editor-config"
```

## Basic Usage

Use the `ImageEditor` field in your Filament form:

```php
use Pjedesigns\FilamentImageEditor\Forms\Components\ImageEditor;

public static function form(Form $form): Form
{
    return $form->schema([
        ImageEditor::make('avatar')
            ->label('Profile Picture')
            ->required(),
    ]);
}
```

## Configuration

### Storage Options

Configure how images are stored:

```php
ImageEditor::make('photo')
    ->disk('public')
    ->directory('photos')
    ->visibility('public')
    ->shouldPreserveFilenames()  // Keep original filename (slugified)
```

#### Filename Options

By default, images are saved with a UUID filename (e.g., `550e8400-e29b-41d4-a716-446655440000.jpg`). Use `shouldPreserveFilenames()` to keep the original filename:

```php
ImageEditor::make('photo')
    ->shouldPreserveFilenames()  // "My Vacation Photo.PNG" → "my-vacation-photo.jpg"
```

The original filename is slugified (lowercase, special characters replaced with hyphens) and the extension is set based on the output format.

### With Spatie Media Library

> **Important:** This component only supports single-file collections. When defining your Spatie Media Library collection on the model, you must use `->singleFile()`:
>
> ```php
> // In your Model
> public function registerMediaCollections(): void
> {
>     $this->addMediaCollection('avatar')
>         ->singleFile();  // Required for ImageEditor
> }
> ```

```php
ImageEditor::make('photo')
    ->collection('photos')
    ->conversion('thumb')
    ->responsiveImages()
    ->customProperties(['source' => 'editor'])
```

### Editor Behavior

```php
ImageEditor::make('image')
    ->openOnSelect(true)    // Open editor immediately when file selected (default)
    ->openOnSelect(false)   // Skip editor - upload image directly, edit later via "Edit" button
    ->modalSize('6xl')      // Modal size (sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl, 7xl, full)
    ->tools(['crop', 'filter', 'draw'])  // Available tools
```

When `openOnSelect(false)` is set:
- Images are uploaded directly without opening the editor
- The thumbnail appears with an "Edit" button overlay
- Users can optionally click "Edit" to make changes later

### Crop Options

```php
ImageEditor::make('image')
    ->cropAspectRatios([
        'free' => null,
        '1:1' => 1,
        '16:9' => 16/9,
        'cinematic' => 2.35,
    ])
    ->defaultAspectRatio('16:9')
    ->cropMinSize(width: 100, height: 100)
    ->cropMaxSize(width: 4000, height: 4000)
    ->enableRotation(true)
    ->enableFlip(true)
```

### Filter Options

```php
ImageEditor::make('image')
    ->filterPresets([
        'original', 'grayscale', 'sepia', 'vintage',
        'warm', 'cool', 'dramatic', 'fade', 'vivid',
    ])
    ->adjustments(['brightness', 'contrast', 'saturation'])
    ->disableFilters()      // Only show adjustments
    ->disableAdjustments()  // Only show filter presets
```

### Drawing Options

```php
ImageEditor::make('image')
    ->drawingTools(['select', 'freehand', 'eraser', 'line', 'arrow', 'rectangle', 'ellipse', 'text'])
    ->defaultStrokeColor('#FF0000')
    ->defaultStrokeWidth(4)
    ->defaultFillColor('transparent')
    ->colorPalette([
        '#FFFFFF', '#000000', '#FF0000', '#00FF00', '#0000FF',
    ])
    ->fonts(['Arial', 'Helvetica', 'Georgia'])
    ->disableDrawing()  // Remove drawing tool entirely
```

### Output Options

```php
ImageEditor::make('image')
    ->outputFormat('webp')      // jpeg, png, webp
    ->outputQuality(0.85)       // 0.0 to 1.0
    ->maxOutputSize(width: 2000, height: 2000)
```

### Validation

```php
ImageEditor::make('image')
    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
    ->maxFileSize(5 * 1024)     // 5MB in KB
    ->minDimensions(width: 800, height: 600)  // Warning threshold
```

### History & Shortcuts

```php
ImageEditor::make('image')
    ->historyLimit(50)
    ->enableKeyboardShortcuts(true)
```

## Keyboard Shortcuts

When the editor is open:

| Shortcut | Action |
|----------|--------|
| `Ctrl/Cmd + Z` | Undo |
| `Ctrl/Cmd + Y` | Redo |
| `Ctrl/Cmd + Shift + Z` | Redo |
| `Delete` / `Backspace` | Delete selected shape |
| `Ctrl/Cmd + D` | Duplicate selected |
| `Escape` | Deselect / Cancel |

## Zoom & Pan Controls

The editor provides several ways to zoom and pan around the image:

### Zooming
- **Mouse wheel** - Scroll up/down to zoom in/out (zooms to cursor position)
- **Zoom dropdown** - Select preset zoom levels (Fit, 50%, 100%, 200%)
- **Zoom buttons** - Use +/- buttons in the bottom toolbar

### Panning (when zoomed in)
- **Alt + Click & Drag** - Hold Alt key and drag to pan the canvas
- **Middle Mouse Button** - Click and drag with middle mouse button to pan

The "Fit" option in the zoom dropdown resets the view to fit the entire image in the viewport.

## Standalone Livewire Component

You can also use the editor as a standalone Livewire component:

```blade
<livewire:image-editor-modal
    wire:model="editedImage"
    :source="$originalImageUrl"
    :config="[
        'tools' => ['crop', 'filter'],
        'cropAspectRatios' => ['1:1' => 1, '16:9' => 16/9],
    ]"
/>
```

## JavaScript API

For programmatic usage outside of Livewire:

```javascript
window.FilamentImageEditor.open({
    source: '/path/to/image.jpg',
    tools: ['crop', 'filter', 'draw'],
    onApply: (file) => {
        console.log('Edited image:', file);
    },
    onCancel: () => {
        console.log('User cancelled');
    }
});
```

## Configuration File

After publishing the config, you can set defaults in `config/filament-image-editor.php`:

```php
return [
    'storage' => [
        'disk' => 'public',
        'directory' => 'images',
        'visibility' => 'public',
    ],

    'output' => [
        'format' => 'jpeg',
        'quality' => 0.92,
    ],

    'tools' => ['crop', 'filter', 'draw'],

    'crop' => [
        'aspect_ratios' => [
            'free' => null,
            '1:1' => 1,
            '4:3' => 4/3,
            '16:9' => 16/9,
        ],
        'enable_rotation' => true,
        'enable_flip' => true,
    ],

    // ... more options
];
```

## Testing

### Running Tests in Your Application

For full test coverage including Livewire integration tests, publish the tests to your Laravel application:

```bash
# Publish tests to your application
php artisan vendor:publish --tag=filament-image-editor-tests

# Run the tests
php artisan test tests/Feature/FilamentImageEditor
```

This publishes tests to `tests/Feature/FilamentImageEditor/` and runs all 44 tests including:
- **Storage Tests** - Saving images to disk, directory configuration, file extensions
- **Spatie Media Library Tests** - Collection, conversion, responsive images configuration
- **Field Configuration Tests** - All form field options and closures

### Package Development

When developing the package itself:

```bash
# Install dependencies
composer install

# Run standalone tests (skips Livewire integration tests)
composer test

# Or run via the parent Laravel application for full coverage
cd /path/to/laravel-app
php artisan test packages/pjedesigns/filament-image-editor/tests
```

> **Note:** Livewire integration tests require a full Laravel application context and will be skipped when running standalone via `composer test`. Use the publish method above or run via a Laravel application for complete test coverage.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email paul@pjedesigns.com instead of using the issue tracker.

## Credits

- [Paul Egan](https://github.com/pjedesigns)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
