<?php

declare(strict_types=1);

namespace Pjedesigns\FilamentImageEditor\Concerns;

use Closure;

trait HasFilterOptions
{
    protected array|Closure $filterPresets = [];

    protected array|Closure $adjustments = [];

    protected bool|Closure $filtersDisabled = false;

    protected bool|Closure $adjustmentsDisabled = false;

    /**
     * Set the available filter presets.
     *
     * @param  array<string>  $presets
     */
    public function filterPresets(array|Closure $presets): static
    {
        $this->filterPresets = $presets;

        return $this;
    }

    /**
     * Get the filter presets.
     *
     * @return array<string>
     */
    public function getFilterPresets(): array
    {
        $presets = $this->evaluate($this->filterPresets);

        if (empty($presets)) {
            return config('filament-image-editor.filters.presets', [
                'original',
                'grayscale',
                'sepia',
                'vintage',
                'warm',
                'cool',
                'dramatic',
                'fade',
                'vivid',
            ]);
        }

        return $presets;
    }

    /**
     * Set the available adjustments.
     *
     * @param  array<string>  $adjustments
     */
    public function adjustments(array|Closure $adjustments): static
    {
        $this->adjustments = $adjustments;

        return $this;
    }

    /**
     * Get the adjustments.
     *
     * @return array<string>
     */
    public function getAdjustments(): array
    {
        $adjustments = $this->evaluate($this->adjustments);

        if (empty($adjustments)) {
            return config('filament-image-editor.filters.adjustments', [
                'brightness',
                'contrast',
                'saturation',
            ]);
        }

        return $adjustments;
    }

    /**
     * Disable filter presets (show only adjustments).
     */
    public function disableFilters(bool|Closure $disable = true): static
    {
        $this->filtersDisabled = $disable;

        return $this;
    }

    /**
     * Check if filters are disabled.
     */
    public function areFiltersDisabled(): bool
    {
        return $this->evaluate($this->filtersDisabled);
    }

    /**
     * Disable adjustments (show only filter presets).
     */
    public function disableAdjustments(bool|Closure $disable = true): static
    {
        $this->adjustmentsDisabled = $disable;

        return $this;
    }

    /**
     * Check if adjustments are disabled.
     */
    public function areAdjustmentsDisabled(): bool
    {
        return $this->evaluate($this->adjustmentsDisabled);
    }
}
