/**
 * Export utilities for canvas to image conversion
 */

/**
 * Export the canvas to a Blob
 *
 * @param {fabric.Canvas} canvas - The Fabric.js canvas
 * @param {fabric.Image} originalImage - The original image for full-resolution export
 * @param {Object} options - Export options
 * @returns {Promise<Blob>}
 */
export async function exportCanvas(canvas, originalImage, options = {}) {
    const {
        format = 'jpeg',
        quality = 0.92,
        maxWidth,
        maxHeight,
        cropArea,
        rotation = 0,
        flipH = false,
        flipV = false,
        filters = {},
    } = options;

    // Safety check for null originalImage
    if (!originalImage) {
        throw new Error('Original image is not available');
    }

    const origWidth = originalImage.width;
    const origHeight = originalImage.height;
    const canvasWidth = canvas.width;
    const canvasHeight = canvas.height;

    // Calculate the scale factor between original image and canvas display
    const scale = origWidth / canvasWidth;

    let exportCanvas;
    let ctx;

    if (rotation !== 0 || flipH || flipV) {
        // For any rotation or flip, render the transformed image first, then crop
        // This ensures the crop area (defined on the transformed view) maps correctly
        const result = exportWithTransformation(
            canvas, originalImage, cropArea, rotation, flipH, flipV, scale
        );
        exportCanvas = result.canvas;
        ctx = result.ctx;
    } else {
        // No rotation or flip - can crop directly from original for best quality
        const result = exportWithoutTransformation(
            originalImage, cropArea, canvasWidth, canvasHeight
        );
        exportCanvas = result.canvas;
        ctx = result.ctx;
    }

    // Apply max dimension constraints
    let finalCanvas = exportCanvas;
    if (maxWidth || maxHeight) {
        finalCanvas = applyMaxDimensions(exportCanvas, maxWidth, maxHeight);
    }

    // Use willReadFrequently for better performance with filter operations (getImageData)
    const finalCtx = finalCanvas.getContext('2d', { willReadFrequently: true });

    // Apply filters
    if (filters.preset && filters.preset !== 'original') {
        applyFilterToCanvas(finalCtx, finalCanvas, filters.preset);
    }

    if (filters.adjustments) {
        applyAdjustmentsToCanvas(finalCtx, finalCanvas, filters.adjustments);
    }

    // Draw annotations from fabric canvas
    await drawAnnotations(canvas, finalCtx, finalCanvas, cropArea, {
        scaleX: finalCanvas.width / (cropArea?.width || canvasWidth),
        scaleY: finalCanvas.height / (cropArea?.height || canvasHeight),
    });

    // Convert to blob
    return new Promise((resolve, reject) => {
        const mimeType = getMimeType(format);

        finalCanvas.toBlob(
            (blob) => {
                if (blob) {
                    resolve(blob);
                } else {
                    reject(new Error('Failed to create blob'));
                }
            },
            mimeType,
            quality
        );
    });
}

/**
 * Export with transformation (rotation and/or flip)
 * This renders the transformed view at full resolution, then crops
 */
