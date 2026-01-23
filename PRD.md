# Product Requirements Document: Filament Image Editor

## Overview

**Package Name:** `pjedesigns/filament-image-editor`
**Version:** 1.0.0
**Author:** Paul Egan (paul@pjedesigns.com)
**License:** MIT

A powerful, client-side image editor for Laravel applications. Designed as a standalone Filament form field that provides a similar look and feel to the Spatie Media Library file upload component, with an integrated full-featured image editor that opens in a modal before/after upload.

---

## Problem Statement

Current image editing solutions for Laravel/Filament either:
1. Require expensive commercial licenses with watermarks (e.g., Pintura at ~$100-500)
2. Have poor integration with Filament's form system and theming
3. Are bloated with features, resulting in large bundle sizes (~700KB)
4. Only offer basic cropping without filters or annotation tools

This package provides a **free, open-source, MIT-licensed** alternative with:
- First-class Filament integration that matches the existing UI patterns
- Support for both Spatie Media Library and standard Laravel storage
- Full-featured editing (crop, filters, annotations) in a compact bundle
- Lazy-loaded tools for optimal initial load performance

---

## Goals

1. **Seamless Filament Integration** - Looks and feels like native Filament components
2. **Dual Storage Support** - Works with Spatie Media Library OR standard Laravel storage
3. **Client-Side Processing** - All image manipulation in browser for instant feedback
4. **Compact Bundle** - Lazy-load tools to minimize initial JavaScript payload
5. **Quality over Speed** - Polished UX with proper loading states and validation
6. **Zero Telemetry** - No tracking, fully private

---

## Target Users

1. **Filament Developers** - Building admin panels that need image editing before upload
2. **Laravel Developers** - Building public-facing applications with image editing needs
3. **End Users** - Non-technical users who need to crop, filter, and annotate images

---

## Technical Stack

| Component | Technology | Rationale |
|-----------|------------|-----------|
| Canvas Library | Fabric.js 6.x | Mature, well-documented, ~90KB gzipped |
| Frontend Framework | Alpine.js 3.x | Already included with Filament/Livewire |
| Styling | Tailwind CSS 4.x | Filament-native theming |
| Build Tool | Vite 7.x | Matches project configuration |
| Backend | Laravel 12 / Livewire 3 | Project requirements |
| Admin Panel | Filament 4.x | Primary integration target |
| Filter Processing | Canvas 2D API | Most compatible, no WebGL dependency |

---

## User Flow

### Flow 1: New Image Upload (openOnSelect: true)
```
1. User clicks "Upload Image" or drags file onto dropzone
2. File is validated (type, size)
3. Editor modal opens automatically with image loaded
4. User edits image (crop, filter, annotate)
5. User clicks "Apply" → edited image uploaded to server
6. Thumbnail appears in form field with hover "Edit" button
7. Form is saved → image persisted via Spatie or Laravel Storage
```

### Flow 2: New Image Upload (openOnSelect: false)
```
1. User clicks "Upload Image" or drags file onto dropzone
2. File is validated and uploaded immediately (no editor)
3. Thumbnail appears with hover "Edit" button
4. User can optionally click "Edit" to open editor
5. User edits and clicks "Apply" → image re-uploaded
6. Form is saved → image persisted
```

### Flow 3: Edit Existing Image
```
1. Form loads with existing image from database
2. Thumbnail displays with hover "Edit" button
3. User clicks "Edit" → editor modal opens with current image
4. User makes changes and clicks "Apply"
5. Edited image replaces original (re-runs Spatie conversions if applicable)
6. Form is saved → changes persisted
```

### Flow 4: Multi-Image Sequential Editing
```
1. User uploads multiple images (multi-upload field)
2. Editor opens with first image
3. User edits and clicks "Next" → moves to next image
4. User can click "Previous" to go back
5. User clicks "Done" when finished with all images
6. All edited images uploaded
```

---

## Feature Specifications

### 1. Crop & Transform Tool

#### 1.1 Cropping
- **Free-form cropping** with draggable corner/edge handles
- **Aspect ratio presets** (configurable per-field):
  - Free (no constraint)
  - 1:1 (Square)
  - 4:3 (Standard)
  - 3:2 (Photo)
  - 16:9 (Widescreen)
  - 9:16 (Portrait/Mobile)
  - 2:3 (Portrait Photo)
  - 3:4 (Portrait Standard)
