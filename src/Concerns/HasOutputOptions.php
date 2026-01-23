<?php

namespace Pjedesigns\FilamentImageEditor\Concerns;

use Closure;
use Pjedesigns\FilamentImageEditor\Enums\OutputFormat;

trait HasOutputOptions
{
    protected OutputFormat|string|Closure|null $outputFormat = null;

    protected float|Closure|null $outputQuality = null;

    protected int|Closure|null $maxOutputWidth = null;

    protected int|Closure|null $maxOutputHeight = null;

    /**
     * Set the output format.
     */
    public function outputFormat(OutputFormat|string|Closure $format): static
    {
        $this->outputFormat = $format;

        return $this;
    }

    /**
     * Get the output format.
     */
    public function getOutputFormat(): string
    {
        $format = $this->evaluate($this->outputFormat);

        if ($format instanceof OutputFormat) {
            return $format->value;
        }

        return $format ?? config('filament-image-editor.output.format', 'jpeg');
    }

    /**
     * Get the output MIME type.
     */
    public function getOutputMimeType(): string
    {
        $format = $this->getOutputFormat();

        return match ($format) {
            'jpeg', 'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    /**
     * Set the output quality (0.0 to 1.0).
     */
    public function outputQuality(float|Closure $quality): static
    {
        $this->outputQuality = $quality;

        return $this;
    }

    /**
     * Get the output quality.
     */
    public function getOutputQuality(): float
    {
        return $this->evaluate($this->outputQuality)
            ?? config('filament-image-editor.output.quality', 0.92);
    }

    /**
     * Set the maximum output dimensions.
     */
    public function maxOutputSize(int|Closure|null $width = null, int|Closure|null $height = null): static
    {
        $this->maxOutputWidth = $width;
        $this->maxOutputHeight = $height;

        return $this;
    }

    /**
     * Get the maximum output width.
     */
    public function getMaxOutputWidth(): ?int
    {
        return $this->evaluate($this->maxOutputWidth)
            ?? config('filament-image-editor.output.max_width');
    }

    /**
     * Get the maximum output height.
     */
    public function getMaxOutputHeight(): ?int
    {
        return $this->evaluate($this->maxOutputHeight)
            ?? config('filament-image-editor.output.max_height');
    }
}