function exportWithTransformation(canvas, originalImage, cropArea, rotation, flipH, flipV, baseScale) {
    const origWidth = originalImage.width;
    const origHeight = originalImage.height;
    const canvasWidth = canvas.width;
    const canvasHeight = canvas.height;
    const radians = (rotation * Math.PI) / 180;

    // When the canvas has been rotated 90/270 degrees, its dimensions are swapped
    // We need to calculate scale based on the unrotated canvas dimensions
    const absRotation = Math.abs(rotation % 360);
    const isRotated90or270 = absRotation === 90 || absRotation === 270;

    // Get the unrotated canvas dimensions (what canvas size would be without rotation)
    const unrotatedCanvasWidth = isRotated90or270 ? canvasHeight : canvasWidth;
    const unrotatedCanvasHeight = isRotated90or270 ? canvasWidth : canvasHeight;

    // Calculate correct scale based on unrotated dimensions
    const scale = origWidth / unrotatedCanvasWidth;

    // The Fabric.js canvas shows the image rotated within fixed canvas bounds.
    // We need to recreate this view at full resolution.

    // Create a full-resolution canvas matching the display canvas aspect
    const fullResWidth = Math.round(canvasWidth * scale);
    const fullResHeight = Math.round(canvasHeight * scale);

    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = fullResWidth;
    tempCanvas.height = fullResHeight;
    const tempCtx = tempCanvas.getContext('2d', { willReadFrequently: true });

    // Fill with transparent/white background
    tempCtx.fillStyle = '#ffffff';
    tempCtx.fillRect(0, 0, fullResWidth, fullResHeight);

    // Draw the image centered and rotated (matching Fabric.js behavior)
    tempCtx.save();
    tempCtx.translate(fullResWidth / 2, fullResHeight / 2);
    tempCtx.rotate(radians);

    // Apply flip
    const flipScaleX = flipH ? -1 : 1;
    const flipScaleY = flipV ? -1 : 1;
    tempCtx.scale(flipScaleX, flipScaleY);

    // Draw image centered
    tempCtx.drawImage(
        originalImage.getElement(),
        -origWidth / 2,
        -origHeight / 2,
        origWidth,
        origHeight
    );
    tempCtx.restore();

    // Now crop from this rendered canvas
    let cropX = 0;
    let cropY = 0;
    let cropW = fullResWidth;
    let cropH = fullResHeight;

    if (cropArea) {
        // Scale crop area to full resolution
        cropX = Math.round(cropArea.left * scale);
        cropY = Math.round(cropArea.top * scale);
        cropW = Math.round(cropArea.width * scale);
        cropH = Math.round(cropArea.height * scale);
    }

    // Create final export canvas with crop dimensions
    const exportCanvas = document.createElement('canvas');
    exportCanvas.width = cropW;
    exportCanvas.height = cropH;
    const ctx = exportCanvas.getContext('2d', { willReadFrequently: true });

    // Copy the cropped region
    ctx.drawImage(
        tempCanvas,
        cropX, cropY, cropW, cropH,
        0, 0, cropW, cropH
    );

    return { canvas: exportCanvas, ctx };
}

/**
 * Export without transformation (no rotation or flip)
 * This can crop directly from the original image for best quality
 */
function exportWithoutTransformation(originalImage, cropArea, canvasWidth, canvasHeight) {
    const origWidth = originalImage.width;
    const origHeight = originalImage.height;

    // Calculate crop area in original image coordinates
    let cropX = 0;
    let cropY = 0;
    let cropW = origWidth;
    let cropH = origHeight;

    if (cropArea) {
        const scaleX = origWidth / canvasWidth;
        const scaleY = origHeight / canvasHeight;

        cropX = Math.round(cropArea.left * scaleX);
        cropY = Math.round(cropArea.top * scaleY);
        cropW = Math.round(cropArea.width * scaleX);
        cropH = Math.round(cropArea.height * scaleY);
    }

    // Create export canvas
    const exportCanvas = document.createElement('canvas');
    exportCanvas.width = cropW;
    exportCanvas.height = cropH;
    const ctx = exportCanvas.getContext('2d', { willReadFrequently: true });

    // Draw original image with crop (no transformations)
    ctx.drawImage(
        originalImage.getElement(),
        cropX, cropY, cropW, cropH,
        0, 0, cropW, cropH
    );

    return { canvas: exportCanvas, ctx };
}

/**
 * Apply max dimension constraints
 */
function applyMaxDimensions(sourceCanvas, maxWidth, maxHeight) {
    let targetWidth = sourceCanvas.width;
    let targetHeight = sourceCanvas.height;

    if (maxWidth && targetWidth > maxWidth) {
        const scale = maxWidth / targetWidth;
        targetWidth = maxWidth;
        targetHeight = Math.round(targetHeight * scale);
    }

    if (maxHeight && targetHeight > maxHeight) {
        const scale = maxHeight / targetHeight;
        targetHeight = maxHeight;
        targetWidth = Math.round(targetWidth * scale);
    }

    if (targetWidth === sourceCanvas.width && targetHeight === sourceCanvas.height) {
        return sourceCanvas;
    }

    const resizedCanvas = document.createElement('canvas');
    resizedCanvas.width = targetWidth;
    resizedCanvas.height = targetHeight;
    const ctx = resizedCanvas.getContext('2d', { willReadFrequently: true });

    ctx.drawImage(sourceCanvas, 0, 0, targetWidth, targetHeight);

    return resizedCanvas;
}

