<?php

namespace Pjedesigns\FilamentImageEditor\Concerns;

use Closure;

trait HasSpatieMediaLibrary
{
    protected string|Closure|null $collection = null;

    protected string|Closure|null $conversion = null;

    protected bool|Closure $responsiveImages = false;

    protected array|Closure $customProperties = [];

    protected bool $usingSpatieMediaLibrary = false;

    /**
     * Set the Spatie Media Library collection name.
     */
    public function collection(string|Closure $collection): static
    {
        $this->collection = $collection;
        $this->usingSpatieMediaLibrary = true;

        return $this;
    }

    /**
     * Get the collection name.
     */
    public function getCollection(): ?string
    {
        return $this->evaluate($this->collection);
    }

    /**
     * Set the conversion to display in the thumbnail.
     */
    public function conversion(string|Closure $conversion): static
    {
        $this->conversion = $conversion;

        return $this;
    }

    /**
     * Get the conversion name.
     */
    public function getConversion(): ?string
    {
        return $this->evaluate($this->conversion);
    }

    /**
     * Enable responsive images.
     */
    public function responsiveImages(bool|Closure $enable = true): static
    {
        $this->responsiveImages = $enable;

        return $this;
    }

    /**
     * Check if responsive images are enabled.
     */
    public function hasResponsiveImages(): bool
    {
        return $this->evaluate($this->responsiveImages);
    }

    /**
     * Set custom properties for the media item.
     */
    public function customProperties(array|Closure $properties): static
    {
        $this->customProperties = $properties;

        return $this;
    }

    /**
     * Get the custom properties.
     */
    public function getCustomProperties(): array
    {
        return $this->evaluate($this->customProperties);
    }

    /**
     * Check if using Spatie Media Library.
     */
    public function isUsingSpatieMediaLibrary(): bool
    {
        return $this->usingSpatieMediaLibrary;
    }
}
