/**
 * Filament Image Editor - Main Entry Point
 *
 * This file exports the Alpine.js component that powers the image editor.
 * Filament's AlpineComponent asset type expects a default export function.
 */

import { ImageEditor } from './ImageEditor.js';

/**
 * Export the component factory function for Filament's AlpineComponent registration.
 * This follows the same pattern as other Filament packages.
 */
export default function imageEditorComponent(config) {
    return ImageEditor(config);
}

// Also expose globally for programmatic usage
if (typeof window !== 'undefined') {
    window.FilamentImageEditor = {
        open: (options) => {
            // Dispatch event to open editor with options
            window.dispatchEvent(new CustomEvent('open-image-editor', {
                detail: options,
            }));
        },
    };
}