/**
 * Draw annotations from Fabric canvas to export canvas
 * Uses Fabric.js's built-in rendering for accurate reproduction
 * Renders at full export resolution to avoid quality loss when scaling up
 */
async function drawAnnotations(fabricCanvas, ctx, exportCanvas, cropArea, scale) {
    const objects = fabricCanvas.getObjects();

    // Filter out background and crop elements
    const annotationObjects = objects.filter(obj => {
        if (obj === fabricCanvas.backgroundImage) return false;
        if (obj._isCropElement) return false;
        return true;
    });

    if (annotationObjects.length === 0) return;

    // Calculate the scale factor from canvas display size to export size
    // This allows us to render annotations at full resolution
    const canvasWidth = fabricCanvas.width;
    const canvasHeight = fabricCanvas.height;

    // Calculate crop offset in canvas coordinates
    const offsetX = cropArea?.left || 0;
    const offsetY = cropArea?.top || 0;
    const cropW = cropArea?.width || canvasWidth;
    const cropH = cropArea?.height || canvasHeight;

    // Calculate the scale factor to render at export resolution
    const renderScaleX = exportCanvas.width / cropW;
    const renderScaleY = exportCanvas.height / cropH;

    // Save the current context state
    ctx.save();

    // Apply the transformation to render at full resolution:
    // 1. Scale up to export size
    // 2. Translate to account for crop offset
    ctx.scale(renderScaleX, renderScaleY);
    ctx.translate(-offsetX, -offsetY);

    // Render each object using Fabric's built-in render method
    // The objects will be rendered at full export resolution because
    // we've scaled the context, so strokes and shapes remain crisp
    for (const obj of annotationObjects) {
        ctx.save();

        // Handle eraser paths - they need destination-out composite operation
        // We must manually render these because Fabric caches the stroke color
        if (obj._isEraserPath === true && obj.path) {
            ctx.globalCompositeOperation = 'destination-out';
            renderEraserPath(ctx, obj);
        } else {
            obj.render(ctx);
        }

        ctx.restore();
    }

    // Restore the context state
    ctx.restore();
}

/**
 * Manually render an eraser path with destination-out composite operation
 * This is needed because Fabric.js caches the visual appearance and won't
 * respect stroke color changes after the path is created
 */
function renderEraserPath(ctx, obj) {
    ctx.save();

    // Apply the object's transformation matrix
    const m = obj.calcTransformMatrix();
    ctx.transform(m[0], m[1], m[2], m[3], m[4], m[5]);

    // Set up stroke properties for erasing
    ctx.strokeStyle = 'rgba(0,0,0,1)';
    ctx.lineWidth = obj.strokeWidth || 20;
    ctx.lineCap = obj.strokeLineCap || 'round';
    ctx.lineJoin = obj.strokeLineJoin || 'round';

    // Draw the path
    ctx.beginPath();

    // Fabric.js paths have a pathOffset that indicates the path's origin
    const pathOffsetX = obj.pathOffset?.x || 0;
    const pathOffsetY = obj.pathOffset?.y || 0;

    for (const command of obj.path) {
        switch (command[0]) {
            case 'M':
                ctx.moveTo(command[1] - pathOffsetX, command[2] - pathOffsetY);
                break;
            case 'L':
                ctx.lineTo(command[1] - pathOffsetX, command[2] - pathOffsetY);
                break;
            case 'Q':
                ctx.quadraticCurveTo(
                    command[1] - pathOffsetX, command[2] - pathOffsetY,
                    command[3] - pathOffsetX, command[4] - pathOffsetY
                );
                break;
            case 'C':
                ctx.bezierCurveTo(
                    command[1] - pathOffsetX, command[2] - pathOffsetY,
                    command[3] - pathOffsetX, command[4] - pathOffsetY,
                    command[5] - pathOffsetX, command[6] - pathOffsetY
                );
                break;
            case 'z':
            case 'Z':
                ctx.closePath();
                break;
        }
    }

    ctx.stroke();
    ctx.restore();
}

