/**
 * FilterTool - Handles filter presets and adjustments
 */

import * as fabric from 'fabric';

export class FilterTool {
    constructor(editor) {
        this.editor = editor;

        // Store original image data for non-destructive editing
        this.originalImageData = null;

        // Current filter state
        this.currentPreset = 'original';
        this.adjustments = {
            brightness: 0,
            contrast: 0,
            saturation: 0,
            exposure: 0,
            warmth: 0,
        };
    }

    /**
     * Get the canvas instance from the editor
     * Always use this getter instead of caching canvas reference,
     * because canvas can be disposed and recreated when loading new images
     */
    get canvas() {
        return this.editor.canvas;
    }

    /**
     * Activate the filter tool
     */
    activate() {
        if (!this.canvas) return;

        // Disable drawing mode
        this.canvas.isDrawingMode = false;

        // Enable selection for viewing but not for editing
        this.canvas.selection = false;
        this.canvas.forEachObject((obj) => {
            obj.selectable = false;
            obj.evented = false;
        });

        // Store original image data if not already stored
        if (!this.originalImageData && this.editor.backgroundImage) {
            this.storeOriginalImage();
        }
    }

    /**
     * Deactivate the filter tool
     */
    deactivate() {
        // Nothing specific to clean up
    }

    /**
     * Store the original image data for non-destructive editing
     */
    storeOriginalImage() {
        const bg = this.editor.backgroundImage;
        if (!bg) return;

        const element = bg.getElement();
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = element.width;
        tempCanvas.height = element.height;

        const ctx = tempCanvas.getContext('2d', { willReadFrequently: true });
        ctx.drawImage(element, 0, 0);

        this.originalImageData = ctx.getImageData(0, 0, tempCanvas.width, tempCanvas.height);
    }

    /**
     * Apply a filter preset
     */
    applyPreset(preset) {
        this.currentPreset = preset;
        this.applyFiltersToCanvas();
    }

    /**
     * Apply adjustments
     */
    applyAdjustments(adjustments) {
        this.adjustments = { ...this.adjustments, ...adjustments };
        this.applyFiltersToCanvas();
    }

    /**
     * Apply all filters to the canvas
     * Uses CSS filters for preview to avoid Fabric.js background image issues
     * Actual Fabric.js filters are applied during export
     */
    applyFiltersToCanvas() {
        if (!this.canvas) return;

        // Build CSS filter string
        const filterString = this.buildFilterString();

        // Apply CSS filter to the canvas element for preview
        // This avoids issues with Fabric.js filters on scaled background images
        const canvasEl = this.canvas.lowerCanvasEl;
        if (canvasEl) {
            canvasEl.style.filter = filterString;
        }

        // Also apply to upper canvas (for drawings layer)
        const upperCanvasEl = this.canvas.upperCanvasEl;
        if (upperCanvasEl) {
            upperCanvasEl.style.filter = filterString;
        }
    }