- **Custom aspect ratios** configurable per-field instance
- **Crop area visualization** with darkened mask outside selection
- **Minimum size enforcement** - prevents cropping below configured minimum
- **Live dimension display** showing current crop size in pixels

#### 1.2 Rotation
- **90-degree rotation buttons** (clockwise/counter-clockwise)
- **Free rotation slider** (-45° to +45°) with snap-to-zero at 0°
- **Rotation input field** for precise degree entry
- **Auto-expand canvas** to fit rotated image

#### 1.3 Flip
- **Horizontal flip** (mirror left-right)
- **Vertical flip** (mirror top-bottom)

#### 1.4 Configuration Options
```php
ImageEditor::make('image')
    ->cropAspectRatios([
        'free' => null,
        '1:1' => 1,
        '4:3' => 4/3,
        '16:9' => 16/9,
        'cinematic' => 2.35,
    ])
    ->defaultAspectRatio('free')
    ->cropMinSize(width: 100, height: 100)
    ->cropMaxSize(width: 4000, height: 4000)
    ->enableRotation(true)
    ->enableFlip(true)
```

---

### 2. Filters & Adjustments Tool

#### 2.1 Preset Filters
Each filter applies a predefined combination of Canvas 2D filter operations:

| Filter Name | Description |
|-------------|-------------|
| Original | No filter applied |
| Grayscale | Black and white conversion |
| Sepia | Warm brown vintage tone |
| Vintage | Faded, warm, low contrast |
| Warm | Orange/yellow color temperature shift |
| Cool | Blue color temperature shift |
| High Contrast | Increased contrast and saturation |
| Fade | Lifted blacks, reduced contrast |
| Dramatic | High contrast with slight desaturation |
| Vivid | Boosted saturation and contrast |

#### 2.2 Manual Adjustments
Slider controls with real-time preview:

| Adjustment | Range | Default | Description |
|------------|-------|---------|-------------|
| Brightness | -100 to +100 | 0 | Lightens or darkens image |
| Contrast | -100 to +100 | 0 | Difference between light and dark |
| Saturation | -100 to +100 | 0 | Color intensity (-100 = grayscale) |
| Exposure | -100 to +100 | 0 | Simulated exposure compensation |
| Warmth | -100 to +100 | 0 | Color temperature (cool to warm) |

#### 2.3 Filter Implementation
- Uses Canvas 2D `filter` property for basic adjustments
- Uses `ImageData` pixel manipulation for complex filters
- All processing happens on downsampled preview for performance
- Full-resolution processing applied on export

#### 2.4 Configuration Options
```php
ImageEditor::make('image')
    ->filterPresets([
        'original', 'grayscale', 'sepia', 'vintage',
        'warm', 'cool', 'dramatic', 'fade', 'vivid',
    ])
    ->adjustments([
        'brightness', 'contrast', 'saturation',
    ])
    ->disableFilters() // Only show adjustments
    ->disableAdjustments() // Only show filter presets
```

---

### 3. Annotation & Drawing Tool

#### 3.1 Drawing Tools

| Tool | Description | Options |
|------|-------------|---------|
| Select/Move | Select and manipulate existing shapes | - |
| Freehand/Brush | Draw freely with mouse/touch | Stroke width, color |
| Eraser | Erase parts of freehand drawings | Eraser size |
| Line | Straight line between two points | Stroke width, color |
| Arrow | Line with arrowhead | Stroke width, color |
| Rectangle | Rectangle/square shape | Stroke, fill, corner radius |
| Ellipse | Ellipse/circle shape | Stroke, fill |
| Text | Text annotation | Font, size, color, bold, italic |

#### 3.2 Shape Properties

**Stroke Options:**
- Width: 1px, 2px, 4px, 8px, 12px, 16px
- Color: Color picker with preset palette + hex input + eyedropper (where supported)
- Opacity: 0-100%

**Fill Options:**
- Color: Transparent, or color picker
- Opacity: 0-100%

**Text Options:**
- Font Family: Arial, Helvetica, Georgia, Times New Roman, Courier New, Verdana
- Font Size: 12px to 72px (slider + input)
- Font Weight: Normal, Bold
- Font Style: Normal, Italic
- Text Align: Left, Center, Right
- Color: Color picker

#### 3.3 Shape Manipulation
- **Selection** - Click to select any drawn shape
- **Multi-select** - Shift+click to select multiple (desktop)
- **Move** - Drag selected shapes
- **Resize** - Corner/edge handles with aspect ratio lock (Shift)
- **Rotate** - Rotation handle above selection
- **Delete** - Delete key or trash button removes selected shapes
- **Duplicate** - Ctrl+D duplicates selected shape
- **Layer Order** - Bring to front, send to back buttons

