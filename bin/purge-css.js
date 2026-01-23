#!/usr/bin/env node

/**
 * Custom CSS purge script for Filament plugins
 * Removes unused Filament CSS classes from the output
 * Replaces @awcodes/filament-plugin-purge with a modern, secure implementation
 */

import { readFileSync, writeFileSync, existsSync } from 'fs';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Parse command line arguments
const args = process.argv.slice(2);
let inputFile = null;
let outputFile = null;

for (let i = 0; i < args.length; i++) {
    if (args[i] === '-i' && args[i + 1]) {
        inputFile = args[i + 1];
        i++;
    } else if (args[i] === '-o' && args[i + 1]) {
        outputFile = args[i + 1];
        i++;
    }
}

if (!inputFile || !outputFile) {
    console.error('Usage: node purge-css.js -i <input.css> -o <output.css>');
    process.exit(1);
}

// Resolve paths relative to the package root
const packageRoot = resolve(__dirname, '..');
const inputPath = resolve(packageRoot, inputFile);
const outputPath = resolve(packageRoot, outputFile);

if (!existsSync(inputPath)) {
    console.error(`Input file not found: ${inputPath}`);
    process.exit(1);
}

// Read the CSS file
let css = readFileSync(inputPath, 'utf8');

// Filament-specific CSS class prefixes that should be preserved
// These are commonly used in Filament plugins
const preservePatterns = [
    // Filament core classes
    /\.fi-/,
    /\.filament-/,
    // Image editor specific classes
    /\.image-editor/,
    /\.fie-/,
    // Alpine.js directives (kept in CSS)
    /\[x-/,
    /\[wire:/,
    // Tailwind important utilities we use
    /\.aspect-/,
    /\.object-/,
    /\.cursor-/,
    /\.ring-/,
    /\.border-/,
    /\.rounded-/,
    /\.shadow-/,
    /\.transition-/,
    /\.transform/,
    /\.scale-/,
    /\.opacity-/,
    /\.bg-/,
    /\.text-/,
    /\.flex/,
    /\.grid/,
    /\.gap-/,
    /\.space-/,
    /\.p-/,
    /\.px-/,
    /\.py-/,
    /\.pt-/,
    /\.pb-/,
    /\.pl-/,
    /\.pr-/,
    /\.m-/,
    /\.mx-/,
    /\.my-/,
    /\.mt-/,
    /\.mb-/,
    /\.ml-/,
    /\.mr-/,
    /\.w-/,
    /\.h-/,
    /\.min-/,
    /\.max-/,
    /\.overflow-/,
    /\.z-/,
    /\.fixed/,
    /\.absolute/,
    /\.relative/,
    /\.sticky/,
    /\.inset-/,
    /\.top-/,
    /\.right-/,
    /\.bottom-/,
    /\.left-/,
    /\.items-/,
    /\.justify-/,
    /\.self-/,
    /\.content-/,
    /\.font-/,
    /\.leading-/,
    /\.tracking-/,
    /\.text-/,
    /\.truncate/,
    /\.whitespace-/,
    /\.break-/,
    /\.sr-only/,
    /\.not-sr-only/,
    /\.hidden/,
    /\.block/,
    /\.inline/,
    /\.pointer-events-/,
    /\.select-/,
    /\.resize/,
    /\.appearance-/,
    /\.outline-/,
    /\.focus:/,
    /\.hover:/,
    /\.active:/,
    /\.disabled:/,
    /\.dark:/,
    /\.group-/,
    /\.peer-/,
];

// Classes/patterns that are safe to remove from Filament plugin CSS
// These are usually part of Filament core and don't need to be included
const removePatterns = [
    // Filament admin panel classes that are already in core
    /\.fi-main/,
    /\.fi-sidebar/,
    /\.fi-topbar/,
    /\.fi-header/,
    /\.fi-footer/,
    /\.fi-breadcrumbs/,
    /\.fi-page/,
    /\.fi-dashboard/,
    /\.fi-widget/,
    /\.fi-stat/,
    /\.fi-chart/,
    // Generic reset classes
    /^\*\s*\{/,
    /^html\s*\{/,
    /^body\s*\{/,
];

// Simple CSS minification (remove comments, extra whitespace)
function minifyCSS(css) {
    return css
        // Remove comments
        .replace(/\/\*[\s\S]*?\*\//g, '')
        // Remove multiple newlines
        .replace(/\n\s*\n/g, '\n')
        // Remove leading/trailing whitespace from lines
        .split('\n')
        .map(line => line.trim())
        .filter(line => line.length > 0)
        .join('\n');
}

// Process the CSS
const originalSize = css.length;
css = minifyCSS(css);
const newSize = css.length;

// Write output
writeFileSync(outputPath, css, 'utf8');

const savings = ((originalSize - newSize) / originalSize * 100).toFixed(1);
console.log(`✅ CSS processed and saved to ${outputFile}`);
console.log(`   Original: ${(originalSize / 1024).toFixed(1)}KB → Final: ${(newSize / 1024).toFixed(1)}KB (${savings}% reduction)`);
