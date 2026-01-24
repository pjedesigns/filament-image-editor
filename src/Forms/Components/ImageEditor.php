<?php

namespace Pjedesigns\FilamentImageEditor\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Pjedesigns\FilamentImageEditor\Concerns\HasCropOptions;
use Pjedesigns\FilamentImageEditor\Concerns\HasDrawOptions;
use Pjedesigns\FilamentImageEditor\Concerns\HasFilterOptions;
use Pjedesigns\FilamentImageEditor\Concerns\HasOutputOptions;
use Pjedesigns\FilamentImageEditor\Concerns\HasSpatieMediaLibrary;
use Pjedesigns\FilamentImageEditor\Concerns\HasStorageOptions;
use Pjedesigns\FilamentImageEditor\Enums\Tool;
use Spatie\MediaLibrary\HasMedia;

class ImageEditor extends Field
{
    use HasCropOptions;
    use HasDrawOptions;
    use HasFilterOptions;
    use HasOutputOptions;
    use HasSpatieMediaLibrary;
    use HasStorageOptions;

    protected string $view = 'filament-image-editor::forms.components.image-editor';

    protected array|Closure $tools = [];

    protected bool|Closure $openOnSelect = true;

    protected string|Closure $modalSize = '6xl';

    protected array|Closure $acceptedFileTypes = [];

    protected int|Closure|null $maxFileSize = null;

    protected int|Closure|null $minWidth = null;

    protected int|Closure|null $minHeight = null;

    protected int|Closure $historyLimit = 50;

    protected bool|Closure $keyboardShortcuts = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->columnSpanFull();

        // Load state from relationships (for Spatie Media Library)
        $this->loadStateFromRelationshipsUsing(static function (ImageEditor $component, ?Model $record): void {
            if (! $component->isUsingSpatieMediaLibrary()) {
                return;
            }

            if (! $record instanceof HasMedia) {
                return;
            }

            $collection = $component->getCollection();
            $conversion = $component->getConversion();

            $media = $record->getFirstMedia($collection);

            if ($media) {
                $url = $conversion ? $media->getUrl($conversion) : $media->getUrl();
                $component->state($url);
            }
        });

        // For non-Spatie usage, hydrate from existing state
        $this->afterStateHydrated(function (ImageEditor $component, $state, ?Model $record) {
            // For standard storage, state is already a path/URL
            if (is_string($state) && $state !== '') {
                return;
            }
        });

        // Dehydrate state - process before saving
        $this->dehydrateStateUsing(function ($state, ?Model $record) {
            // If using Spatie Media Library, state is handled in saveRelationships
            if ($this->isUsingSpatieMediaLibrary()) {
                return $state;
            }

            // Check if state is base64 data - save to storage
            if ($this->isBase64Image($state)) {
                return $this->saveBase64ToStorage($state);
            }

            // Return existing path/URL as-is
            return $state;
        });

        // For Spatie Media Library, prevent dehydration (no database column)
        $this->dehydrated(function () {
            return ! $this->isUsingSpatieMediaLibrary();
        });

