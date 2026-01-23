/**
 * DrawTool - Handles drawing and annotation tools
 */

import * as fabric from 'fabric';

export class DrawTool {
    constructor(editor) {
        this.editor = editor;

        // Current tool state
        this.currentTool = 'select';
        this.isDrawing = false;
        this.startPoint = null;
        this.currentShape = null;

        // Drawing settings (synced from editor)
        this.strokeColor = editor.strokeColor || '#000000';
        this.strokeWidth = editor.strokeWidth || 4;
        this.fillColor = editor.fillColor || 'transparent';
        this.fontSize = 24;
        this.fontFamily = 'Arial';
        this.fontBold = false;
        this.fontItalic = false;

        // Bound event handlers (store references for proper removal)
        this._boundMouseDown = this.handleMouseDown.bind(this);
        this._boundMouseMove = this.handleMouseMove.bind(this);
        this._boundMouseUp = this.handleMouseUp.bind(this);
        this._boundPathCreated = this.handlePathCreated.bind(this);
        this._boundMouseDblClick = this.handleMouseDblClick.bind(this);
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
     * Get the raw (unwrapped) canvas for operations that need it
     * This bypasses Alpine's proxy which can break Fabric.js controls
     * See: https://github.com/fabricjs/fabric.js/issues/7485
     */
    getRawCanvas() {
        const canvas = this.editor.canvas;
        if (canvas && typeof Alpine !== 'undefined' && Alpine.raw) {
            return Alpine.raw(canvas);
        }
        return canvas;
    }

    /**
     * Activate the draw tool
     */
    activate() {
        if (!this.canvas) return;

        // Enable selection by default
        this.canvas.selection = true;
        this.canvas.forEachObject((obj) => {
            if (obj !== this.canvas.backgroundImage) {
                obj.selectable = true;
                obj.evented = true;
            }
        });

        // Setup event listeners
        this.setupEventListeners();

        // Set to select tool by default
        this.selectTool('select');
    }

    /**
     * Deactivate the draw tool
     */
    deactivate() {
        if (!this.canvas) return;

        this.canvas.isDrawingMode = false;
        this.removeEventListeners();
    }

    /**
     * Setup canvas event listeners
     */
    setupEventListeners() {
        this.canvas.on('mouse:down', this._boundMouseDown);
        this.canvas.on('mouse:move', this._boundMouseMove);
        this.canvas.on('mouse:up', this._boundMouseUp);
        this.canvas.on('path:created', this._boundPathCreated);
        this.canvas.on('mouse:dblclick', this._boundMouseDblClick);
    }

    /**
     * Remove event listeners
     */
    removeEventListeners() {
        this.canvas.off('mouse:down', this._boundMouseDown);
        this.canvas.off('mouse:move', this._boundMouseMove);
        this.canvas.off('mouse:up', this._boundMouseUp);
        this.canvas.off('path:created', this._boundPathCreated);
        this.canvas.off('mouse:dblclick', this._boundMouseDblClick);
    }

    /**
     * Handle path:created event (for freehand drawing)
     * This is fired when freeDrawingMode completes a stroke
     */
    handlePathCreated(e) {
        const path = e.path;

        // Mark eraser paths for special export handling
        if (this.currentTool === 'eraser' && path) {
            path._isEraserPath = true;
            // Use a visually distinct color so user can see what they're erasing
            // The actual erasing happens on export via globalCompositeOperation
            path.set({
                stroke: 'rgba(255, 100, 100, 0.5)', // Semi-transparent red for visibility
                _isEraserPath: true,
            });
            this.canvas.renderAll();
        }

        // Mark as having unsaved changes and save to history
        this.editor.hasUnsavedChanges = true;
        this.editor.saveToHistory();
    }

    /**
     * Select a drawing tool
     */
    selectTool(tool) {
        this.currentTool = tool;

        // Update canvas mode based on tool
        switch (tool) {
            case 'select':
                this.canvas.isDrawingMode = false;
                this.canvas.selection = true;
                // Re-enable selectability and controls on all objects when switching to select tool
                this.canvas.forEachObject((obj) => {
                    if (obj !== this.canvas.backgroundImage && !obj._isCropElement) {
                        // Apply all common options including control styling
                        const options = this.getCommonObjectOptions();
                        Object.keys(options).forEach(key => {
                            obj[key] = options[key];
                        });
                        // Update object coordinates to ensure controls are properly positioned
                        obj.setCoords();
                    }
                });
                this.canvas.requestRenderAll();
                break;

            case 'freehand':
                this.canvas.isDrawingMode = true;
                this.canvas.selection = false;
                this.setupFreehandBrush();
                break;

            case 'eraser':
                this.canvas.isDrawingMode = true;
                this.canvas.selection = false;
                this.setupEraserBrush();
                break;

            case 'text':
                // Text tool needs selection enabled so users can select/move existing text
                // and double-click to edit. New text is created on click in empty space.
                this.canvas.isDrawingMode = false;
                this.canvas.selection = true;
                // Enable selectability on text objects specifically
                this.canvas.forEachObject((obj) => {
                    if (obj !== this.canvas.backgroundImage && !obj._isCropElement) {
                        obj.selectable = true;
                        obj.evented = true;
                    }
                });
                this.canvas.renderAll();
                break;

            default:
                this.canvas.isDrawingMode = false;
                this.canvas.selection = false;
                break;
        }

        // Deselect any selected objects when switching tools (except select and text)
        if (tool !== 'select' && tool !== 'text') {
            this.canvas.discardActiveObject();
            this.canvas.renderAll();
        }
    }

    /**
     * Setup freehand drawing brush
     */
    setupFreehandBrush() {
        this.canvas.freeDrawingBrush = new fabric.PencilBrush(this.canvas);
        this.syncBrushSettings();
    }

    /**
     * Sync brush settings from editor state
     * Called when brush is set up or when settings change
     */
    syncBrushSettings() {
        if (!this.canvas.freeDrawingBrush) return;

        if (this.currentTool === 'eraser') {
            // Eraser uses destination-out composite operation for true erasing
            this.canvas.freeDrawingBrush.color = 'rgba(255,255,255,1)';
            this.canvas.freeDrawingBrush.width = this.editor.eraserSize || 20;
        } else {
            this.canvas.freeDrawingBrush.color = this.editor.strokeColor;
            this.canvas.freeDrawingBrush.width = this.editor.strokeWidth;
        }
    }

    /**
     * Setup eraser brush
     * Note: True erasing requires special handling - for now we mark eraser paths
     * so they can be rendered with globalCompositeOperation = 'destination-out' on export
     */
    setupEraserBrush() {
        this.canvas.freeDrawingBrush = new fabric.PencilBrush(this.canvas);
        this.canvas.freeDrawingBrush.color = 'rgba(0,0,0,1)';
        this.canvas.freeDrawingBrush.width = this.editor.eraserSize || 20;

        // Mark paths created by eraser for special handling
        // The path:created handler will tag these
    }

    /**
     * Update brush when editor settings change
     * This should be called from the ImageEditor when strokeColor/strokeWidth change
     */
    updateBrushFromEditor() {
        this.syncBrushSettings();
    }

    /**
     * Handle mouse down
     */
    handleMouseDown(event) {
        if (this.canvas.isDrawingMode) return;
        if (this.currentTool === 'select') return;

        const pointer = this.canvas.getPointer(event.e);
        const target = event.target;

        // Special handling for text tool
        if (this.currentTool === 'text') {
            // If clicking on an existing text object, just select it (don't create new)
            if (target && this.isTextObject(target)) {
                this.canvas.setActiveObject(target);
                this.canvas.renderAll();
                return;
            }

            // If clicking on empty space, create new text
            if (!target || target === this.canvas.backgroundImage) {
                this.createTextbox(pointer);
            }
            return;
        }

        // For shape tools (line, arrow, rectangle, ellipse):
        // Only start drawing if clicking on empty space or the background
        // If clicking on an existing object, don't start drawing (let Fabric handle selection if applicable)
        if (target && target !== this.canvas.backgroundImage && !target._isCropElement) {
            // Clicked on an existing object - don't start drawing
            return;
        }

        this.startPoint = pointer;
        this.isDrawing = true;

        // Create shape based on tool
        switch (this.currentTool) {
            case 'line':
                this.currentShape = this.createLine(pointer);
                break;
            case 'arrow':
                this.currentShape = this.createLine(pointer);
                break;
            case 'rectangle':
                this.currentShape = this.createRectangle(pointer);
                break;
            case 'ellipse':
                this.currentShape = this.createEllipse(pointer);
                break;
        }

        if (this.currentShape) {
            // Use raw canvas to add objects - this prevents Alpine's proxy from
            // breaking Fabric.js controls (resize, rotate handles)
            this.getRawCanvas().add(this.currentShape);
        }
    }

    /**
     * Handle mouse double-click (for entering text edit mode)
     * Note: Fabric.js IText has built-in double-click editing, but we need to ensure
     * it works even when using tools other than 'select'
     */
    handleMouseDblClick(event) {
        const target = event.target;

        // If double-clicking on a text object, enter edit mode
        if (target && this.isTextObject(target)) {
            // Make sure the object is selected first
            if (this.canvas.getActiveObject() !== target) {
                this.canvas.setActiveObject(target);
            }

            // Enter editing mode if not already editing
            if (!target.isEditing) {
                target.enterEditing();
                target.selectAll();

                // Notify editor that we're editing text (disables focus trap)
                // The canvas event should fire, but set it directly for safety
                this.editor.isEditingText = true;
            }

            // Ensure the hidden textarea has focus for keyboard input
            // Use setTimeout to ensure Alpine has processed the isEditingText change
            setTimeout(() => {
                if (target.hiddenTextarea) {
                    target.hiddenTextarea.focus();
                }
            }, 50);

            this.canvas.renderAll();
        }
    }

    /**
     * Check if an object is a text object
     */
    isTextObject(obj) {
        return obj && ['i-text', 'text', 'textbox'].includes(obj.type);
    }

    /**
     * Handle mouse move
     */
    handleMouseMove(event) {
        if (!this.isDrawing || !this.currentShape) return;

        const pointer = this.canvas.getPointer(event.e);
        const shiftKey = event.e.shiftKey;

        switch (this.currentTool) {
            case 'line':
            case 'arrow':
                this.updateLine(pointer, shiftKey);
                break;
            case 'rectangle':
                this.updateRectangle(pointer, shiftKey);
                break;
            case 'ellipse':
                this.updateEllipse(pointer, shiftKey);
                break;
        }

        this.canvas.renderAll();
    }

    /**
     * Handle mouse up
     */
    handleMouseUp(event) {
        if (!this.isDrawing) return;

        this.isDrawing = false;

        // For arrow, add arrowhead
        if (this.currentTool === 'arrow' && this.currentShape) {
            this.addArrowhead(this.currentShape);
        }

        // Ensure the shape has proper controls set up
        // In Fabric.js 6.x, we need to call setCoords() after modifying object dimensions
        if (this.currentShape) {
            this.currentShape.setCoords();
            this.canvas.renderAll();
        }

        // Notify editor of changes
        this.editor.hasUnsavedChanges = true;
        this.editor.saveToHistory();

        this.currentShape = null;
        this.startPoint = null;
    }

    /**
     * Get common object options for controls and interactivity
     */
    getCommonObjectOptions() {
        return {
            selectable: true,
            evented: true,
            hasControls: true,
            hasBorders: true,
            // Explicitly unlock all transformations
            lockMovementX: false,
            lockMovementY: false,
            lockScalingX: false,
            lockScalingY: false,
            lockRotation: false,
            lockSkewingX: false,
            lockSkewingY: false,
            // Control styling for better visibility and interaction
            cornerSize: 12,
            cornerColor: 'rgb(59, 130, 246)',
            cornerStrokeColor: 'white',
            cornerStyle: 'circle',
            transparentCorners: false,
            borderColor: 'rgb(59, 130, 246)',
            borderScaleFactor: 2,
            padding: 0,
        };
    }

    /**
     * Create a line
     */
    createLine(point) {
        return new fabric.Line([point.x, point.y, point.x, point.y], {
            stroke: this.editor.strokeColor,
            strokeWidth: this.editor.strokeWidth,
            ...this.getCommonObjectOptions(),
        });
    }

    /**
     * Update line during drag
     * When shift is held, constrain to 45-degree angles
     */
    updateLine(point, shiftKey = false) {
        let x2 = point.x;
        let y2 = point.y;

        if (shiftKey) {
            const dx = point.x - this.startPoint.x;
            const dy = point.y - this.startPoint.y;
            const angle = Math.atan2(dy, dx);
            const length = Math.sqrt(dx * dx + dy * dy);

            // Snap to nearest 45-degree angle (0, 45, 90, 135, 180, etc.)
            const snapAngle = Math.round(angle / (Math.PI / 4)) * (Math.PI / 4);

            x2 = this.startPoint.x + Math.cos(snapAngle) * length;
            y2 = this.startPoint.y + Math.sin(snapAngle) * length;
        }

        this.currentShape.set({
            x2: x2,
            y2: y2,
        });
    }

    /**
     * Add arrowhead to line
     */
    addArrowhead(line) {
        const strokeWidth = this.editor.strokeWidth;
        const angle = Math.atan2(line.y2 - line.y1, line.x2 - line.x1);

        // Scale arrow head size based on stroke width
        // Base size of 12, scaling up with stroke width
        const headLength = Math.max(12, strokeWidth * 3);
        const headWidth = Math.max(10, strokeWidth * 2.5);

        // The tip of the arrow should be at the original line end point
        const tipX = line.x2;
        const tipY = line.y2;

        // Fabric.js Triangle has its "point" at the top when angle=0
        // The triangle is drawn with point at top, base at bottom
        // When we rotate by (angle + 90deg), the point faces the line direction
        // With originY: 'top', the position is at the tip of the triangle

        const triangle = new fabric.Triangle({
            left: tipX,
            top: tipY,
            width: headWidth,
            height: headLength,
            fill: this.editor.strokeColor,
            angle: (angle * 180 / Math.PI) + 90,
            originX: 'center',
            originY: 'top', // Origin at the tip (top of unrotated triangle)
            selectable: false,
            evented: false,
        });

        // Shorten the line so it ends at the base of the arrow head (not the tip)
        // The line should connect to where the triangle base is
        const shortenAmount = headLength;
        const newX2 = line.x2 - Math.cos(angle) * shortenAmount;
        const newY2 = line.y2 - Math.sin(angle) * shortenAmount;
        line.set({ x2: newX2, y2: newY2 });

        // Group line and arrowhead
        const rawCanvas = this.getRawCanvas();
        rawCanvas.remove(line);

        const group = new fabric.Group([line, triangle], {
            ...this.getCommonObjectOptions(),
        });

        // Use raw canvas to add the group - prevents Alpine proxy issues
        rawCanvas.add(group);
        group.setCoords();
    }

    /**
     * Create a rectangle
     */
    createRectangle(point) {
        // Use 'transparent' instead of null so the shape is clickable in its interior
        const fill = this.editor.fillColor === 'transparent' ? 'transparent' : this.editor.fillColor;

        return new fabric.Rect({
            left: point.x,
            top: point.y,
            width: 0,
            height: 0,
            stroke: this.editor.strokeColor,
            strokeWidth: this.editor.strokeWidth,
            fill: fill,
            ...this.getCommonObjectOptions(),
        });
    }

    /**
     * Update rectangle during drag
     * When shift is held, constrain to a perfect square
     */
    updateRectangle(point, shiftKey = false) {
        let width = point.x - this.startPoint.x;
        let height = point.y - this.startPoint.y;

        if (shiftKey) {
            // Constrain to square - use the larger dimension
            const size = Math.max(Math.abs(width), Math.abs(height));
            width = width < 0 ? -size : size;
            height = height < 0 ? -size : size;
        }

        this.currentShape.set({
            width: Math.abs(width),
            height: Math.abs(height),
            left: width < 0 ? this.startPoint.x + width : this.startPoint.x,
            top: height < 0 ? this.startPoint.y + height : this.startPoint.y,
        });
    }

    /**
     * Create an ellipse
     */
    createEllipse(point) {
        // Use 'transparent' instead of null so the shape is clickable in its interior
        const fill = this.editor.fillColor === 'transparent' ? 'transparent' : this.editor.fillColor;

        return new fabric.Ellipse({
            left: point.x,
            top: point.y,
            rx: 0,
            ry: 0,
            stroke: this.editor.strokeColor,
            strokeWidth: this.editor.strokeWidth,
            fill: fill,
            ...this.getCommonObjectOptions(),
        });
    }

    /**
     * Update ellipse during drag
     * When shift is held, constrain to a perfect circle
     */
    updateEllipse(point, shiftKey = false) {
        let width = point.x - this.startPoint.x;
        let height = point.y - this.startPoint.y;

        if (shiftKey) {
            // Constrain to circle - use the larger dimension
            const size = Math.max(Math.abs(width), Math.abs(height));
            width = width < 0 ? -size : size;
            height = height < 0 ? -size : size;
        }

        const rx = Math.abs(width) / 2;
        const ry = Math.abs(height) / 2;

        this.currentShape.set({
            rx: rx,
            ry: ry,
            left: width < 0 ? this.startPoint.x + width : this.startPoint.x,
            top: height < 0 ? this.startPoint.y + height : this.startPoint.y,
        });
    }

    /**
     * Create a textbox
     */
    createTextbox(point) {
        const fontWeight = this.editor.textBold ? 'bold' : 'normal';
        const fontStyle = this.editor.textItalic ? 'italic' : 'normal';

        const text = new fabric.IText('Text', {
            left: point.x,
            top: point.y,
            fontFamily: this.editor.textFont,
            fontSize: this.editor.textSize,
            fontWeight: fontWeight,
            fontStyle: fontStyle,
            fill: this.editor.textColor,
            ...this.getCommonObjectOptions(),
        });

        // Use raw canvas to add text - prevents Alpine proxy issues with controls
        this.getRawCanvas().add(text);
        this.canvas.setActiveObject(text);
        text.enterEditing();
        text.selectAll();

        // Notify editor that we're editing text (disables focus trap)
        this.editor.isEditingText = true;

        // Ensure the hidden textarea has focus for keyboard input
        // Use setTimeout to ensure Alpine has processed the isEditingText change
        setTimeout(() => {
            if (text.hiddenTextarea) {
                text.hiddenTextarea.focus();
            }
        }, 50);

        this.canvas.renderAll();

        // Notify editor
        this.editor.hasUnsavedChanges = true;
    }

    /**
     * Bring selected object to front
     * Note: Fabric.js 6.x uses canvas.bringObjectToFront() instead of object.bringToFront()
     */
    bringToFront() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            this.canvas.bringObjectToFront(activeObject);
            this.canvas.renderAll();
        }
    }

    /**
     * Send selected object to back
     * Note: Fabric.js 6.x uses canvas.sendObjectToBack() instead of object.sendToBack()
     */
    sendToBack() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            this.canvas.sendObjectToBack(activeObject);
            this.canvas.renderAll();
        }
    }

    /**
     * Duplicate selected object
     * Note: Fabric.js 6.x clone() returns a Promise instead of using callback
     */
    async duplicate() {
        const activeObject = this.canvas.getActiveObject();
        if (!activeObject) return;

        try {
            const cloned = await activeObject.clone();
            cloned.set({
                left: cloned.left + 20,
                top: cloned.top + 20,
                evented: true,
            });

            // Use raw canvas to add cloned object - prevents Alpine proxy issues
            this.getRawCanvas().add(cloned);
            this.canvas.setActiveObject(cloned);
            this.canvas.renderAll();
        } catch (error) {
            console.error('Error duplicating object:', error);
        }
    }

    /**
     * Delete selected object
     */
    deleteSelected() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            this.canvas.remove(activeObject);
            this.canvas.renderAll();
        }
    }

    /**
     * Update stroke color
     */
    setStrokeColor(color) {
        this.strokeColor = color;

        if (this.canvas.isDrawingMode && this.canvas.freeDrawingBrush) {
            this.canvas.freeDrawingBrush.color = color;
        }

        // Update selected object if any
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            activeObject.set('stroke', color);
            this.canvas.renderAll();
        }
    }

    /**
     * Update stroke width
     */
    setStrokeWidth(width) {
        this.strokeWidth = width;

        if (this.canvas.isDrawingMode && this.canvas.freeDrawingBrush) {
            this.canvas.freeDrawingBrush.width = width;
        }

        // Update selected object if any
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            activeObject.set('strokeWidth', width);
            this.canvas.renderAll();
        }
    }

    /**
     * Update fill color
     */
    setFillColor(color) {
        this.fillColor = color === 'transparent' ? null : color;

        // Update selected object if any
        const activeObject = this.canvas.getActiveObject();
        if (activeObject && activeObject.type !== 'path') {
            activeObject.set('fill', this.fillColor);
            this.canvas.renderAll();
        }
    }
}