/**
 * Draw a single object to the export canvas
 */
async function drawObject(ctx, obj, offsetX, offsetY, scale) {
    // Save context state
    ctx.save();

    // Get object center position (Fabric uses center origin by default for most ops)
    const centerX = obj.left + (obj.width * obj.scaleX) / 2;
    const centerY = obj.top + (obj.height * obj.scaleY) / 2;

    // Apply object transformations with crop offset
    ctx.translate(
        (obj.left - offsetX) * scale.scaleX,
        (obj.top - offsetY) * scale.scaleY
    );
    ctx.rotate(((obj.angle || 0) * Math.PI) / 180);
    ctx.scale(obj.scaleX || 1, obj.scaleY || 1);

    // Also apply export scale
    ctx.scale(scale.scaleX / (obj.scaleX || 1), scale.scaleY / (obj.scaleY || 1));

    // Draw based on object type
    switch (obj.type) {
        case 'group':
            await drawGroup(ctx, obj, offsetX, offsetY, scale);
            break;
        case 'path':
            drawPath(ctx, obj);
            break;
        case 'rect':
            drawRect(ctx, obj);
            break;
        case 'ellipse':
            drawEllipse(ctx, obj);
            break;
        case 'line':
            drawLine(ctx, obj);
            break;
        case 'triangle':
            drawTriangle(ctx, obj);
            break;
        case 'text':
        case 'i-text':
        case 'textbox':
            drawText(ctx, obj);
            break;
        default:
            // For unknown types, try to render using Fabric's toDataURL
            await drawFabricObject(ctx, obj);
    }

    ctx.restore();
}

/**
 * Draw a group of objects (e.g., arrow = line + triangle)
 */
async function drawGroup(ctx, group, offsetX, offsetY, scale) {
    const objects = group.getObjects ? group.getObjects() : [];

    for (const obj of objects) {
        ctx.save();

        // Position relative to group center
        ctx.translate(obj.left || 0, obj.top || 0);
        ctx.rotate(((obj.angle || 0) * Math.PI) / 180);
        ctx.scale(obj.scaleX || 1, obj.scaleY || 1);

        // Draw the object
        switch (obj.type) {
            case 'line':
                drawLine(ctx, obj);
                break;
            case 'triangle':
                drawTriangle(ctx, obj);
                break;
            case 'rect':
                drawRect(ctx, obj);
                break;
            case 'ellipse':
                drawEllipse(ctx, obj);
                break;
            case 'path':
                drawPath(ctx, obj);
                break;
            default:
                await drawFabricObject(ctx, obj);
        }

        ctx.restore();
    }
}

function drawPath(ctx, obj) {
    if (!obj.path) return;

    // Handle eraser paths with destination-out composite
    const isEraser = obj._isEraserPath === true;
    if (isEraser) {
        ctx.globalCompositeOperation = 'destination-out';
        ctx.strokeStyle = 'rgba(0,0,0,1)';
    } else {
        ctx.strokeStyle = obj.stroke || '#000000';
    }

    ctx.beginPath();
    ctx.lineWidth = obj.strokeWidth || 1;
    ctx.lineCap = obj.strokeLineCap || 'round';
    ctx.lineJoin = obj.strokeLineJoin || 'round';

    // Fabric.js paths have a pathOffset that indicates the path's origin
    // We need to offset all coordinates by this amount to draw correctly
    const pathOffsetX = obj.pathOffset?.x || 0;
    const pathOffsetY = obj.pathOffset?.y || 0;

    const path = obj.path;
    for (const command of path) {
        switch (command[0]) {
            case 'M':
                ctx.moveTo(command[1] - pathOffsetX, command[2] - pathOffsetY);
                break;
            case 'L':
                ctx.lineTo(command[1] - pathOffsetX, command[2] - pathOffsetY);
                break;
            case 'Q':
                ctx.quadraticCurveTo(
                    command[1] - pathOffsetX, command[2] - pathOffsetY,
                    command[3] - pathOffsetX, command[4] - pathOffsetY
                );
                break;
            case 'C':
                ctx.bezierCurveTo(
                    command[1] - pathOffsetX, command[2] - pathOffsetY,
                    command[3] - pathOffsetX, command[4] - pathOffsetY,
                    command[5] - pathOffsetX, command[6] - pathOffsetY
                );
                break;
            case 'z':
            case 'Z':
                ctx.closePath();
                break;
        }
    }

    ctx.stroke();

    // Restore composite operation
    if (isEraser) {
        ctx.globalCompositeOperation = 'source-over';
    }
}

