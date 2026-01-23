<?php

namespace Pjedesigns\FilamentImageEditor\Concerns;

use Closure;
use Pjedesigns\FilamentImageEditor\Enums\AspectRatio;

trait HasCropOptions
{
    protected array|Closure $cropAspectRatios = [];

    protected string|Closure|null $defaultAspectRatio = null;

    protected int|Closure|null $cropMinWidth = null;

    protected int|Closure|null $cropMinHeight = null;

    protected int|Closure|null $cropMaxWidth = null;

    protected int|Closure|null $cropMaxHeight = null;

    protected bool|Closure $enableRotation = true;

    protected bool|Closure $enableFlip = true;

    /**
     * Set the available crop aspect ratios.
     *
     * @param  array<string, float|null>  $ratios  Key-value pairs of label => ratio
     */
    public function cropAspectRatios(array|Closure $ratios): static
    {
        $this->cropAspectRatios = $ratios;

        return $this;
    }

    /**
     * Get the crop aspect ratios.
     *
     * @return array<string, float|null>
     */
    public function getCropAspectRatios(): array
    {
        $ratios = $this->evaluate($this->cropAspectRatios);

        if (empty($ratios)) {
            return config('filament-image-editor.crop.aspect_ratios', [
                'free' => null,
                '1:1' => 1,
                '4:3' => 4 / 3,
                '16:9' => 16 / 9,
            ]);
        }

        return $ratios;
    }

    /**
     * Set the default aspect ratio.
     */
    public function defaultAspectRatio(string|Closure $ratio): static
    {
        $this->defaultAspectRatio = $ratio;

        return $this;
    }

    /**
     * Get the default aspect ratio.
     */
    public function getDefaultAspectRatio(): string
    {
        return $this->evaluate($this->defaultAspectRatio)
            ?? config('filament-image-editor.crop.default_ratio', 'free');
    }

    /**
     * Set the minimum crop size.
     */
    public function cropMinSize(int|Closure|null $width = null, int|Closure|null $height = null): static
    {
        $this->cropMinWidth = $width;
        $this->cropMinHeight = $height;

        return $this;
    }

    /**
     * Get the minimum crop width.
     */
    public function getCropMinWidth(): int
    {
        return $this->evaluate($this->cropMinWidth)
            ?? config('filament-image-editor.crop.min_width', 10);
    }

    /**
     * Get the minimum crop height.
     */
    public function getCropMinHeight(): int
    {
        return $this->evaluate($this->cropMinHeight)
            ?? config('filament-image-editor.crop.min_height', 10);
    }

    /**
     * Set the maximum crop size.
     */
    public function cropMaxSize(int|Closure|null $width = null, int|Closure|null $height = null): static
    {
        $this->cropMaxWidth = $width;
        $this->cropMaxHeight = $height;

        return $this;
    }

    /**
     * Get the maximum crop width.
     */
    public function getCropMaxWidth(): ?int
    {
        return $this->evaluate($this->cropMaxWidth);
    }

    /**
     * Get the maximum crop height.
     */
    public function getCropMaxHeight(): ?int
    {
        return $this->evaluate($this->cropMaxHeight);
    }

    /**
     * Enable or disable rotation.
     */
    public function enableRotation(bool|Closure $enable = true): static
    {
        $this->enableRotation = $enable;

        return $this;
    }

    /**
     * Check if rotation is enabled.
     */
    public function isRotationEnabled(): bool
    {
        return $this->evaluate($this->enableRotation)
            ?? config('filament-image-editor.crop.enable_rotation', true);
    }

    /**
     * Enable or disable flip.
     */
    public function enableFlip(bool|Closure $enable = true): static
    {
        $this->enableFlip = $enable;

        return $this;
    }

    /**
     * Check if flip is enabled.
     */
    public function isFlipEnabled(): bool
    {
        return $this->evaluate($this->enableFlip)
            ?? config('filament-image-editor.crop.enable_flip', true);
    }
}