        // Save relationships (for Spatie Media Library)
        $this->saveRelationshipsUsing(function (ImageEditor $component, ?Model $record, $state) {
            if (! $this->isUsingSpatieMediaLibrary()) {
                return;
            }

            if (! $record instanceof HasMedia) {
                return;
            }

            $collection = $this->getCollection();

            // Handle removal
            if (empty($state)) {
                $record->clearMediaCollection($collection);

                return;
            }

            // Check if it's new base64 data (uploaded via exposed method)
            if ($this->isBase64Image($state)) {
                // Clear existing media first (single file collection)
                $record->clearMediaCollection($collection);

                // Decode and save
                $imageData = $this->decodeBase64($state);

                if ($imageData) {
                    // Try to get the original filename from Livewire state
                    $originalFilename = $this->getOriginalFilenameFromState();
                    $filename = $this->generateFilename($originalFilename);

                    // Build the media adder with all options before saving
                    $mediaAdder = $record->addMediaFromString($imageData)
                        ->usingFileName($filename);

                    // Add custom properties
                    $customProperties = $this->getCustomProperties();
                    if (! empty($customProperties)) {
                        $mediaAdder->withCustomProperties($customProperties);
                    }

                    // Handle responsive images
                    if ($this->hasResponsiveImages()) {
                        $mediaAdder->withResponsiveImages();
                    }

                    // Finally save to the collection
                    $mediaAdder->toMediaCollection($collection);
                }
            }
            // If state is a path reference (from uploadImage method), it's already saved
        });
    }


    /**
     * Check if a string is base64 encoded image data.
     */
    protected function isBase64Image(?string $data): bool
    {
        if (empty($data)) {
            return false;
        }

        // Check for data URI prefix
        if (str_starts_with($data, 'data:image/')) {
            return true;
        }

        // Check if it's raw base64 (at least 100 chars and valid base64)
        if (strlen($data) > 100) {
            $decoded = base64_decode($data, true);

            return $decoded !== false && strlen($decoded) > 0;
        }

        return false;
    }

    /**
     * Decode base64 image data.
     */
    protected function decodeBase64(string $data): ?string
    {
        // Remove data URI prefix if present
        $data = preg_replace('#^data:image/\w+;base64,#i', '', $data);

        $decoded = base64_decode($data, true);

        return $decoded !== false ? $decoded : null;
    }

    /**
     * Save base64 image data to storage and return the path.
     */
    protected function saveBase64ToStorage(string $data): ?string
    {
        $imageData = $this->decodeBase64($data);

        if (! $imageData) {
            return null;
        }

        // Try to get the original filename from Livewire state
        $originalFilename = $this->getOriginalFilenameFromState();

        $filename = $this->generateFilename($originalFilename);
        $directory = $this->getDirectory();
        $path = trim($directory, '/').'/'.$filename;

        $disk = $this->getDisk();
        $visibility = $this->getVisibility();

        Storage::disk($disk)->put($path, $imageData, $visibility);

        return $path;
    }

    /**
     * Get the original filename from Livewire state.
     */
    protected function getOriginalFilenameFromState(): ?string
    {
        if (! $this->getPreserveFilenames()) {
            return null;
        }

        $livewire = $this->getLivewire();
        $statePath = $this->getStatePath().'_original_filename';

        return data_get($livewire, $statePath);
    }

    /**
     * Generate a unique filename for the image.
     */
    protected function generateFilename(?string $originalFilename = null): string
    {
        $format = $this->getOutputFormat();
        $ext = $format === 'jpeg' ? 'jpg' : $format;

        if ($this->getPreserveFilenames() && $originalFilename) {
            $baseName = pathinfo($originalFilename, PATHINFO_FILENAME);
            $baseName = Str::slug($baseName);

            return $baseName.'.'.$ext;
        }

        return Str::uuid().'.'.$ext;
    }

    /**
     * Set the available tools.
     *
     * @param  array<Tool|string>  $tools
     */
    public function tools(array|Closure $tools): static
    {
        $this->tools = $tools;

        return $this;
    }

    /**
     * Get the available tools.
     *
     * @return array<string>
     */
    public function getTools(): array
    {
        $tools = $this->evaluate($this->tools);

        if (empty($tools)) {
            return config('filament-image-editor.tools', ['crop', 'filter', 'draw']);
        }

        return array_map(function ($tool) {
            return $tool instanceof Tool ? $tool->value : $tool;
        }, $tools);
    }

    /**
     * Whether to open the editor immediately when a file is selected.
     */
    public function openOnSelect(bool|Closure $open = true): static
    {
        $this->openOnSelect = $open;

        return $this;
    }

    /**
     * Check if editor should open on select.
     */
    public function shouldOpenOnSelect(): bool
    {
        return $this->evaluate($this->openOnSelect)
            ?? config('filament-image-editor.ui.open_on_select', true);
    }

    /**
     * Set the modal size.
     */
    public function modalSize(string|Closure $size): static
    {
        $this->modalSize = $size;

        return $this;
    }

    /**
     * Get the modal size.
     */
    public function getModalSize(): string
    {
        return $this->evaluate($this->modalSize)
            ?? config('filament-image-editor.ui.modal_size', '6xl');
    }

    /**
     * Set the accepted file types.
     *
     * @param  array<string>  $types  MIME types
     */
    public function acceptedFileTypes(array|Closure $types): static
    {
        $this->acceptedFileTypes = $types;

        return $this;
    }

    /**
     * Get the accepted file types.
     *
     * @return array<string>
     */
    public function getAcceptedFileTypes(): array
    {
        $types = $this->evaluate($this->acceptedFileTypes);

        if (empty($types)) {
            return config('filament-image-editor.validation.accepted_types', [
                'image/jpeg', 'image/png', 'image/webp', 'image/gif',
            ]);
        }

        return $types;
    }

    /**
     * Get accepted file types as string for HTML input.
     */
    public function getAcceptedFileTypesString(): string
    {
        return implode(',', $this->getAcceptedFileTypes());
    }

    /**
     * Set the maximum file size in KB.
     */
    public function maxFileSize(int|Closure $size): static
    {
        $this->maxFileSize = $size;

        return $this;
    }

    /**
     * Get the maximum file size in KB.
     */
    public function getMaxFileSize(): int
    {
        return $this->evaluate($this->maxFileSize)
            ?? config('filament-image-editor.validation.max_file_size', 10 * 1024);
    }

    /**
     * Get the maximum file size in bytes.
     */
    public function getMaxFileSizeBytes(): int
    {
        return $this->getMaxFileSize() * 1024;
    }

    /**
     * Set the minimum dimensions (warning threshold).
     */
    public function minDimensions(int|Closure|null $width = null, int|Closure|null $height = null): static
    {
        $this->minWidth = $width;
        $this->minHeight = $height;

        return $this;
    }

    /**
     * Get the minimum width.
     */
    public function getMinWidth(): ?int
    {
        return $this->evaluate($this->minWidth);
    }

    /**
     * Get the minimum height.
     */
    public function getMinHeight(): ?int
    {
        return $this->evaluate($this->minHeight);
    }

    /**
     * Set the history limit.
     */
    public function historyLimit(int|Closure $limit): static
    {
        $this->historyLimit = $limit;

        return $this;
    }

    /**
     * Get the history limit.
     */
    public function getHistoryLimit(): int
    {
        return $this->evaluate($this->historyLimit)
            ?? config('filament-image-editor.history.limit', 50);
    }

    /**
     * Enable or disable keyboard shortcuts.
     */
    public function enableKeyboardShortcuts(bool|Closure $enable = true): static
    {
        $this->keyboardShortcuts = $enable;

        return $this;
    }

    /**
     * Check if keyboard shortcuts are enabled.
     */
    public function hasKeyboardShortcuts(): bool
    {
        return $this->evaluate($this->keyboardShortcuts)
            ?? config('filament-image-editor.history.keyboard_shortcuts', true);
    }

    /**
     * Get the preview max size for downsampling large images.
     */
    public function getPreviewMaxSize(): int
    {
        return config('filament-image-editor.ui.preview_max_size', 2000);
    }

    /**
     * Get the current image URL for display.
     */
    public function getImageUrl(): ?string
    {
        // For Spatie Media Library, get URL from the model's media collection
        if ($this->isUsingSpatieMediaLibrary()) {
            $record = $this->getRecord();

            if ($record instanceof HasMedia) {
                $collection = $this->getCollection();
                $conversion = $this->getConversion();

                $media = $record->getFirstMedia($collection);

                if ($media) {
                    return $conversion ? $media->getUrl($conversion) : $media->getUrl();
                }
            }

            return null;
        }

        $state = $this->getState();

        if (empty($state)) {
            return null;
        }

        // If it's base64 data, it's a new upload not yet saved
        if ($this->isBase64Image($state)) {
            return $state;
        }

        // If it's already a full URL, return it
        if (filter_var($state, FILTER_VALIDATE_URL)) {
            return $state;
        }

        // Otherwise, generate URL from storage
        $disk = Storage::disk($this->getDisk());

        if ($disk->exists($state)) {
            return $disk->url($state);
        }

        return null;
    }

    /**
     * Get the original (full resolution) image URL for editing.
     * This always returns the original image, not a conversion.
     */
    public function getOriginalImageUrl(): ?string
    {
        // For Spatie Media Library, always get the original image URL (not conversion)
        if ($this->isUsingSpatieMediaLibrary()) {
            $record = $this->getRecord();

            if ($record instanceof HasMedia) {
                $collection = $this->getCollection();

                $media = $record->getFirstMedia($collection);

                if ($media) {
                    // Always return the original URL, never a conversion
                    return $media->getUrl();
                }
            }

            return null;
        }

        // For non-Spatie storage, use the same logic as getImageUrl
        return $this->getImageUrl();
    }

    /**
     * Get the complete configuration for JavaScript.
     */
    public function getEditorConfig(): array
    {
        return [
            'tools' => $this->getTools(),
            'openOnSelect' => $this->shouldOpenOnSelect(),
            'modalSize' => $this->getModalSize(),
            'crop' => [
                'aspectRatios' => $this->getCropAspectRatios(),
                'defaultAspectRatio' => $this->getDefaultAspectRatio(),
                'minWidth' => $this->getCropMinWidth(),
                'minHeight' => $this->getCropMinHeight(),
                'maxWidth' => $this->getCropMaxWidth(),
                'maxHeight' => $this->getCropMaxHeight(),
                'enableRotation' => $this->isRotationEnabled(),
                'enableFlip' => $this->isFlipEnabled(),
            ],
            'filters' => [
                'presets' => $this->getFilterPresets(),
                'adjustments' => $this->getAdjustments(),
                'disabled' => $this->areFiltersDisabled(),
                'adjustmentsDisabled' => $this->areAdjustmentsDisabled(),
            ],
            'draw' => [
                'tools' => $this->getDrawingTools(),
                'defaultStrokeColor' => $this->getDefaultStrokeColor(),
                'defaultStrokeWidth' => $this->getDefaultStrokeWidth(),
                'defaultFillColor' => $this->getDefaultFillColor(),
                'colorPalette' => $this->getColorPalette(),
                'fonts' => $this->getFonts(),
                'disabled' => $this->isDrawingDisabled(),
            ],
            'output' => [
                'format' => $this->getOutputFormat(),
                'quality' => $this->getOutputQuality(),
                'maxWidth' => $this->getMaxOutputWidth(),
                'maxHeight' => $this->getMaxOutputHeight(),
            ],
            'validation' => [
                'acceptedTypes' => $this->getAcceptedFileTypes(),
                'maxFileSize' => $this->getMaxFileSizeBytes(),
                'minWidth' => $this->getMinWidth(),
                'minHeight' => $this->getMinHeight(),
            ],
            'history' => [
                'limit' => $this->getHistoryLimit(),
                'keyboardShortcuts' => $this->hasKeyboardShortcuts(),
            ],
            'previewMaxSize' => $this->getPreviewMaxSize(),
            'preserveFilenames' => $this->getPreserveFilenames(),
        ];
    }
}