#### 3.4 Color Palette
Default preset colors (17 colors):
```
Transparent, White (#FFFFFF), Silver (#C0C0C0), Gray (#808080), Black (#000000),
Navy (#000080), Blue (#0000FF), Aqua (#00FFFF), Teal (#008080), Olive (#808000),
Green (#00FF00), Yellow (#FFFF00), Orange (#FFA500), Red (#FF0000), Maroon (#800000),
Fuchsia (#FF00FF), Purple (#800080)
```

#### 3.5 Eyedropper Tool
- Uses native `EyeDropper` API where supported (Chrome 95+, Edge 95+)
- Hidden on unsupported browsers (Firefox, Safari)
- Samples color from anywhere on screen

#### 3.6 Annotation Output
- All drawings are **flattened/baked into** the final image
- No separate JSON storage of shapes
- Simpler implementation, universal compatibility

#### 3.7 Configuration Options
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
    ->disableDrawing() // Remove drawing tool entirely
```

---

### 4. History & Undo/Redo

#### 4.1 History Stack
- Maintains a stack of canvas states
- Maximum history depth: 50 states (configurable)
- Each state stores serialized Fabric.js canvas JSON
- Memory-efficient: only stores deltas where possible

#### 4.2 Controls
- **Undo Button** - Reverts to previous state (disabled when at start)
- **Redo Button** - Re-applies undone state (disabled when at end)
- **Reset Button** - Returns to original image with confirmation dialog

#### 4.3 Keyboard Shortcuts (Minimal)
- `Ctrl/Cmd + Z` - Undo
- `Ctrl/Cmd + Y` or `Ctrl/Cmd + Shift + Z` - Redo
- `Escape` - Deselect current shape / Cancel current operation
- `Delete` / `Backspace` - Delete selected shape

#### 4.4 Configuration Options
```php
ImageEditor::make('image')
    ->historyLimit(50)
    ->enableKeyboardShortcuts(true)
```

---

### 5. User Interface

#### 5.1 Modal Structure
```
┌─────────────────────────────────────────────────────────────────┐
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │  [← Back]     Edit Image (1/3)           [Cancel] [Apply ✓] │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │  [Crop]  [Filter]  [Draw]        [Undo] [Redo] [Reset]     │ │
│ └─────────────────────────────────────────────────────────────┘ │
│ ┌───────────────────────────────────────────────┬─────────────┐ │
│ │                                               │             │ │
│ │                                               │   Tool      │ │
│ │                                               │   Options   │ │
│ │              Canvas Area                      │             │ │
│ │           (Image + Shapes)                    │  [Aspect]   │ │
│ │                                               │  [Rotate]   │ │
│ │                                               │  [Flip]     │ │
│ │                                               │             │ │
│ └───────────────────────────────────────────────┴─────────────┘ │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │                    [Zoom: Fit ▾]  [-] [+]                   │ │
│ └─────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

#### 5.2 Component Breakdown

**Header Bar:**
- Back button (for sequential multi-image editing)
- Title with image counter (e.g., "Edit Image (1/3)")
- Cancel button - closes without saving (with unsaved changes confirmation)
- Apply button - saves and closes (or moves to next image in sequential mode)

**Tool Tabs:**
- Crop / Filter / Draw tabs
- Active tab highlighted
- Tools lazy-loaded on first click

**History Controls:**
- Undo / Redo buttons with disabled states
- Reset button (with confirmation)

**Canvas Area:**
- Main Fabric.js canvas
- Checkered background for transparency
- Zoom and pan support

**Tool Options Panel (Right Sidebar):**
- Context-sensitive based on selected tool
- **Crop:** Aspect ratio buttons, rotation slider, flip buttons
- **Filter:** Filter preset grid, adjustment sliders
- **Draw:** Tool buttons, color picker, stroke/fill options

**Zoom Controls (Bottom):**
- Zoom dropdown (Fit, 50%, 100%, 200%)
- Zoom in/out buttons

#### 5.3 Sequential Multi-Image Mode
When editing multiple images:
- Header shows "Edit Image (1/3)" counter
- "Previous" and "Next" buttons appear
- "Apply" becomes "Next" until last image
- "Done" button on last image closes editor

#### 5.4 Loading States
- **Initial load:** Skeleton placeholder with spinner
- **Tool switching:** Subtle loading indicator if lazy-loading
- **Export:** Progress bar with percentage
- **Upload:** Progress bar integrated with Livewire upload

