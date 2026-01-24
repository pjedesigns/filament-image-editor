<?php

namespace Pjedesigns\FilamentImageEditor\Concerns;

use Closure;

trait HasStorageOptions
{
    protected string|Closure|null $disk = null;

    protected string|Closure|null $directory = null;

    protected string|Closure|null $visibility = null;

    protected bool|Closure $preserveFilenames = false;

    protected ?string $originalFilename = null;

    /**
     * Set the storage disk.
     */
    public function disk(string|Closure $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Get the storage disk.
     */
    public function getDisk(): string
    {
        return $this->evaluate($this->disk)
            ?? config('filament-image-editor.storage.disk', 'public');
    }

    /**
     * Set the storage directory.
     */
    public function directory(string|Closure $directory): static
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * Get the storage directory.
     */
    public function getDirectory(): string
    {
        return $this->evaluate($this->directory)
            ?? config('filament-image-editor.storage.directory', 'images');
    }

    /**
     * Set the file visibility.
     */
    public function visibility(string|Closure $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Get the file visibility.
     */
    public function getVisibility(): string
    {
        return $this->evaluate($this->visibility)
            ?? config('filament-image-editor.storage.visibility', 'public');
    }

    /**
     * Generate the storage path for a file.
     */
    public function getStoragePath(string $filename): string
    {
        $directory = trim($this->getDirectory(), '/');

        return $directory ? "{$directory}/{$filename}" : $filename;
    }

    /**
     * Set whether to preserve the original filename when saving.
     */
    public function shouldPreserveFilenames(bool|Closure $preserve = true): static
    {
        $this->preserveFilenames = $preserve;

        return $this;
    }

    /**
     * Check if filenames should be preserved.
     */
    public function getPreserveFilenames(): bool
    {
        return $this->evaluate($this->preserveFilenames);
    }

    /**
     * Set the original filename (called when a file is selected).
     */
    public function setOriginalFilename(?string $filename): static
    {
        $this->originalFilename = $filename;

        return $this;
    }

    /**
     * Get the original filename.
     */
    public function getOriginalFilename(): ?string
    {
        return $this->originalFilename;
    }
}