function drawRect(ctx, obj) {
    const width = obj.width;
    const height = obj.height;

    if (obj.fill && obj.fill !== 'transparent') {
        ctx.fillStyle = obj.fill;
        ctx.fillRect(-width / 2, -height / 2, width, height);
    }

    if (obj.stroke) {
        ctx.strokeStyle = obj.stroke;
        ctx.lineWidth = obj.strokeWidth || 1;
        ctx.strokeRect(-width / 2, -height / 2, width, height);
    }
}

function drawEllipse(ctx, obj) {
    const rx = obj.rx;
    const ry = obj.ry;

    ctx.beginPath();
    ctx.ellipse(0, 0, rx, ry, 0, 0, Math.PI * 2);

    if (obj.fill && obj.fill !== 'transparent') {
        ctx.fillStyle = obj.fill;
        ctx.fill();
    }

    if (obj.stroke) {
        ctx.strokeStyle = obj.stroke;
        ctx.lineWidth = obj.strokeWidth || 1;
        ctx.stroke();
    }
}

function drawLine(ctx, obj) {
    ctx.beginPath();
    ctx.strokeStyle = obj.stroke || '#000000';
    ctx.lineWidth = obj.strokeWidth || 1;
    ctx.lineCap = 'round';

    // Fabric.js Line stores coordinates relative to object's bounding box
    // x1,y1,x2,y2 are the actual line endpoints
    // For a line, the object's width/height define the bounding box
    // and left/top position the bounding box

    // The line coordinates are relative to the object's center
    const x1 = obj.x1 - obj.width / 2;
    const y1 = obj.y1 - obj.height / 2;
    const x2 = obj.x2 - obj.width / 2;
    const y2 = obj.y2 - obj.height / 2;

    ctx.moveTo(x1, y1);
    ctx.lineTo(x2, y2);
    ctx.stroke();
}

function drawTriangle(ctx, obj) {
    const width = obj.width;
    const height = obj.height;

    ctx.beginPath();
    // Triangle points: top center, bottom left, bottom right
    ctx.moveTo(0, -height / 2);
    ctx.lineTo(-width / 2, height / 2);
    ctx.lineTo(width / 2, height / 2);
    ctx.closePath();

    if (obj.fill && obj.fill !== 'transparent') {
        ctx.fillStyle = obj.fill;
        ctx.fill();
    }

    if (obj.stroke) {
        ctx.strokeStyle = obj.stroke;
        ctx.lineWidth = obj.strokeWidth || 1;
        ctx.stroke();
    }
}

function drawText(ctx, obj) {
    const fontStyle = obj.fontStyle || 'normal';
    const fontWeight = obj.fontWeight || 'normal';
    const fontSize = obj.fontSize || 24;
    const fontFamily = obj.fontFamily || 'Arial';

    ctx.font = `${fontStyle} ${fontWeight} ${fontSize}px ${fontFamily}`;
    ctx.fillStyle = obj.fill || '#000000';
    ctx.textBaseline = 'top';

    // Handle text alignment
    const textAlign = obj.textAlign || 'left';
    ctx.textAlign = 'left'; // Always draw from left, we'll position manually

    // Calculate x position based on alignment
    let textX = 0;
    if (textAlign === 'center') {
        textX = 0; // Already centered by transform
    } else if (textAlign === 'right') {
        textX = -obj.width;
    }

    // IText and Textbox may have multiple lines
    const text = obj.text || '';
    const lines = text.split('\n');
    const lineHeight = fontSize * (obj.lineHeight || 1.16);

    lines.forEach((line, index) => {
        ctx.fillText(line, textX, index * lineHeight);
    });
}