#### 5.5 Responsive Design
- **Desktop (>1024px):** Full layout with side panel (280px)
- **Tablet (768-1024px):** Narrower side panel (200px)
- **Mobile (<768px):** Basic functionality, tool panel as bottom sheet

Note: Desktop-focused design. Mobile gets basic functionality but touch gestures (pinch-zoom, two-finger rotate) are not prioritized.

#### 5.6 Theming
Inherits from Filament's theme system using CSS custom properties:

```css
/* Automatically inherits Filament's theme */
--image-editor-bg: rgb(var(--gray-100));
--image-editor-bg-dark: rgb(var(--gray-900));
--image-editor-surface: rgb(var(--gray-50));
--image-editor-surface-dark: rgb(var(--gray-800));
--image-editor-border: rgb(var(--gray-200));
--image-editor-border-dark: rgb(var(--gray-700));
--image-editor-text: rgb(var(--gray-900));
--image-editor-text-dark: rgb(var(--gray-100));
--image-editor-primary: rgb(var(--primary-500));
--image-editor-danger: rgb(var(--danger-500));
```

Full dark mode support matching Filament's dark mode.

#### 5.7 Modal Size (Configurable)
```php
ImageEditor::make('image')
    ->modalSize('6xl') // sm, md, lg, xl, 2xl, 3xl, 4xl, 5xl, 6xl, 7xl, full
```

Default: `6xl` (large modal with page context visible)

---

### 6. Image Processing

#### 6.1 Large Image Handling
For images larger than 2000px in either dimension:
- **Editing:** Work on downsampled preview (max 2000px)
- **Export:** Apply all transformations to original full-resolution image
- Provides smooth editing experience regardless of source image size

#### 6.2 Supported Input Formats
- JPEG (`image/jpeg`)
- PNG (`image/png`)
- WebP (`image/webp`)
- GIF (`image/gif`) - static only, first frame

#### 6.3 Output Formats
| Format | MIME Type | Use Case |
|--------|-----------|----------|
| JPEG | image/jpeg | Photos, smaller file size (default) |
| PNG | image/png | Graphics, transparency needed |
| WebP | image/webp | Modern browsers, best compression |

#### 6.4 Quality Settings
- JPEG/WebP quality: 0.1 to 1.0 (default: 0.92)
- PNG: Lossless (no quality setting)

#### 6.5 Output Sizing
- **Original size** - Export at source image dimensions (after crop)
- **Max dimensions** - Constrain to maximum width/height while preserving aspect ratio
- **Specific size** - Force exact output dimensions (may crop/pad)

#### 6.6 Configuration Options
```php
ImageEditor::make('image')
    ->outputFormat('webp') // jpeg, png, webp
    ->outputQuality(0.85)
    ->maxOutputSize(width: 2000, height: 2000)
```

---

### 7. Validation

#### 7.1 Input Validation
- File type validation (MIME type check)
- File size validation (configurable max size)
- Minimum dimensions warning (shows info message if below recommended)

#### 7.2 Edit-Time Validation
- **Prevent invalid actions:** Cannot crop smaller than configured minimum
- Crop handles snap to minimum size boundary
- Rotation/filter operations always allowed

#### 7.3 Configuration
```php
ImageEditor::make('image')
    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
    ->maxFileSize(10 * 1024) // 10MB in KB
    ->minDimensions(width: 200, height: 200) // Warning shown if smaller
    ->cropMinSize(width: 100, height: 100) // Hard minimum for crop
```

---

### 8. Cancel & Unsaved Changes

#### 8.1 Behavior
- Clicking Cancel or X with unsaved changes shows confirmation dialog
- "Discard changes?" with "Cancel" and "Discard" buttons
- If no changes made, closes immediately without confirmation

#### 8.2 What Counts as "Changes"
- Any crop adjustment
- Any rotation or flip
- Any filter or adjustment applied
- Any shape drawn

---

### 9. Filament Form Field Integration

#### 9.1 Basic Usage
```php
use Pjedesigns\FilamentImageEditor\ImageEditor;

public static function form(Form $form): Form
{
    return $form->schema([
        ImageEditor::make('avatar')
            ->label('Profile Picture')
            ->required(),
    ]);
}
```