    /**
     * Apply Fabric.js filters to the image
     * Note: Fabric.js 6.x uses fabric.filters.X instead of fabric.Image.filters.X
     */
    applyFabricFilters(image) {
        // Apply preset filter
        switch (this.currentPreset) {
            case 'grayscale':
                image.filters.push(new fabric.filters.Grayscale());
                break;

            case 'sepia':
                image.filters.push(new fabric.filters.Sepia());
                break;

            case 'vintage':
                image.filters.push(new fabric.filters.Vintage());
                break;

            case 'warm':
                // Warm = increase red/yellow, decrease blue
                image.filters.push(new fabric.filters.ColorMatrix({
                    matrix: [
                        1.1, 0, 0, 0, 0.1,
                        0, 1.05, 0, 0, 0.05,
                        0, 0, 0.9, 0, 0,
                        0, 0, 0, 1, 0
                    ]
                }));
                break;

            case 'cool':
                // Cool = increase blue, decrease red
                image.filters.push(new fabric.filters.ColorMatrix({
                    matrix: [
                        0.9, 0, 0, 0, 0,
                        0, 1, 0, 0, 0,
                        0, 0, 1.1, 0, 0.1,
                        0, 0, 0, 1, 0
                    ]
                }));
                break;

            case 'high-contrast':
                image.filters.push(new fabric.filters.Contrast({ contrast: 0.3 }));
                image.filters.push(new fabric.filters.Saturation({ saturation: 0.1 }));
                break;

            case 'fade':
                image.filters.push(new fabric.filters.Contrast({ contrast: -0.2 }));
                image.filters.push(new fabric.filters.Brightness({ brightness: 0.1 }));
                break;

            case 'dramatic':
                image.filters.push(new fabric.filters.Contrast({ contrast: 0.4 }));
                image.filters.push(new fabric.filters.Saturation({ saturation: -0.1 }));
                break;

            case 'vivid':
                image.filters.push(new fabric.filters.Contrast({ contrast: 0.2 }));
                image.filters.push(new fabric.filters.Saturation({ saturation: 0.3 }));
                break;
        }

        // Apply adjustments
        if (this.adjustments.brightness !== 0) {
            image.filters.push(new fabric.filters.Brightness({
                brightness: this.adjustments.brightness / 100,
            }));
        }

        if (this.adjustments.contrast !== 0) {
            image.filters.push(new fabric.filters.Contrast({
                contrast: this.adjustments.contrast / 100,
            }));
        }

        if (this.adjustments.saturation !== 0) {
            image.filters.push(new fabric.filters.Saturation({
                saturation: this.adjustments.saturation / 100,
            }));
        }
    }

    /**
     * Build CSS filter string for preview
     */
    buildFilterString() {
        const filters = [];

        // Preset filters
        switch (this.currentPreset) {
            case 'grayscale':
                filters.push('grayscale(100%)');
                break;
            case 'sepia':
                filters.push('sepia(80%)');
                break;
            case 'vintage':
                filters.push('sepia(30%) contrast(90%) brightness(105%)');
                break;
            case 'warm':
                filters.push('sepia(20%) saturate(110%)');
                break;
            case 'cool':
                filters.push('saturate(90%) hue-rotate(10deg)');
                break;
            case 'high-contrast':
                filters.push('contrast(130%) saturate(110%)');
                break;
            case 'fade':
                filters.push('contrast(80%) brightness(110%)');
                break;
            case 'dramatic':
                filters.push('contrast(140%) saturate(90%)');
                break;
            case 'vivid':
                filters.push('contrast(120%) saturate(140%)');
                break;
        }

        // Adjustments
        if (this.adjustments.brightness !== 0) {
            const value = 100 + this.adjustments.brightness;
            filters.push(`brightness(${value}%)`);
        }

        if (this.adjustments.contrast !== 0) {
            const value = 100 + this.adjustments.contrast;
            filters.push(`contrast(${value}%)`);
        }

        if (this.adjustments.saturation !== 0) {
            const value = 100 + this.adjustments.saturation;
            filters.push(`saturate(${value}%)`);
        }

        return filters.length > 0 ? filters.join(' ') : 'none';
    }

    /**
     * Get CSS filter string for a preset (used in thumbnail previews)
     */
    getFilterCss(preset) {
        switch (preset) {
            case 'original':
                return 'none';
            case 'grayscale':
                return 'grayscale(100%)';
            case 'sepia':
                return 'sepia(80%)';
            case 'vintage':
                return 'sepia(30%) contrast(90%) brightness(105%)';
            case 'warm':
                return 'sepia(20%) saturate(110%)';
            case 'cool':
                return 'saturate(90%) hue-rotate(10deg)';
            case 'high-contrast':
                return 'contrast(130%) saturate(110%)';
            case 'fade':
                return 'contrast(80%) brightness(110%)';
            case 'dramatic':
                return 'contrast(140%) saturate(90%)';
            case 'vivid':
                return 'contrast(120%) saturate(140%)';
            default:
                return 'none';
        }
    }

    /**
     * Reset all filters
     */
    reset() {
        this.currentPreset = 'original';
        this.adjustments = {
            brightness: 0,
            contrast: 0,
            saturation: 0,
            exposure: 0,
            warmth: 0,
        };

        this.applyFiltersToCanvas();
    }
}
