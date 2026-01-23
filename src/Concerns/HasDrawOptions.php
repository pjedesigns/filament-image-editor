<?php

namespace Pjedesigns\FilamentImageEditor\Concerns;

use Closure;

trait HasDrawOptions
{
    protected array|Closure $drawingTools = [];

    protected string|Closure|null $defaultStrokeColor = null;

    protected int|Closure|null $defaultStrokeWidth = null;

    protected string|Closure|null $defaultFillColor = null;

    protected array|Closure $colorPalette = [];

    protected array|Closure $fonts = [];

    protected bool|Closure $drawingDisabled = false;

    /**
     * Set the available drawing tools.
     *
     * @param  array<string>  $tools
     */
    public function drawingTools(array|Closure $tools): static
    {
        $this->drawingTools = $tools;

        return $this;
    }

    /**
     * Get the drawing tools.
     *
     * @return array<string>
     */
    public function getDrawingTools(): array
    {
        $tools = $this->evaluate($this->drawingTools);

        if (empty($tools)) {
            return config('filament-image-editor.draw.tools', [
                'select',
                'freehand',
                'eraser',
                'line',
                'arrow',
                'rectangle',
                'ellipse',
                'text',
            ]);
        }

        return $tools;
    }

    /**
     * Set the default stroke color.
     */
    public function defaultStrokeColor(string|Closure $color): static
    {
        $this->defaultStrokeColor = $color;

        return $this;
    }

    /**
     * Get the default stroke color.
     */
    public function getDefaultStrokeColor(): string
    {
        return $this->evaluate($this->defaultStrokeColor)
            ?? config('filament-image-editor.draw.default_stroke_color', '#000000');
    }

    /**
     * Set the default stroke width.
     */
    public function defaultStrokeWidth(int|Closure $width): static
    {
        $this->defaultStrokeWidth = $width;

        return $this;
    }

    /**
     * Get the default stroke width.
     */
    public function getDefaultStrokeWidth(): int
    {
        return $this->evaluate($this->defaultStrokeWidth)
            ?? config('filament-image-editor.draw.default_stroke_width', 4);
    }

    /**
     * Set the default fill color.
     */
    public function defaultFillColor(string|Closure $color): static
    {
        $this->defaultFillColor = $color;

        return $this;
    }

    /**
     * Get the default fill color.
     */
    public function getDefaultFillColor(): string
    {
        return $this->evaluate($this->defaultFillColor)
            ?? config('filament-image-editor.draw.default_fill_color', 'transparent');
    }

    /**
     * Set the color palette.
     *
     * @param  array<string>  $colors
     */
    public function colorPalette(array|Closure $colors): static
    {
        $this->colorPalette = $colors;

        return $this;
    }

    /**
     * Get the color palette.
     *
     * @return array<string>
     */
    public function getColorPalette(): array
    {
        $palette = $this->evaluate($this->colorPalette);

        if (empty($palette)) {
            return config('filament-image-editor.draw.color_palette', [
                'transparent', '#FFFFFF', '#C0C0C0', '#808080', '#000000',
                '#000080', '#0000FF', '#00FFFF', '#008080', '#808000',
                '#00FF00', '#FFFF00', '#FFA500', '#FF0000', '#800000',
                '#FF00FF', '#800080',
            ]);
        }

        return $palette;
    }

    /**
     * Set the available fonts.
     *
     * @param  array<string>  $fonts
     */
    public function fonts(array|Closure $fonts): static
    {
        $this->fonts = $fonts;

        return $this;
    }

    /**
     * Get the fonts.
     *
     * @return array<string>
     */
    public function getFonts(): array
    {
        $fonts = $this->evaluate($this->fonts);

        if (empty($fonts)) {
            return config('filament-image-editor.draw.fonts', [
                'Arial', 'Helvetica', 'Georgia', 'Times New Roman',
                'Courier New', 'Verdana', 'Trebuchet MS',
            ]);
        }

        return $fonts;
    }

    /**
     * Disable drawing entirely.
     */
    public function disableDrawing(bool|Closure $disable = true): static
    {
        $this->drawingDisabled = $disable;

        return $this;
    }

    /**
     * Check if drawing is disabled.
     */
    public function isDrawingDisabled(): bool
    {
        return $this->evaluate($this->drawingDisabled);
    }
}