#### 9.2 Full Configuration Example
```php
ImageEditor::make('cover_image')
    ->label('Cover Image')
    ->helperText('Recommended size: 1200x630px')

    // Storage (choose one)
    ->disk('public')
    ->directory('covers')
    ->visibility('public')
    // OR for Spatie Media Library:
    ->collection('cover_image')
    ->conversion('thumb') // Display this conversion in thumbnail

    // Editor behavior
    ->openOnSelect(true) // Open editor immediately when file selected
    ->modalSize('6xl')
    ->tools(['crop', 'filter', 'draw']) // Which tools to show

    // Crop options
    ->cropAspectRatios([
        'free' => null,
        '16:9' => 16/9,
        '1:1' => 1,
    ])
    ->defaultAspectRatio('16:9')
    ->cropMinSize(width: 600, height: 315)
    ->enableRotation(true)
    ->enableFlip(true)

    // Filter options
    ->filterPresets(['original', 'grayscale', 'sepia', 'warm', 'cool'])
    ->adjustments(['brightness', 'contrast', 'saturation'])

    // Drawing options
    ->drawingTools(['freehand', 'arrow', 'rectangle', 'text'])
    ->colorPalette(['#FFFFFF', '#000000', '#FF0000', '#00FF00', '#0000FF'])

    // Output options
    ->outputFormat('webp')
    ->outputQuality(0.9)
    ->maxOutputSize(width: 2000, height: 2000)

    // Validation
    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
    ->maxFileSize(5 * 1024) // 5MB
    ->minDimensions(width: 800, height: 400)
```

#### 9.3 Storage Backends

**Standard Laravel Storage:**
```php
ImageEditor::make('photo')
    ->disk('public')
    ->directory('photos')
    ->visibility('public')
```

**Spatie Media Library:**
```php
ImageEditor::make('photo')
    ->collection('photos')
    ->conversion('thumb')
    ->responsiveImages()
    ->customProperties(['source' => 'editor'])
```

When using Spatie:
- Automatically regenerates conversions after edit
- Supports all Spatie features (responsive images, custom properties, etc.)
- UUID-based state management matching existing Spatie implementation

#### 9.4 Events
```php
ImageEditor::make('image')
    ->afterStateUpdated(function ($state, $old, $component) {
        // Called when image is edited and applied
    })
```

---

### 10. Livewire Integration

#### 10.1 Standalone Component
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

#### 10.2 Trigger via Alpine Event
```blade
<button x-on:click="$dispatch('open-image-editor', {
    source: '{{ $imageUrl }}',
    config: { tools: ['crop'] }
})">
    Edit Image
</button>

<livewire:image-editor-modal />
```

#### 10.3 JavaScript API
```javascript
// Programmatic usage (for non-Livewire contexts)
const editor = window.FilamentImageEditor.open({
    source: '/path/to/image.jpg',
    tools: ['crop', 'filter', 'draw'],
    onApply: (file) => {
        // Handle the edited image File object
        console.log('Edited image:', file);
    },
    onCancel: () => {
        console.log('User cancelled');
    }
});
```

---

### 11. Configuration File

`config/filament-image-editor.php`:
```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Storage
    |--------------------------------------------------------------------------
    | Default storage configuration when not using Spatie Media Library.
    */
    'storage' => [
        'disk' => env('FILAMENT_IMAGE_EDITOR_DISK', 'public'),
        'directory' => env('FILAMENT_IMAGE_EDITOR_DIR', 'images'),
        'visibility' => 'public',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Output Settings
    |--------------------------------------------------------------------------
    */
    'output' => [
        'format' => 'jpeg', // jpeg, png, webp
        'quality' => 0.92,
        'max_width' => null,
        'max_height' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Tools
    |--------------------------------------------------------------------------
    */
    'tools' => ['crop', 'filter', 'draw'],

    /*
    |--------------------------------------------------------------------------
    | Crop Settings
    |--------------------------------------------------------------------------
    */
    'crop' => [
        'aspect_ratios' => [
            'free' => null,
            '1:1' => 1,
            '4:3' => 4/3,
            '3:2' => 3/2,
            '16:9' => 16/9,
            '9:16' => 9/16,
        ],
        'default_ratio' => 'free',
        'min_width' => 10,
        'min_height' => 10,
        'enable_rotation' => true,
        'enable_flip' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filter Settings
    |--------------------------------------------------------------------------
    */
    'filters' => [
        'presets' => [
            'original', 'grayscale', 'sepia', 'vintage',
            'warm', 'cool', 'dramatic', 'fade', 'vivid',
        ],
        'adjustments' => [
            'brightness', 'contrast', 'saturation',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Drawing Settings
    |--------------------------------------------------------------------------
    */
    'draw' => [
        'tools' => ['select', 'freehand', 'eraser', 'line', 'arrow', 'rectangle', 'ellipse', 'text'],
        'default_stroke_color' => '#000000',
        'default_stroke_width' => 4,
        'default_fill_color' => 'transparent',
        'color_palette' => [
            'transparent', '#FFFFFF', '#C0C0C0', '#808080', '#000000',
            '#000080', '#0000FF', '#00FFFF', '#008080', '#808000',
            '#00FF00', '#FFFF00', '#FFA500', '#FF0000', '#800000',
            '#FF00FF', '#800080',
        ],
        'fonts' => [
            'Arial', 'Helvetica', 'Georgia', 'Times New Roman',
            'Courier New', 'Verdana', 'Trebuchet MS',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | History Settings
    |--------------------------------------------------------------------------
    */
    'history' => [
        'limit' => 50,
        'keyboard_shortcuts' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Settings
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'modal_size' => '6xl',
        'open_on_select' => true,
        'show_zoom_controls' => true,
        'preview_max_size' => 2000, // Downsample images larger than this for editing
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Defaults
    |--------------------------------------------------------------------------
    */
    'validation' => [
        'accepted_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'max_file_size' => 10 * 1024, // 10MB in KB
    ],
];
```