async function drawFabricObject(ctx, obj) {
    // Fallback: render the object to a temporary canvas and draw it
    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = obj.width * obj.scaleX;
    tempCanvas.height = obj.height * obj.scaleY;

    const tempCtx = tempCanvas.getContext('2d', { willReadFrequently: true });
    obj.render(tempCtx);

    ctx.drawImage(tempCanvas, -obj.width / 2, -obj.height / 2);
}

/**
 * Apply filter preset to canvas
 * Matches CSS filter behavior for consistent preview/export results
 */
function applyFilterToCanvas(ctx, canvas, preset) {
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imageData.data;

    // Helper functions matching CSS filter behavior
    const applyBrightness = (r, g, b, factor) => ({
        r: r * factor,
        g: g * factor,
        b: b * factor
    });

    const applyContrast = (r, g, b, factor) => ({
        r: (r - 128) * factor + 128,
        g: (g - 128) * factor + 128,
        b: (b - 128) * factor + 128
    });

    const applySaturate = (r, g, b, factor) => {
        const gray = 0.2126 * r + 0.7152 * g + 0.0722 * b;
        return {
            r: gray + factor * (r - gray),
            g: gray + factor * (g - gray),
            b: gray + factor * (b - gray)
        };
    };

    const applySepia = (r, g, b, amount = 1) => {
        // Sepia matrix
        const sr = r * (1 - 0.607 * amount) + g * 0.769 * amount + b * 0.189 * amount;
        const sg = r * 0.349 * amount + g * (1 - 0.314 * amount) + b * 0.168 * amount;
        const sb = r * 0.272 * amount + g * 0.534 * amount + b * (1 - 0.869 * amount);
        return { r: sr, g: sg, b: sb };
    };

    const applyHueRotate = (r, g, b, degrees) => {
        const rad = degrees * Math.PI / 180;
        const cos = Math.cos(rad);
        const sin = Math.sin(rad);
        const nr = r * (0.213 + cos * 0.787 - sin * 0.213) +
                   g * (0.715 - cos * 0.715 - sin * 0.715) +
                   b * (0.072 - cos * 0.072 + sin * 0.928);
        const ng = r * (0.213 - cos * 0.213 + sin * 0.143) +
                   g * (0.715 + cos * 0.285 + sin * 0.140) +
                   b * (0.072 - cos * 0.072 - sin * 0.283);
        const nb = r * (0.213 - cos * 0.213 - sin * 0.787) +
                   g * (0.715 - cos * 0.715 + sin * 0.715) +
                   b * (0.072 + cos * 0.928 + sin * 0.072);
        return { r: nr, g: ng, b: nb };
    };

    for (let i = 0; i < data.length; i += 4) {
        let r = data[i];
        let g = data[i + 1];
        let b = data[i + 2];
        let result;

        switch (preset) {
            case 'grayscale':
                // grayscale(100%)
                const gray = 0.2126 * r + 0.7152 * g + 0.0722 * b;
                r = g = b = gray;
                break;

            case 'sepia':
                // sepia(80%)
                result = applySepia(r, g, b, 0.8);
                r = result.r; g = result.g; b = result.b;
                break;

            case 'vintage':
                // sepia(30%) contrast(90%) brightness(105%)
                result = applySepia(r, g, b, 0.3);
                r = result.r; g = result.g; b = result.b;
                result = applyContrast(r, g, b, 0.9);
                r = result.r; g = result.g; b = result.b;
                result = applyBrightness(r, g, b, 1.05);
                r = result.r; g = result.g; b = result.b;
                break;

            case 'warm':
                // sepia(20%) saturate(110%)
                result = applySepia(r, g, b, 0.2);
                r = result.r; g = result.g; b = result.b;
                result = applySaturate(r, g, b, 1.1);
                r = result.r; g = result.g; b = result.b;
                break;

            case 'cool':
                // saturate(90%) hue-rotate(10deg)
                result = applySaturate(r, g, b, 0.9);
                r = result.r; g = result.g; b = result.b;
                result = applyHueRotate(r, g, b, 10);
                r = result.r; g = result.g; b = result.b;
                break;

            case 'high-contrast':
                // contrast(130%) saturate(110%)
                result = applyContrast(r, g, b, 1.3);
                r = result.r; g = result.g; b = result.b;
                result = applySaturate(r, g, b, 1.1);
                r = result.r; g = result.g; b = result.b;
                break;

            case 'fade':
                // contrast(80%) brightness(110%)
                result = applyContrast(r, g, b, 0.8);
                r = result.r; g = result.g; b = result.b;
                result = applyBrightness(r, g, b, 1.1);
                r = result.r; g = result.g; b = result.b;
                break;

            case 'dramatic':
                // contrast(140%) saturate(90%)
                result = applyContrast(r, g, b, 1.4);
                r = result.r; g = result.g; b = result.b;
                result = applySaturate(r, g, b, 0.9);
                r = result.r; g = result.g; b = result.b;
                break;

            case 'vivid':
                // contrast(120%) saturate(140%)
                result = applyContrast(r, g, b, 1.2);
                r = result.r; g = result.g; b = result.b;
                result = applySaturate(r, g, b, 1.4);
                r = result.r; g = result.g; b = result.b;
                break;
        }

        // Clamp values
        data[i] = Math.max(0, Math.min(255, Math.round(r)));
        data[i + 1] = Math.max(0, Math.min(255, Math.round(g)));
        data[i + 2] = Math.max(0, Math.min(255, Math.round(b)));
    }

    ctx.putImageData(imageData, 0, 0);
}

