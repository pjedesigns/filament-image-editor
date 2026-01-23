/**
 * CropTool - Handles cropping, rotation, and flip operations
 * Provides a visual crop overlay with draggable handles
 */

import * as fabric from 'fabric';

export class CropTool {
    constructor(editor) {
        this.editor = editor;

        // Crop state
        this.cropRect = null;
        this.cropOverlay = null;
        this.aspectRatio = null;
        this.isDragging = false;
        this.isResizing = false;
        this.currentHandle = null;
        this.startX = 0;
        this.startY = 0;

        // Crop area (in canvas coordinates)
        this.cropArea = null;

        // Rotation state
        this.currentRotation = 0;
        this.fineRotation = 0;

        // Flip state
        this.flippedH = false;
        this.flippedV = false;

        // Bound event handlers
        this.handleMouseDown = this.handleMouseDown.bind(this);
        this.handleMouseMove = this.handleMouseMove.bind(this);
        this.handleMouseUp = this.handleMouseUp.bind(this);
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
     * Activate the crop tool
     */
    activate() {
        if (!this.canvas) return;

        // Disable object selection
        this.canvas.selection = false;
        this.canvas.forEachObject((obj) => {
            obj.selectable = false;
            obj.evented = false;
        });

        // Initialize crop overlay
        this.initializeCropOverlay();

        // Add event listeners
        this.canvas.on('mouse:down', this.handleMouseDown);
        this.canvas.on('mouse:move', this.handleMouseMove);
        this.canvas.on('mouse:up', this.handleMouseUp);
    }

    /**
     * Deactivate the crop tool
     */
    deactivate() {
        if (!this.canvas) return;

        // Remove event listeners
        this.canvas.off('mouse:down', this.handleMouseDown);
        this.canvas.off('mouse:move', this.handleMouseMove);
        this.canvas.off('mouse:up', this.handleMouseUp);

        // Re-enable object selection
        this.canvas.selection = true;
        this.canvas.forEachObject((obj) => {
            if (obj !== this.canvas.backgroundImage && !obj._isCropElement) {
                obj.selectable = true;
                obj.evented = true;
            }
        });

        // Remove crop overlay elements
        this.removeCropOverlay();
    }

    /**
     * Remove all crop overlay elements
     */
    removeCropOverlay() {
        if (this.cropRect) {
            this.canvas.remove(this.cropRect);
            this.cropRect = null;
        }

        // Remove overlay masks
        const objectsToRemove = this.canvas.getObjects().filter(obj => obj._isCropElement);
        objectsToRemove.forEach(obj => this.canvas.remove(obj));

        this.canvas.renderAll();
    }

    /**
     * Initialize the crop overlay with visual rectangle and handles
     */
    initializeCropOverlay() {
        // Use getWidth/getHeight for Fabric.js 6.x compatibility
        const canvasWidth = this.canvas.getWidth();
        const canvasHeight = this.canvas.getHeight();

        // Check if there's a default aspect ratio set
        const defaultAspectRatioLabel = this.editor.currentAspectRatio || 'free';
        const aspectRatios = this.editor.config?.crop?.aspectRatios || {};
        const defaultRatio = aspectRatios[defaultAspectRatioLabel];

        if (defaultRatio && defaultRatio !== null) {
            // Initialize with the default aspect ratio, maximizing to canvas bounds
            this.aspectRatio = defaultRatio;
            this.cropArea = this.calculateMaxCropAreaForRatio(canvasWidth, canvasHeight, defaultRatio);
        } else {
            // No aspect ratio - initialize to full canvas (no padding)
            this.aspectRatio = null;
            this.cropArea = {
                left: 0,
                top: 0,
                width: canvasWidth,
                height: canvasHeight,
            };
        }

        // Update editor state with real dimensions (not canvas dimensions)
        this.updateEditorDimensions();

        // Create the visual crop rectangle
        this.createCropRect();
    }

    /**
     * Calculate the maximum crop area for a given aspect ratio that fits within canvas bounds
     * The crop area will reach the edge of the canvas on at least one axis
     */
    calculateMaxCropAreaForRatio(canvasWidth, canvasHeight, ratio) {
        let cropWidth, cropHeight;

        // Calculate dimensions that fit within canvas while maintaining ratio
        if (ratio > canvasWidth / canvasHeight) {
            // Ratio is wider than canvas - width is the limiting factor
            cropWidth = canvasWidth;
            cropHeight = canvasWidth / ratio;
        } else {
            // Ratio is taller than or equal to canvas - height is the limiting factor
            cropHeight = canvasHeight;
            cropWidth = canvasHeight * ratio;
        }

        // Center the crop area
        return {
            left: (canvasWidth - cropWidth) / 2,
            top: (canvasHeight - cropHeight) / 2,
            width: cropWidth,
            height: cropHeight,
        };
    }

    /**
     * Create the visual crop rectangle with handles
     */
    createCropRect() {
        // Remove existing crop rect if any
        this.removeCropOverlay();

        const { left, top, width, height } = this.cropArea;

        // Create dark overlay masks (4 rectangles around the crop area)
        this.createOverlayMasks();

        // Create the crop rectangle (selection border) with solid white line
        this.cropRect = new fabric.Rect({
            left: left,
            top: top,
            width: width,
            height: height,
            fill: 'transparent',
            stroke: '#ffffff',
            strokeWidth: 2,
            selectable: false,
            evented: false,
            _isCropElement: true,
        });

        this.canvas.add(this.cropRect);

        // Create corner handles
        this.createHandles();

        // Create rule of thirds grid
        this.createGrid();

        // Bring all crop elements to front
        this.canvas.getObjects().forEach(obj => {
            if (obj._isCropElement) {
                this.canvas.bringObjectToFront(obj);
            }
        });

        this.canvas.renderAll();
    }

    /**
     * Create dark overlay masks around the crop area
     * Uses four rectangles to create a "frame" effect, leaving the crop area clear
     */
    createOverlayMasks() {
        const canvasWidth = this.canvas.getWidth();
        const canvasHeight = this.canvas.getHeight();
        const { left, top, width, height } = this.cropArea;

        const overlayColor = 'rgba(0, 0, 0, 0.5)';

        // Create a single overlay that covers the entire canvas
        // with an inverted clip path to create the "hole" for the crop area
        // This is more reliable than 4 separate rectangles

        // Top mask - covers entire width, from top of canvas to top of crop area
        if (top > 0) {
            const topMask = new fabric.Rect({
                left: 0,
                top: 0,
                width: canvasWidth,
                height: top,
                fill: overlayColor,
                selectable: false,
                evented: false,
                objectCaching: false,
                _isCropElement: true,
                _maskType: 'top',
            });
            this.canvas.add(topMask);
        }

        // Bottom mask - covers entire width, from bottom of crop area to bottom of canvas
        const bottomStart = top + height;
        if (bottomStart < canvasHeight) {
            const bottomMask = new fabric.Rect({
                left: 0,
                top: bottomStart,
                width: canvasWidth,
                height: canvasHeight - bottomStart,
                fill: overlayColor,
                selectable: false,
                evented: false,
                objectCaching: false,
                _isCropElement: true,
                _maskType: 'bottom',
            });
            this.canvas.add(bottomMask);
        }

        // Left mask - covers from left edge to left of crop area, between top and bottom masks
        if (left > 0) {
            const leftMask = new fabric.Rect({
                left: 0,
                top: top,
                width: left,
                height: height,
                fill: overlayColor,
                selectable: false,
                evented: false,
                objectCaching: false,
                _isCropElement: true,
                _maskType: 'left',
            });
            this.canvas.add(leftMask);
        }

        // Right mask - covers from right of crop area to right edge, between top and bottom masks
        const rightStart = left + width;
        if (rightStart < canvasWidth) {
            const rightMask = new fabric.Rect({
                left: rightStart,
                top: top,
                width: canvasWidth - rightStart,
                height: height,
                fill: overlayColor,
                selectable: false,
                evented: false,
                objectCaching: false,
                _isCropElement: true,
                _maskType: 'right',
            });
            this.canvas.add(rightMask);
        }
    }

    /**
     * Create resize handles at corners and edges
     */
    createHandles() {
        const { left, top, width, height } = this.cropArea;
        const handleSize = 12;
        const handleColor = '#ffffff';

        const handles = [
            // Corners
            { name: 'tl', x: left, y: top },
            { name: 'tr', x: left + width, y: top },
            { name: 'bl', x: left, y: top + height },
            { name: 'br', x: left + width, y: top + height },
            // Edges
            { name: 'tm', x: left + width / 2, y: top },
            { name: 'bm', x: left + width / 2, y: top + height },
            { name: 'ml', x: left, y: top + height / 2 },
            { name: 'mr', x: left + width, y: top + height / 2 },
        ];

        handles.forEach(h => {
            const handle = new fabric.Rect({
                left: h.x - handleSize / 2,
                top: h.y - handleSize / 2,
                width: handleSize,
                height: handleSize,
                fill: handleColor,
                stroke: '#0ea5e9',
                strokeWidth: 2,
                selectable: false,
                evented: true,
                _isCropElement: true,
                _handleName: h.name,
                hoverCursor: this.getHandleCursor(h.name),
            });

            this.canvas.add(handle);
        });
    }

    /**
     * Create rule of thirds grid lines
     */
    createGrid() {
        const { left, top, width, height } = this.cropArea;
        const lineColor = 'rgba(255, 255, 255, 0.4)';

        // Vertical lines
        for (let i = 1; i <= 2; i++) {
            const x = left + (width / 3) * i;
            const line = new fabric.Line([x, top, x, top + height], {
                stroke: lineColor,
                strokeWidth: 1,
                selectable: false,
                evented: false,
                _isCropElement: true,
            });
            this.canvas.add(line);
        }

        // Horizontal lines
        for (let i = 1; i <= 2; i++) {
            const y = top + (height / 3) * i;
            const line = new fabric.Line([left, y, left + width, y], {
                stroke: lineColor,
                strokeWidth: 1,
                selectable: false,
                evented: false,
                _isCropElement: true,
            });
            this.canvas.add(line);
        }
    }

    /**
     * Get cursor style for handle
     */
    getHandleCursor(handleName) {
        const cursors = {
            tl: 'nw-resize',
            tr: 'ne-resize',
            bl: 'sw-resize',
            br: 'se-resize',
            tm: 'n-resize',
            bm: 's-resize',
            ml: 'w-resize',
            mr: 'e-resize',
        };
        return cursors[handleName] || 'move';
    }

    /**
     * Handle mouse down on canvas
     */
    handleMouseDown(e) {
        const pointer = this.canvas.getPointer(e.e);
        const target = e.target;

        if (target && target._handleName) {
            // Start resizing
            this.isResizing = true;
            this.currentHandle = target._handleName;
            this.startX = pointer.x;
            this.startY = pointer.y;
            this.startCropArea = { ...this.cropArea };
        } else if (this.isInsideCropArea(pointer)) {
            // Start dragging
            this.isDragging = true;
            this.startX = pointer.x;
            this.startY = pointer.y;
            this.startCropArea = { ...this.cropArea };
        }
    }

    /**
     * Handle mouse move on canvas
     */
    handleMouseMove(e) {
        if (!this.isDragging && !this.isResizing) return;

        const pointer = this.canvas.getPointer(e.e);
        const deltaX = pointer.x - this.startX;
        const deltaY = pointer.y - this.startY;

        if (this.isDragging) {
            this.moveCropArea(deltaX, deltaY);
        } else if (this.isResizing) {
            this.resizeCropArea(deltaX, deltaY);
        }

        // Redraw crop overlay
        this.createCropRect();
    }

    /**
     * Handle mouse up on canvas
     */
    handleMouseUp() {
        this.isDragging = false;
        this.isResizing = false;
        this.currentHandle = null;
    }

    /**
     * Check if pointer is inside crop area
     */
    isInsideCropArea(pointer) {
        const { left, top, width, height } = this.cropArea;
        return (
            pointer.x >= left &&
            pointer.x <= left + width &&
            pointer.y >= top &&
            pointer.y <= top + height
        );
    }

    /**
     * Move the crop area
     */
    moveCropArea(deltaX, deltaY) {
        const canvasWidth = this.canvas.getWidth();
        const canvasHeight = this.canvas.getHeight();

        let newLeft = this.startCropArea.left + deltaX;
        let newTop = this.startCropArea.top + deltaY;

        // Constrain to canvas bounds
        newLeft = Math.max(0, Math.min(newLeft, canvasWidth - this.cropArea.width));
        newTop = Math.max(0, Math.min(newTop, canvasHeight - this.cropArea.height));

        this.cropArea.left = newLeft;
        this.cropArea.top = newTop;

        this.updateEditorDimensions();
    }

    /**
     * Resize the crop area based on handle being dragged
     */
    resizeCropArea(deltaX, deltaY) {
        const minSize = 50;
        const canvasWidth = this.canvas.getWidth();
        const canvasHeight = this.canvas.getHeight();
        const start = this.startCropArea;

        // Determine the anchor point based on handle (opposite corner/edge stays fixed)
        let anchorX, anchorY;
        let newLeft, newTop, newWidth, newHeight;

        // Calculate anchor points and new dimensions based on handle
        switch (this.currentHandle) {
            case 'tl':
                // Anchor is bottom-right
                anchorX = start.left + start.width;
                anchorY = start.top + start.height;
                newWidth = start.width - deltaX;
                newHeight = start.height - deltaY;
                break;
            case 'tr':
                // Anchor is bottom-left
                anchorX = start.left;
                anchorY = start.top + start.height;
                newWidth = start.width + deltaX;
                newHeight = start.height - deltaY;
                break;
            case 'bl':
                // Anchor is top-right
                anchorX = start.left + start.width;
                anchorY = start.top;
                newWidth = start.width - deltaX;
                newHeight = start.height + deltaY;
                break;
            case 'br':
                // Anchor is top-left
                anchorX = start.left;
                anchorY = start.top;
                newWidth = start.width + deltaX;
                newHeight = start.height + deltaY;
                break;
            case 'tm':
                // Anchor is bottom edge center
                anchorX = start.left + start.width / 2;
                anchorY = start.top + start.height;
                newWidth = start.width;
                newHeight = start.height - deltaY;
                break;
            case 'bm':
                // Anchor is top edge center
                anchorX = start.left + start.width / 2;
                anchorY = start.top;
                newWidth = start.width;
                newHeight = start.height + deltaY;
                break;
            case 'ml':
                // Anchor is right edge center
                anchorX = start.left + start.width;
                anchorY = start.top + start.height / 2;
                newWidth = start.width - deltaX;
                newHeight = start.height;
                break;
            case 'mr':
                // Anchor is left edge center
                anchorX = start.left;
                anchorY = start.top + start.height / 2;
                newWidth = start.width + deltaX;
                newHeight = start.height;
                break;
        }

        // Ensure minimum size
        newWidth = Math.max(minSize, newWidth);
        newHeight = Math.max(minSize, newHeight);

        // Apply aspect ratio constraint if set
        if (this.aspectRatio) {
            const isCorner = ['tl', 'tr', 'bl', 'br'].includes(this.currentHandle);
            const isHorizontalEdge = ['ml', 'mr'].includes(this.currentHandle);
            const isVerticalEdge = ['tm', 'bm'].includes(this.currentHandle);

            if (isCorner) {
                // For corners, determine primary axis by comparing proportional changes
                const widthChange = Math.abs(newWidth - start.width) / start.width;
                const heightChange = Math.abs(newHeight - start.height) / start.height;

                if (widthChange >= heightChange) {
                    newHeight = newWidth / this.aspectRatio;
                } else {
                    newWidth = newHeight * this.aspectRatio;
                }
            } else if (isHorizontalEdge) {
                newHeight = newWidth / this.aspectRatio;
            } else if (isVerticalEdge) {
                newWidth = newHeight * this.aspectRatio;
            }

            // Re-enforce minimum size with aspect ratio
            if (newWidth < minSize) {
                newWidth = minSize;
                newHeight = newWidth / this.aspectRatio;
            }
            if (newHeight < minSize) {
                newHeight = minSize;
                newWidth = newHeight * this.aspectRatio;
            }
        }

        // Calculate position from anchor and new dimensions
        switch (this.currentHandle) {
            case 'tl':
                newLeft = anchorX - newWidth;
                newTop = anchorY - newHeight;
                break;
            case 'tr':
                newLeft = anchorX;
                newTop = anchorY - newHeight;
                break;
            case 'bl':
                newLeft = anchorX - newWidth;
                newTop = anchorY;
                break;
            case 'br':
                newLeft = anchorX;
                newTop = anchorY;
                break;
            case 'tm':
                newLeft = anchorX - newWidth / 2;
                newTop = anchorY - newHeight;
                break;
            case 'bm':
                newLeft = anchorX - newWidth / 2;
                newTop = anchorY;
                break;
            case 'ml':
                newLeft = anchorX - newWidth;
                newTop = anchorY - newHeight / 2;
                break;
            case 'mr':
                newLeft = anchorX;
                newTop = anchorY - newHeight / 2;
                break;
        }

        // Constrain to canvas bounds while maintaining aspect ratio
        if (this.aspectRatio) {
            // Calculate maximum allowed dimensions based on anchor position and handle type
            let maxWidth, maxHeight;

            switch (this.currentHandle) {
                case 'tl':
                    maxWidth = anchorX;
                    maxHeight = anchorY;
                    break;
                case 'tr':
                    maxWidth = canvasWidth - anchorX;
                    maxHeight = anchorY;
                    break;
                case 'bl':
                    maxWidth = anchorX;
                    maxHeight = canvasHeight - anchorY;
                    break;
                case 'br':
                    maxWidth = canvasWidth - anchorX;
                    maxHeight = canvasHeight - anchorY;
                    break;
                case 'tm':
                    maxWidth = Math.min(anchorX, canvasWidth - anchorX) * 2;
                    maxHeight = anchorY;
                    break;
                case 'bm':
                    maxWidth = Math.min(anchorX, canvasWidth - anchorX) * 2;
                    maxHeight = canvasHeight - anchorY;
                    break;
                case 'ml':
                    maxWidth = anchorX;
                    maxHeight = Math.min(anchorY, canvasHeight - anchorY) * 2;
                    break;
                case 'mr':
                    maxWidth = canvasWidth - anchorX;
                    maxHeight = Math.min(anchorY, canvasHeight - anchorY) * 2;
                    break;
            }

            // Find the maximum size that fits within both bounds while maintaining aspect ratio
            // We need to find the largest rectangle with the given aspect ratio that fits
            // within the maxWidth x maxHeight bounds

            // Option 1: Use maxWidth as the limit
            const widthOption1 = maxWidth;
            const heightOption1 = maxWidth / this.aspectRatio;

            // Option 2: Use maxHeight as the limit
            const heightOption2 = maxHeight;
            const widthOption2 = maxHeight * this.aspectRatio;

            // Choose the option that fits within both constraints
            let maxAllowedWidth, maxAllowedHeight;
            if (heightOption1 <= maxHeight) {
                // Option 1 fits - width is the limiting factor
                maxAllowedWidth = widthOption1;
                maxAllowedHeight = heightOption1;
            } else {
                // Option 2 - height is the limiting factor
                maxAllowedWidth = widthOption2;
                maxAllowedHeight = heightOption2;
            }

            // Always constrain to the maximum allowed dimensions
            // This is the key fix: we must ALWAYS apply the constraint, not just when exceeding
            if (newWidth > maxAllowedWidth) {
                newWidth = maxAllowedWidth;
                newHeight = maxAllowedHeight;
            }
            if (newHeight > maxAllowedHeight) {
                newHeight = maxAllowedHeight;
                newWidth = newHeight * this.aspectRatio;
            }

            // Recalculate position after constraint
            switch (this.currentHandle) {
                case 'tl':
                    newLeft = anchorX - newWidth;
                    newTop = anchorY - newHeight;
                    break;
                case 'tr':
                    newLeft = anchorX;
                    newTop = anchorY - newHeight;
                    break;
                case 'bl':
                    newLeft = anchorX - newWidth;
                    newTop = anchorY;
                    break;
                case 'br':
                    newLeft = anchorX;
                    newTop = anchorY;
                    break;
                case 'tm':
                    newLeft = anchorX - newWidth / 2;
                    newTop = anchorY - newHeight;
                    break;
                case 'bm':
                    newLeft = anchorX - newWidth / 2;
                    newTop = anchorY;
                    break;
                case 'ml':
                    newLeft = anchorX - newWidth;
                    newTop = anchorY - newHeight / 2;
                    break;
                case 'mr':
                    newLeft = anchorX;
                    newTop = anchorY - newHeight / 2;
                    break;
            }
        } else {
            // Non-aspect ratio mode: simple bounds clamping
            newLeft = Math.max(0, Math.min(newLeft, canvasWidth - newWidth));
            newTop = Math.max(0, Math.min(newTop, canvasHeight - newHeight));
            newWidth = Math.min(newWidth, canvasWidth - newLeft);
            newHeight = Math.min(newHeight, canvasHeight - newTop);
        }

        this.cropArea = { left: newLeft, top: newTop, width: newWidth, height: newHeight };
        this.updateEditorDimensions();
    }

    /**
     * Update editor with current crop dimensions (actual output size, not canvas size)
     */
    updateEditorDimensions() {
        // Calculate the scale factor between original image and canvas
        const scale = this.getScaleFactor();

        // Show the actual output dimensions, not the canvas dimensions
        this.editor.cropWidth = Math.round(this.cropArea.width * scale);
        this.editor.cropHeight = Math.round(this.cropArea.height * scale);
    }

    /**
     * Get the scale factor between original image and canvas
     */
    getScaleFactor() {
        if (!this.editor.originalImage || !this.canvas) {
            return 1;
        }

        const origWidth = this.editor.originalImage.width;
        const canvasWidth = this.canvas.getWidth();
        const canvasHeight = this.canvas.getHeight();

        // Account for rotation - if 90/270 rotated, canvas dimensions are swapped
        const rotation = this.editor.currentRotation || 0;
        const absRotation = Math.abs(rotation % 360);
        const isRotated90or270 = absRotation === 90 || absRotation === 270;

        // Get unrotated canvas width to calculate correct scale
        const unrotatedCanvasWidth = isRotated90or270 ? canvasHeight : canvasWidth;

        return origWidth / unrotatedCanvasWidth;
    }

    /**
     * Set aspect ratio constraint
     */
    setAspectRatio(ratio) {
        this.aspectRatio = ratio;

        const canvasWidth = this.canvas.getWidth();
        const canvasHeight = this.canvas.getHeight();

        if (ratio && ratio !== null) {
            // Adjust crop area to match ratio, maximizing to canvas bounds
            this.cropArea = this.calculateMaxCropAreaForRatio(canvasWidth, canvasHeight, ratio);
        } else {
            // Free ratio - expand to full canvas
            this.cropArea = {
                left: 0,
                top: 0,
                width: canvasWidth,
                height: canvasHeight,
            };
        }

        this.updateEditorDimensions();
        this.createCropRect();
    }

    /**
     * Rotate the image by 90-degree increments
     */
    rotate(degrees) {
        if (!this.canvas || !this.editor.backgroundImage) return;

        this.currentRotation = (this.currentRotation + degrees) % 360;

        const bg = this.editor.backgroundImage;
        bg.rotate((bg.angle || 0) + degrees);

        // Swap dimensions if 90 or 270
        if (Math.abs(degrees) === 90 || Math.abs(degrees) === 270) {
            const newWidth = this.canvas.height;
            const newHeight = this.canvas.width;

            this.canvas.setDimensions({ width: newWidth, height: newHeight });

            // Recenter the image
            bg.set({
                left: newWidth / 2,
                top: newHeight / 2,
                originX: 'center',
                originY: 'center',
            });

            // Recalculate crop area for new dimensions, maintaining aspect ratio if set
            if (this.aspectRatio) {
                this.cropArea = this.calculateMaxCropAreaForRatio(newWidth, newHeight, this.aspectRatio);
            } else {
                // Free ratio - full canvas
                this.cropArea = {
                    left: 0,
                    top: 0,
                    width: newWidth,
                    height: newHeight,
                };
            }
        }

        this.canvas.renderAll();
        this.updateEditorDimensions();
        this.createCropRect();
    }

    /**
     * Set fine rotation (-45 to +45 degrees)
     */
    setFineRotation(degrees) {
        this.fineRotation = degrees;

        if (!this.canvas || !this.editor.backgroundImage) return;

        const bg = this.editor.backgroundImage;
        const baseRotation = this.currentRotation;

        bg.rotate(baseRotation + degrees);
        this.canvas.renderAll();
    }

    /**
     * Flip horizontally
     */
    flipHorizontal() {
        if (!this.canvas || !this.editor.backgroundImage) return;

        this.flippedH = !this.flippedH;

        const bg = this.editor.backgroundImage;
        bg.set('flipX', this.flippedH);

        this.canvas.renderAll();
    }

    /**
     * Flip vertically
     */
    flipVertical() {
        if (!this.canvas || !this.editor.backgroundImage) return;

        this.flippedV = !this.flippedV;

        const bg = this.editor.backgroundImage;
        bg.set('flipY', this.flippedV);

        this.canvas.renderAll();
    }

    /**
     * Get the current crop area
     */
    getCropArea() {
        return this.cropArea;
    }

    /**
     * Fit canvas to container - reset zoom to 1 and center viewport
     */
    fitToContainer() {
        if (!this.canvas) return;

        // Reset zoom to 1
        this.canvas.setZoom(1);

        // Reset viewport transform to center
        this.canvas.viewportTransform = [1, 0, 0, 1, 0, 0];

        this.canvas.requestRenderAll();
    }

    /**
     * Set zoom level
     */
    setZoom(level) {
        if (!this.canvas) return;

        // Get canvas center point
        const center = {
            x: this.canvas.getWidth() / 2,
            y: this.canvas.getHeight() / 2,
        };

        // Zoom to center
        this.canvas.zoomToPoint(center, level);
        this.canvas.requestRenderAll();
    }

    /**
     * Update crop area from UI interaction
     */
    updateCropArea(area) {
        this.cropArea = area;
        this.updateEditorDimensions();
        this.createCropRect();
    }
}