---

## File Structure

```
packages/pjedesigns/filament-image-editor/
├── .gitignore
├── composer.json
├── package.json
├── vite.config.js
├── LICENSE.md
├── README.md
├── PRD.md
│
├── config/
│   └── filament-image-editor.php
│
├── resources/
│   ├── css/
│   │   └── image-editor.css
│   │
│   ├── js/
│   │   ├── index.js                    # Main entry, Alpine registration
│   │   ├── ImageEditor.js              # Core editor class
│   │   │
│   │   ├── tools/                      # Lazy-loaded tool modules
│   │   │   ├── CropTool.js
│   │   │   ├── FilterTool.js
│   │   │   └── DrawTool.js
│   │   │
│   │   ├── filters/
│   │   │   ├── presets.js              # Filter preset definitions
│   │   │   └── adjustments.js          # Brightness, contrast, etc.
│   │   │
│   │   ├── shapes/
│   │   │   ├── Arrow.js                # Custom Fabric.js arrow
│   │   │   └── index.js
│   │   │
│   │   └── utils/
│   │       ├── history.js              # Undo/redo manager
│   │       ├── export.js               # Canvas to Blob/File
│   │       ├── keyboard.js             # Shortcut handler
│   │       └── downscale.js            # Large image handling
│   │
│   ├── views/
│   │   ├── image-editor.blade.php      # Main editor modal
│   │   ├── components/
│   │   │   ├── modal.blade.php
│   │   │   ├── header.blade.php
│   │   │   ├── toolbar.blade.php
│   │   │   ├── canvas.blade.php
│   │   │   └── panels/
│   │   │       ├── crop.blade.php
│   │   │       ├── filter.blade.php
│   │   │       └── draw.blade.php
│   │   └── filament/
│   │       └── forms/
│   │           └── components/
│   │               └── image-editor.blade.php
│   │
│   └── lang/
│       └── en/
│           └── editor.php
│
├── src/
│   ├── FilamentImageEditorServiceProvider.php
│   │
│   ├── Forms/
│   │   └── Components/
│   │       └── ImageEditor.php         # Main Filament form field
│   │
│   ├── Livewire/
│   │   └── ImageEditorModal.php        # Standalone Livewire modal
│   │
│   ├── Enums/
│   │   ├── Tool.php
│   │   ├── AspectRatio.php
│   │   ├── FilterPreset.php
│   │   ├── DrawingTool.php
│   │   └── OutputFormat.php
│   │
│   ├── Support/
│   │   ├── EditorConfig.php            # Config builder/resolver
│   │   └── SpatieMediaHandler.php      # Spatie integration helper
│   │
│   └── Concerns/
│       ├── HasCropOptions.php
│       ├── HasFilterOptions.php
│       ├── HasDrawOptions.php
│       ├── HasOutputOptions.php
│       ├── HasStorageOptions.php
│       └── HasSpatieMediaLibrary.php
│
├── dist/                               # Built assets (git-ignored)
│   ├── filament-image-editor.js
│   ├── filament-image-editor.css
│   └── manifest.json
│
└── tests/
    ├── Pest.php
    ├── TestCase.php
    ├── Feature/
    │   ├── ImageEditorFieldTest.php
    │   ├── LivewireModalTest.php
    │   └── SpatieIntegrationTest.php
    └── Unit/
        ├── ConfigTest.php
        └── EnumTest.php
```