/**
 * Apply adjustments to canvas
 * Uses the same algorithm as CSS filters for consistent results
 */
function applyAdjustmentsToCanvas(ctx, canvas, adjustments) {
    const { brightness = 0, contrast = 0, saturation = 0 } = adjustments;

    if (brightness === 0 && contrast === 0 && saturation === 0) {
        return;
    }

    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imageData.data;

    // CSS filter brightness(x%) multiplies by x/100
    // Our input: -100 to 100, where 0 = no change
    // brightness: 50 means brightness(150%) = multiply by 1.5
    // brightness: -50 means brightness(50%) = multiply by 0.5
    const brightnessFactor = (100 + brightness) / 100;

    // CSS filter contrast(x%) works similarly
    // contrast: 50 means contrast(150%) = 1.5x contrast
    // contrast: -50 means contrast(50%) = 0.5x contrast
    const contrastFactor = (100 + contrast) / 100;

    // CSS filter saturate(x%) works similarly
    const saturationFactor = (100 + saturation) / 100;

    for (let i = 0; i < data.length; i += 4) {
        let r = data[i];
        let g = data[i + 1];
        let b = data[i + 2];

        // Apply brightness (multiply, matching CSS filter behavior)
        r *= brightnessFactor;
        g *= brightnessFactor;
        b *= brightnessFactor;

        // Apply contrast (scale around midpoint 128)
        r = ((r - 128) * contrastFactor) + 128;
        g = ((g - 128) * contrastFactor) + 128;
        b = ((b - 128) * contrastFactor) + 128;

        // Apply saturation (interpolate between grayscale and color)
        const gray = 0.2126 * r + 0.7152 * g + 0.0722 * b; // Rec. 709 luma
        r = gray + saturationFactor * (r - gray);
        g = gray + saturationFactor * (g - gray);
        b = gray + saturationFactor * (b - gray);

        // Clamp values
        data[i] = Math.max(0, Math.min(255, Math.round(r)));
        data[i + 1] = Math.max(0, Math.min(255, Math.round(g)));
        data[i + 2] = Math.max(0, Math.min(255, Math.round(b)));
    }

    ctx.putImageData(imageData, 0, 0);
}

/**
 * Get MIME type from format string
 */
function getMimeType(format) {
    switch (format.toLowerCase()) {
        case 'png':
            return 'image/png';
        case 'webp':
            return 'image/webp';
        case 'jpeg':
        case 'jpg':
        default:
            return 'image/jpeg';
    }
}