---

## Dependencies

### PHP (composer.json)
```json
{
    "name": "pjedesigns/filament-image-editor",
    "description": "A powerful image editor for Filament and Livewire applications",
    "type": "library",
    "license": "MIT",
    "authors": [
        {
            "name": "Paul Egan",
            "email": "paul@pjedesigns.com"
        }
    ],
    "require": {
        "php": "^8.2",
        "filament/filament": "^4.0",
        "livewire/livewire": "^3.0",
        "spatie/laravel-package-tools": "^1.16"
    },
    "require-dev": {
        "orchestra/testbench": "^10.0",
        "pestphp/pest": "^4.0",
        "pestphp/pest-plugin-livewire": "^3.0"
    },
    "suggest": {
        "spatie/laravel-medialibrary": "Required for Spatie Media Library integration"
    },
    "autoload": {
        "psr-4": {
            "Pjedesigns\\FilamentImageEditor\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Pjedesigns\\FilamentImageEditor\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Pjedesigns\\FilamentImageEditor\\FilamentImageEditorServiceProvider"
            ]
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

### JavaScript (package.json)
```json
{
    "name": "@pjedesigns/filament-image-editor",
    "version": "1.0.0",
    "private": true,
    "type": "module",
    "scripts": {
        "dev": "vite build --watch",
        "build": "vite build"
    },
    "dependencies": {
        "fabric": "^6.0.0"
    },
    "devDependencies": {
        "vite": "^7.0.0",
        "terser": "^5.0.0"
    }
}
```

---

## Implementation Phases

### Phase 1: Foundation & Crop Tool ✅ COMPLETE
- [x] Package scaffolding (composer.json, service provider, config)
- [x] Filament form field base class with storage handling
- [x] Basic modal UI structure (header, canvas area, tool panel)
- [x] Fabric.js canvas initialization
- [x] Crop tool with aspect ratios
- [x] Rotation (90° steps + slider)
- [x] Flip horizontal/vertical
- [x] Export to Blob/File
- [x] Livewire upload integration
- [x] Light/dark mode theming
- [x] Unsaved changes confirmation
- [x] Tests for crop functionality (config-level)

### Phase 2: Filters & Adjustments ✅ COMPLETE
- [x] Filter preset system with Canvas 2D
- [x] All 10 preset filters implemented
- [x] Brightness/contrast/saturation/exposure/warmth sliders
- [x] Real-time preview on downsampled image
- [x] Apply to full resolution on export
- [x] Lazy-loading of filter tool
- [x] Tests for filter functionality (config-level)

### Phase 3: Drawing & Annotation ✅ COMPLETE
- [x] Select/Move tool
- [x] Freehand drawing with brush
- [x] Eraser tool
- [x] Shape tools (rectangle, ellipse, line, arrow)
- [x] Text annotation tool
- [x] Color picker (preset palette + hex input)
- [x] Stroke/fill options panel
- [x] Shape manipulation (resize, rotate, delete)
- [x] Lazy-loading of draw tool
- [x] Tests for drawing functionality (config-level)

### Phase 4: Advanced Features & Polish ✅ COMPLETE
- [x] Undo/redo history
- [x] Keyboard shortcuts (Ctrl+Z, Ctrl+Y, Delete, Ctrl+D)
- [x] Zoom and pan controls (mouse wheel, Alt+drag, middle-click)
- [x] Sequential multi-image editing
- [x] Spatie Media Library integration
- [x] Conversion regeneration after edit
- [x] Loading progress indicators
- [x] Validation enforcement
- [x] Responsive adjustments
- [x] Comprehensive test coverage (70+ tests)
- [x] Documentation (README.md)

---

## Success Metrics

| Metric | Target |
|--------|--------|
| Initial JS bundle (core + crop) | < 100KB gzipped |
| Full bundle (all tools loaded) | < 200KB gzipped |
| Time to first edit | < 500ms after image load |
| Export time (2000px image) | < 2 seconds |
| Browser support | Chrome, Firefox, Safari, Edge (latest 2 versions) |
| Lighthouse accessibility | > 90 |
| Test coverage | > 80% |

---

## Non-Goals (Out of Scope)

1. **Video editing** - Images only
2. **AI-powered features** - No background removal, auto-enhance, etc.
3. **Preset shapes/stickers** - No speech bubbles, emojis, stamps
4. **Watermarking** - Not built-in (can be done server-side)
5. **Batch editing** - Sequential only, not apply-to-all
6. **State persistence** - No save-for-later / resume editing
7. **Full mobile optimization** - Desktop-focused, basic mobile support
8. **Offline mode** - Requires connection for upload

---

## Security Considerations

1. **No server-side image processing** - All edits client-side, reducing attack surface
2. **File type validation** - MIME type checking before editor opens
3. **File size limits** - Configurable max upload size
4. **No external requests** - Zero telemetry, no CDN dependencies
5. **CSP compatible** - No inline scripts, uses nonce where needed

---

## Accessibility

1. **Keyboard navigation** - All controls accessible via keyboard
2. **Focus management** - Proper focus trapping in modal
3. **Screen reader labels** - ARIA labels on all interactive elements
4. **Color contrast** - Meets WCAG AA standards
5. **Reduced motion** - Respects `prefers-reduced-motion`

---

## Package Structure & Distribution

### Spatie Package Skeleton
This package follows the [Spatie Laravel Package Skeleton](https://github.com/spatie/package-skeleton-laravel) conventions:
- Standard Laravel package directory structure
- Service provider using `spatie/laravel-package-tools`
- Proper config/view/asset publishing
- Pest testing setup
- GitHub Actions CI workflows

### Versioning Strategy
- **Semantic Versioning (SemVer):** MAJOR.MINOR.PATCH
- **Git Tags:** All releases tagged (e.g., `v1.0.0`, `v1.0.1`)
- **GitHub Releases:** Automated release notes from commits/PRs
- **Branch Strategy:**
  - `main` - stable releases only
  - `develop` - active development
  - Feature branches merged to `develop` via PR

### Packagist Distribution
- **Package Name:** `pjedesigns/filament-image-editor`
- **Repository:** GitHub (public or private with Packagist token)
- **Auto-updates:** GitHub webhook to Packagist for automatic version sync
- **Installation:** `composer require pjedesigns/filament-image-editor`

### GitHub Actions Workflows
```yaml
# .github/workflows/run-tests.yml - Run tests on PR/push
# .github/workflows/fix-php-code-style.yml - Auto-fix with Pint
# .github/workflows/phpstan.yml - Static analysis
# .github/workflows/release.yml - Create GitHub release on tag push
```

### Release Process
1. Update CHANGELOG.md with changes
2. Bump version in composer.json (if hardcoded)
3. Create PR from `develop` → `main`
4. Merge and create Git tag: `git tag v1.0.0 && git push --tags`
5. GitHub Action creates release, Packagist auto-updates

---

## Troubleshooting & Development Notes

### Local Package References
When troubleshooting JS/CSS implementation or asset injection issues, refer to the other packages in `packages/pjedesigns/` as working examples of Filament integration best practices:

- **`packages/pjedesigns/filament-meta-lexical-editor/`** - In-house Filament rich text editor plugin with working JS/CSS asset registration

These packages demonstrate:
- Correct Vite configuration for Filament packages
- Service provider asset registration patterns
- Filament plugin asset injection methods
- Build output structure and manifest handling

### Development Requirements
When adding new features or making changes to the package:

1. **Tests** - All new features and changes must have corresponding Pest tests. Update existing tests if behaviour changes.
2. **README** - Update the README.md file to reflect any new features, configuration options, or usage changes.

Keep tests and documentation in sync with the codebase at all times.

---

## References

- [Spatie Laravel Package Skeleton](https://github.com/spatie/package-skeleton-laravel)
- [Fabric.js Documentation](http://fabricjs.com/docs/)
- [Filament Form Fields](https://filamentphp.com/docs/forms/fields/getting-started)
- [Filament Spatie Media Library Plugin](https://filamentphp.com/plugins/filament-spatie-media-library)
- [Livewire Documentation](https://livewire.laravel.com/docs)
- [Canvas 2D Filters](https://developer.mozilla.org/en-US/docs/Web/API/CanvasRenderingContext2D/filter)
- [EyeDropper API](https://developer.mozilla.org/en-US/docs/Web/API/EyeDropper_API)
- [Packagist - Publishing Packages](https://packagist.org/about)

---

## Changelog

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | January 2026 | Initial stable release with all features (Phases 1-4 complete) |

---

*Document Version: 3.0*
*Last Updated: January 2026*
