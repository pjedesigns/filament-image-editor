/**
 * ImageEditor - Main Alpine.js Component
 *
 * This is the core image editor component that manages state and coordinates
 * between the UI and Fabric.js canvas.
 */

import * as fabric from 'fabric';
import { HistoryManager } from './utils/history.js';
import { exportCanvas } from './utils/export.js';
import { CropTool } from './tools/CropTool.js';
import { FilterTool } from './tools/FilterTool.js';
import { DrawTool } from './tools/DrawTool.js';

export function ImageEditor({ state, statePath, config, imageUrl, originalImageUrl }) {
    return {
        // State management
        state: state,
        statePath: statePath,
        config: config,

        // UI State
        isLoading: false,
        isEditorOpen: false,
        isCanvasLoading: false,
        hasImage: false,
        previewUrl: imageUrl || null,
        thumbnailUrl: null,
        // Original image URL for full-resolution editing (falls back to imageUrl if not provided)
        originalImageUrl: originalImageUrl || imageUrl || null,
        isDragging: false,
        hasUnsavedChanges: false,

        // Original state (for cancel/restore functionality)
        _originalHasImage: false,
        _originalPreviewUrl: null,
        _originalState: null,
        _originalStateSaved: false,

        // Multi-image mode
        isMultiImage: false,
        currentImageIndex: 0,
        totalImages: 1,
        imageQueue: [],

        // Tool State
        availableTools: [],
        activeTool: 'crop',

        // History
        historyManager: null,
        canUndo: false,
        canRedo: false,

        // Crop State
        currentAspectRatio: 'free',
        cropWidth: 0,
        cropHeight: 0,
        currentRotation: 0,  // 90-degree increments
        fineRotation: 0,     // Fine rotation slider (-45 to +45)
        isFlippedH: false,
        isFlippedV: false,
        cropArea: null,

        // Filter State
        currentFilter: 'original',
        adjustments: {
            brightness: 0,
            contrast: 0,
            saturation: 0,
            exposure: 0,
            warmth: 0,
        },

        // Draw State
        currentDrawingTool: 'select',
        strokeColor: '#000000',
        strokeWidth: 4,
        fillColor: 'transparent',
        textFont: 'Arial',
        textSize: 24,
        textColor: '#000000',
        textBold: false,
        textItalic: false,
        eraserSize: 20,
        hasSelection: false,
        selectedObjectType: null, // Track type of selected object for UI
        isEditingText: false, // Track if IText is being edited (disables focus trap)

        // Zoom State
        zoomLevel: 'fit',

        // Dialog State
        showDiscardDialog: false,
        showResetDialog: false,

        // Fabric.js
        // NOTE: Fabric.js objects are stored directly on the DOM element to avoid Alpine proxy issues
        // This is necessary because Alpine's proxy wrapper breaks Fabric.js controls and image references
        // See: https://github.com/fabricjs/fabric.js/issues/7485
        //
        // We use _fallback properties as a backup in case $el is not available when setting values

        // Fallback storage for when $el is not yet available
        _canvasFallback: null,
        _originalImageFallback: null,
        _backgroundImageFallback: null,

        // Canvas getter - returns the raw canvas from DOM element (outside Alpine's proxy)
        get canvas() {
            if (this.$el?._fabricCanvas) {
                return this.$el._fabricCanvas;
            }
            return this._canvasFallback;
        },
        set canvas(value) {
            this._canvasFallback = value;
            if (this.$el) {
                this.$el._fabricCanvas = value;
            }
        },

        // Original image getter - stored on DOM element to avoid proxy issues
        get originalImage() {
            if (this.$el?._fabricOriginalImage) {
                return this.$el._fabricOriginalImage;
            }
            return this._originalImageFallback;
        },
        set originalImage(value) {
            this._originalImageFallback = value;
            if (this.$el) {
                this.$el._fabricOriginalImage = value;
            }
        },

        // Background image getter - stored on DOM element to avoid proxy issues
        get backgroundImage() {
            if (this.$el?._fabricBackgroundImage) {
                return this.$el._fabricBackgroundImage;
            }
            return this._backgroundImageFallback;
        },
        set backgroundImage(value) {
            this._backgroundImageFallback = value;
            if (this.$el) {
                this.$el._fabricBackgroundImage = value;
            }
        },

        // Tool instances
        cropTool: null,
        filterTool: null,
        drawTool: null,

        /**
         * Initialize the component
         */
        init() {
            // Set available tools from config
            this.availableTools = this.config.tools || ['crop', 'filter', 'draw'];

            // Check if we already have an image
            if (this.previewUrl) {
                this.hasImage = true;
                // Set thumbnailUrl for filter previews when editing existing images
                this.thumbnailUrl = this.previewUrl;
            }

            // Initialize defaults from config
            this.currentAspectRatio = this.config.crop?.defaultAspectRatio || 'free';
            this.strokeColor = this.config.draw?.defaultStrokeColor || '#000000';
            this.strokeWidth = this.config.draw?.defaultStrokeWidth || 4;
            this.fillColor = this.config.draw?.defaultFillColor || 'transparent';

            // Initialize history manager
            this.historyManager = new HistoryManager(this.config.history?.limit || 50);

            // Setup keyboard shortcuts if enabled
            if (this.config.history?.keyboardShortcuts !== false) {
                this.setupKeyboardShortcuts();
            }
        },

        /**
         * Handle file selection from input
         */
        async handleFileSelect(event) {
            const files = event.target.files;
            if (!files || files.length === 0) return;

            await this.processFiles(Array.from(files));

            // Reset input
            event.target.value = '';
        },

        /**
         * Handle file drop
         */
        async handleDrop(event) {
            this.isDragging = false;

            const files = event.dataTransfer?.files;
            if (!files || files.length === 0) return;

            await this.processFiles(Array.from(files));
        },

        /**
         * Process uploaded files
         */
        async processFiles(files) {
            // Filter valid image files
            const validFiles = files.filter(file => {
                // Check type
                if (!this.config.validation?.acceptedTypes?.includes(file.type)) {
                    this.showError(this.getTranslation('validation.invalid_type'));
                    return false;
                }

                // Check size
                if (file.size > (this.config.validation?.maxFileSize || Infinity)) {
                    const maxSize = this.formatFileSize(this.config.validation.maxFileSize);
                    this.showError(this.getTranslation('validation.file_too_large', { max: maxSize }));
                    return false;
                }

                return true;
            });

            if (validFiles.length === 0) return;

            // Store original state BEFORE loading new image (for cancel/restore)
            this._originalHasImage = this.hasImage;
            this._originalPreviewUrl = this.previewUrl;
            this._originalState = this.state;
            this._originalStateSaved = true;

            // Handle single or multiple files
            if (validFiles.length === 1) {
                this.isMultiImage = false;
                this.totalImages = 1;
                this.currentImageIndex = 0;
                await this.loadImage(validFiles[0]);
            } else {
                this.isMultiImage = true;
                this.imageQueue = validFiles;
                this.totalImages = validFiles.length;
                this.currentImageIndex = 0;
                await this.loadImage(validFiles[0]);
            }

            // Open editor if configured, otherwise auto-save the uploaded file
            if (this.config.openOnSelect !== false) {
                this.openEditor();
            } else {
                // When openOnSelect is false, auto-save the file directly without opening editor
                // This uploads the file to state so it persists
                await this.uploadFile(validFiles[0]);
            }
        },

        /**
         * Load an image file into the editor
         */
        async loadImage(file) {
            this.isLoading = true;

            try {
                // Create object URL for preview
                const url = URL.createObjectURL(file);
                this.previewUrl = url;
                this.thumbnailUrl = url;
                // For new uploads, the original URL is the same as the preview URL
                this.originalImageUrl = url;
                this.hasImage = true;

                // Store original file reference
                this.originalFile = file;

                // Check dimensions warning
                await this.checkDimensions(file);
            } catch (error) {
                console.error('Error loading image:', error);
                this.showError('Failed to load image');
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Check if image meets minimum dimension requirements
         */
        async checkDimensions(file) {
            return new Promise((resolve) => {
                const img = new Image();
                img.onload = () => {
                    const minWidth = this.config.validation?.minWidth;
                    const minHeight = this.config.validation?.minHeight;

                    if (minWidth && minHeight && (img.width < minWidth || img.height < minHeight)) {
                        this.showWarning(
                            this.getTranslation('validation.min_dimensions', {
                                width: minWidth,
                                height: minHeight,
                            })
                        );
                    }
                    resolve();
                };
                img.src = URL.createObjectURL(file);
            });
        },

        /**
         * Open the editor modal
         */
        async openEditor() {
            // Store original state before editing (for cancel/restore)
            // Only save if not already saved by processFiles (for new uploads)
            if (!this._originalStateSaved) {
                this._originalHasImage = this.hasImage;
                this._originalPreviewUrl = this.previewUrl;
                this._originalState = this.state;
            }

            this.isEditorOpen = true;
            this.isCanvasLoading = true;

            // Reset all editor state to defaults
            this.resetEditorState();

            // Wait for DOM to update
            await this.$nextTick();

            // Initialize Fabric canvas
            await this.initializeCanvas();

            // Initialize tools
            this.initializeTools();

            // Select first available tool
            this.selectTool(this.availableTools[0]);

            this.isCanvasLoading = false;
        },

        /**
         * Reset all editor state to defaults
         */
        resetEditorState() {
            // Reset unsaved changes flag
            this.hasUnsavedChanges = false;

            // Reset crop state
            this.currentAspectRatio = this.config.crop?.defaultAspectRatio || 'free';
            this.cropWidth = 0;
            this.cropHeight = 0;
            this.currentRotation = 0;
            this.fineRotation = 0;
            this.isFlippedH = false;
            this.isFlippedV = false;
            this.cropArea = null;

            // Reset filter state
            this.currentFilter = 'original';
            this.adjustments = {
                brightness: 0,
                contrast: 0,
                saturation: 0,
                exposure: 0,
                warmth: 0,
            };

            // Reset draw state
            this.currentDrawingTool = 'select';
            this.strokeColor = this.config.draw?.defaultStrokeColor || '#000000';
            this.strokeWidth = this.config.draw?.defaultStrokeWidth || 4;
            this.fillColor = this.config.draw?.defaultFillColor || 'transparent';
            this.textFont = 'Arial';
            this.textSize = 24;
            this.textColor = '#000000';
            this.textBold = false;
            this.textItalic = false;
            this.eraserSize = 20;
            this.hasSelection = false;

            // Reset zoom state
            this.zoomLevel = 'fit';

            // Reset history
            if (this.historyManager) {
                this.historyManager.clear();
            }
            this.canUndo = false;
            this.canRedo = false;

            // Reset dialogs
            this.showDiscardDialog = false;
            this.showResetDialog = false;

            // Reset active tool to first available
            this.activeTool = this.availableTools[0] || 'crop';
        },

        /**
         * Initialize Fabric.js canvas
         */
        async initializeCanvas() {
            const canvasEl = this.$refs.fabricCanvas;
            const container = this.$refs.canvasContainer;

            if (!canvasEl || !container) return;

            // Get container dimensions
            const containerRect = container.parentElement.getBoundingClientRect();
            const maxWidth = containerRect.width - 40;
            const maxHeight = containerRect.height - 40;

            // Load original image at full resolution
            // Use originalImageUrl if available (for existing images from server)
            // This ensures we always export at the original resolution, not a downscaled conversion
            const imageUrlToLoad = this.originalImageUrl || this.previewUrl;
            const img = await this.loadFabricImage(imageUrlToLoad);
            this.originalImage = img;

            // Calculate canvas size to fit
            const scale = Math.min(
                maxWidth / img.width,
                maxHeight / img.height,
                1
            );

            const canvasWidth = img.width * scale;
            const canvasHeight = img.height * scale;

            // Create or update canvas
            if (this.canvas) {
                this.canvas.dispose();
            }

            // IMPORTANT: Store canvas in a non-reactive property to prevent Alpine's proxy
            // from wrapping it. This fixes a known issue where Fabric.js controls don't work
            // when the canvas is wrapped in Alpine's reactivity proxy.
            // See: https://github.com/fabricjs/fabric.js/issues/7485
            //
            // The canvas getter/setter uses Object.defineProperty to create a non-enumerable
            // property that Alpine won't proxy.
            this.canvas = new fabric.Canvas(canvasEl, {
                width: canvasWidth,
                height: canvasHeight,
                backgroundColor: 'transparent',
                preserveObjectStacking: true,
                // Ensure objects can be selected even with transparent fill
                // By default, perPixelTargetFind is false, which means clicking anywhere
                // in the bounding box selects the object (good for shapes with transparent fill)
                perPixelTargetFind: false,
                // Enable interactive controls
                controlsAboveOverlay: true,
                // Allow uniform scaling (shift key constraint)
                uniformScaling: true,
                // Center transforms when scaling
                centeredScaling: false,
                centeredRotation: false,
                // Tolerance for finding targets (controls) - helps with control hit detection
                targetFindTolerance: 5,
            });

            // Set background image
            // In Fabric.js 7.x, originX/originY default to 'center', so we must explicitly set 'left'/'top'
            // We must set img.canvas = canvas for proper rendering
            this.backgroundImage = new fabric.FabricImage(img.getElement(), {
                scaleX: scale,
                scaleY: scale,
                left: 0,
                top: 0,
                originX: 'left',
                originY: 'top',
                selectable: false,
                evented: false,
            });

            // Fabric.js 6.x requires setting the canvas reference and calling requestRenderAll
            this.canvas.backgroundImage = this.backgroundImage;
            this.backgroundImage.canvas = this.canvas;
            this.canvas.requestRenderAll();

            // Initialize crop dimensions
            this.cropWidth = Math.round(img.width);
            this.cropHeight = Math.round(img.height);

            // Save initial state to history
            this.saveToHistory();

            // Setup canvas event listeners
            this.setupCanvasEvents();
        },

        /**
         * Load image into Fabric.js format
         * Fabric.js 6.x uses Promises instead of callbacks
         */
        async loadFabricImage(url) {
            try {
                const img = await fabric.Image.fromURL(url, { crossOrigin: 'anonymous' });
                if (!img) {
                    throw new Error('Failed to load image');
                }
                return img;
            } catch (error) {
                console.error('Error loading fabric image:', error);
                throw error;
            }
        },

        /**
         * Initialize tool instances
         */
        initializeTools() {
            this.cropTool = new CropTool(this);
            this.filterTool = new FilterTool(this);
            this.drawTool = new DrawTool(this);
        },

        /**
         * Setup canvas event listeners
         */
        setupCanvasEvents() {
            if (!this.canvas) return;

            this.canvas.on('selection:created', (e) => {
                this.hasSelection = true;
                this.updateSelectedObjectInfo(e.selected?.[0]);
            });

            this.canvas.on('selection:updated', (e) => {
                this.updateSelectedObjectInfo(e.selected?.[0]);
            });

            this.canvas.on('selection:cleared', () => {
                this.hasSelection = false;
                this.selectedObjectType = null;
            });

            this.canvas.on('object:modified', () => {
                this.hasUnsavedChanges = true;
                this.saveToHistory();
            });

            // Track when IText enters/exits editing mode
            // This disables the Alpine focus trap so keyboard input works
            this.canvas.on('text:editing:entered', () => {
                this.isEditingText = true;
            });

            this.canvas.on('text:editing:exited', () => {
                this.isEditingText = false;
                this.hasUnsavedChanges = true;
                this.saveToHistory();
            });

            // Setup panning for zoomed canvas
            this.setupPanning();
        },

        /**
         * Setup canvas panning when zoomed
         */
        setupPanning() {
            let isPanning = false;
            let lastPosX = 0;
            let lastPosY = 0;

            const canvasEl = this.canvas.upperCanvasEl;

            // Use native DOM events for more reliable middle mouse button detection
            if (canvasEl) {
                // Prevent default middle mouse button behavior (auto-scroll)
                canvasEl.addEventListener('mousedown', (evt) => {
                    // Middle mouse button (button === 1) or Alt+Left click
                    if (evt.button === 1 || (evt.altKey && evt.button === 0)) {
                        evt.preventDefault();
                        evt.stopPropagation();
                        isPanning = true;
                        this.canvas.selection = false;
                        lastPosX = evt.clientX;
                        lastPosY = evt.clientY;
                        canvasEl.style.cursor = 'grabbing';
                    }
                });

                canvasEl.addEventListener('mousemove', (evt) => {
                    if (isPanning) {
                        evt.preventDefault();
                        const vpt = this.canvas.viewportTransform;
                        vpt[4] += evt.clientX - lastPosX;
                        vpt[5] += evt.clientY - lastPosY;
                        this.canvas.requestRenderAll();
                        lastPosX = evt.clientX;
                        lastPosY = evt.clientY;
                    }
                });

                canvasEl.addEventListener('mouseup', (evt) => {
                    if (isPanning) {
                        evt.preventDefault();
                        isPanning = false;
                        this.canvas.selection = true;
                        canvasEl.style.cursor = 'default';
                    }
                });

                // Handle mouse leaving the canvas while panning
                canvasEl.addEventListener('mouseleave', () => {
                    if (isPanning) {
                        isPanning = false;
                        this.canvas.selection = true;
                        canvasEl.style.cursor = 'default';
                    }
                });

                // Prevent context menu on middle click
                canvasEl.addEventListener('auxclick', (evt) => {
                    if (evt.button === 1) {
                        evt.preventDefault();
                    }
                });
            }

            // Mouse wheel zoom
            this.canvas.on('mouse:wheel', (opt) => {
                const delta = opt.e.deltaY;
                let zoom = this.canvas.getZoom();
                zoom *= 0.999 ** delta;

                // Limit zoom range
                if (zoom > 5) zoom = 5;
                if (zoom < 0.1) zoom = 0.1;

                // Zoom to cursor position
                this.canvas.zoomToPoint({ x: opt.e.offsetX, y: opt.e.offsetY }, zoom);

                opt.e.preventDefault();
                opt.e.stopPropagation();

                // Update zoom level display
                this.zoomLevel = zoom === 1 ? '1' : zoom < 1 ? `${Math.round(zoom * 100)}%` : `${Math.round(zoom * 100)}%`;
            });
        },

        /**
         * Select a tool tab
         */
        selectTool(tool) {
            // Deactivate current tool
            this.deactivateCurrentTool();

            this.activeTool = tool;

            // Activate new tool
            switch (tool) {
                case 'crop':
                    this.cropTool?.activate();
                    break;
                case 'filter':
                    this.filterTool?.activate();
                    break;
                case 'draw':
                    this.drawTool?.activate();
                    break;
            }
        },

        /**
         * Deactivate current tool
         */
        deactivateCurrentTool() {
            switch (this.activeTool) {
                case 'crop':
                    this.cropTool?.deactivate();
                    break;
                case 'filter':
                    this.filterTool?.deactivate();
                    break;
                case 'draw':
                    this.drawTool?.deactivate();
                    break;
            }
        },

        /**
         * Get tool icon
         */
        getToolIcon(tool) {
            const icons = {
                crop: 'heroicon-o-scissors',
                filter: 'heroicon-o-adjustments-horizontal',
                draw: 'heroicon-o-paint-brush',
            };
            return icons[tool] || 'heroicon-o-cog';
        },

        /**
         * Get tool label
         */
        getToolLabel(tool) {
            const labels = {
                crop: 'Crop',
                filter: 'Filter',
                draw: 'Draw',
            };
            return labels[tool] || tool;
        },

        // ===================
        // Crop Tool Methods
        // ===================

        setAspectRatio(label, ratio) {
            this.currentAspectRatio = label;
            this.cropTool?.setAspectRatio(ratio);
            this.hasUnsavedChanges = true;
        },

        rotate(degrees) {
            this.currentRotation = (this.currentRotation + degrees) % 360;
            // Normalize to positive value
            if (this.currentRotation < 0) {
                this.currentRotation += 360;
            }
            this.cropTool?.rotate(degrees);
            this.hasUnsavedChanges = true;
            this.saveToHistory();
        },

        setFineRotation(degrees) {
            this.fineRotation = parseInt(degrees);
            this.cropTool?.setFineRotation(this.fineRotation);
            this.hasUnsavedChanges = true;
        },

        flipHorizontal() {
            this.isFlippedH = !this.isFlippedH;
            this.cropTool?.flipHorizontal();
            this.hasUnsavedChanges = true;
            this.saveToHistory();
        },

        flipVertical() {
            this.isFlippedV = !this.isFlippedV;
            this.cropTool?.flipVertical();
            this.hasUnsavedChanges = true;
            this.saveToHistory();
        },

        // ===================
        // Filter Tool Methods
        // ===================

        applyFilterPreset(preset) {
            this.currentFilter = preset;
            this.filterTool?.applyPreset(preset);
            this.hasUnsavedChanges = true;
            this.saveToHistory();
        },

        getFilterLabel(preset) {
            const labels = {
                'original': 'Original',
                'grayscale': 'Grayscale',
                'sepia': 'Sepia',
                'vintage': 'Vintage',
                'warm': 'Warm',
                'cool': 'Cool',
                'high-contrast': 'Contrast',
                'fade': 'Fade',
                'dramatic': 'Dramatic',
                'vivid': 'Vivid',
            };
            return labels[preset] || preset;
        },

        getFilterCss(preset) {
            return this.filterTool?.getFilterCss(preset) || 'none';
        },

        applyAdjustments() {
            this.filterTool?.applyAdjustments(this.adjustments);
            this.hasUnsavedChanges = true;
        },

        resetAdjustments() {
            this.adjustments = {
                brightness: 0,
                contrast: 0,
                saturation: 0,
                exposure: 0,
                warmth: 0,
            };
            this.filterTool?.applyAdjustments(this.adjustments);
        },

        // ===================
        // Draw Tool Methods
        // ===================

        selectDrawingTool(tool) {
            this.currentDrawingTool = tool;
            this.drawTool?.selectTool(tool);
        },

        getDrawingToolIcon(tool) {
            const icons = {
                select: 'heroicon-o-cursor-arrow-rays',
                freehand: 'heroicon-o-pencil',
                eraser: 'heroicon-o-backspace',
                line: 'heroicon-o-minus',
                arrow: 'heroicon-o-arrow-long-right',
                rectangle: 'heroicon-o-stop',
                ellipse: 'heroicon-o-ellipsis-horizontal-circle',
                text: 'heroicon-o-document-text',
            };
            return icons[tool] || 'heroicon-o-cursor-arrow-rays';
        },

        getDrawingToolLabel(tool) {
            const labels = {
                select: 'Select',
                freehand: 'Draw',
                eraser: 'Eraser',
                line: 'Line',
                arrow: 'Arrow',
                rectangle: 'Rect',
                ellipse: 'Ellipse',
                text: 'Text',
            };
            return labels[tool] || tool;
        },

        bringToFront() {
            this.drawTool?.bringToFront();
            this.saveToHistory();
        },

        sendToBack() {
            this.drawTool?.sendToBack();
            this.saveToHistory();
        },

        duplicateSelection() {
            this.drawTool?.duplicate();
            this.saveToHistory();
        },

        deleteSelection() {
            this.drawTool?.deleteSelected();
            this.saveToHistory();
        },

        /**
         * Update info about the currently selected object
         */
        updateSelectedObjectInfo(obj) {
            if (!obj) {
                this.selectedObjectType = null;
                return;
            }

            this.selectedObjectType = obj.type;

            // If it's a text object, sync its properties to the UI
            if (obj.type === 'i-text' || obj.type === 'text' || obj.type === 'textbox') {
                this.textFont = obj.fontFamily || 'Arial';
                this.textSize = obj.fontSize || 24;
                this.textColor = obj.fill || '#000000';
                this.textBold = obj.fontWeight === 'bold';
                this.textItalic = obj.fontStyle === 'italic';
            }

            // If it's a shape object, sync stroke and fill properties to the UI
            // Skip eraser paths as they have rgba colors that don't work with color inputs
            if (['rect', 'ellipse', 'line', 'path', 'group', 'triangle'].includes(obj.type) && !obj._isEraserPath) {
                // For groups (like arrows), get stroke from the line object inside
                if (obj.type === 'group' && obj.getObjects) {
                    const lineObj = obj.getObjects().find(o => o.type === 'line');
                    if (lineObj) {
                        if (lineObj.stroke) {
                            this.strokeColor = lineObj.stroke;
                        }
                        if (lineObj.strokeWidth !== undefined) {
                            this.strokeWidth = lineObj.strokeWidth;
                        }
                    }
                } else {
                    // Sync stroke color (default to black if not set)
                    // Only sync hex colors to avoid issues with color input elements
                    if (obj.stroke && obj.stroke.startsWith('#')) {
                        this.strokeColor = obj.stroke;
                    }
                    // Sync stroke width
                    if (obj.strokeWidth !== undefined) {
                        this.strokeWidth = obj.strokeWidth;
                    }
                }
            }

            // Sync fill for fillable shapes
            if (['rect', 'ellipse'].includes(obj.type)) {
                // Handle null, undefined, or actual fill values
                if (obj.fill === null || obj.fill === undefined || obj.fill === '') {
                    this.fillColor = 'transparent';
                } else {
                    this.fillColor = obj.fill;
                }
            }
        },

        /**
         * Check if a text object is currently selected
         */
        isTextSelected() {
            return ['i-text', 'text', 'textbox'].includes(this.selectedObjectType);
        },

        /**
         * Check if a shape object (rect, ellipse, line, path, group) is currently selected
         */
        isShapeSelected() {
            return ['rect', 'ellipse', 'line', 'path', 'group', 'triangle'].includes(this.selectedObjectType);
        },

        /**
         * Check if a fillable shape (rect, ellipse) is currently selected
         */
        isFillableShapeSelected() {
            return ['rect', 'ellipse'].includes(this.selectedObjectType);
        },

        /**
         * Check if a group (arrow) is currently selected
         * Arrows are groups and don't support stroke width changes
         */
        isGroupSelected() {
            return this.selectedObjectType === 'group';
        },

        /**
         * Apply current text settings to the selected text object
         */
        applyTextSettings() {
            const activeObject = this.canvas?.getActiveObject();
            if (!activeObject) return;
            if (!['i-text', 'text', 'textbox'].includes(activeObject.type)) return;

            activeObject.set({
                fontFamily: this.textFont,
                fontSize: parseInt(this.textSize),
                fill: this.textColor,
                fontWeight: this.textBold ? 'bold' : 'normal',
                fontStyle: this.textItalic ? 'italic' : 'normal',
            });

            this.canvas.renderAll();
            this.hasUnsavedChanges = true;
        },

        /**
         * Apply current stroke settings to the selected shape or update brush
         */
        applyStrokeSettings() {
            // Update brush for freehand drawing
            this.drawTool?.updateBrushFromEditor();

            // Apply to selected object if one is selected
            const activeObject = this.canvas?.getActiveObject();
            if (!activeObject) return;

            // Don't apply stroke to text objects
            if (['i-text', 'text', 'textbox'].includes(activeObject.type)) return;

            // For groups (like arrows), only change color - stroke width would break the arrow
            // The arrow was built with a specific stroke width and changing it misaligns components
            if (activeObject.type === 'group' && activeObject.getObjects) {
                activeObject.getObjects().forEach(obj => {
                    if (obj.type === 'line') {
                        // Only change stroke color for arrows, not width
                        obj.set({ stroke: this.strokeColor });
                    } else if (obj.type === 'triangle') {
                        // Arrow head - change fill color to match stroke
                        obj.set({ fill: this.strokeColor });
                    }
                });
            } else {
                activeObject.set({
                    stroke: this.strokeColor,
                    strokeWidth: parseInt(this.strokeWidth),
                });
            }

            this.canvas.renderAll();
            this.hasUnsavedChanges = true;
        },

        /**
         * Apply current fill settings to the selected shape
         */
        applyFillSettings() {
            const activeObject = this.canvas?.getActiveObject();
            if (!activeObject) return;

            // Only apply fill to fillable shapes
            if (!['rect', 'ellipse'].includes(activeObject.type)) return;

            const fill = this.fillColor === 'transparent' ? 'transparent' : this.fillColor;
            activeObject.set({ fill: fill });

            this.canvas.renderAll();
            this.hasUnsavedChanges = true;
        },

        // ===================
        // History Methods
        // ===================

        saveToHistory() {
            if (!this.canvas) return;

            const state = this.canvas.toJSON();
            this.historyManager.push(state);
            this.updateHistoryState();
        },

        updateHistoryState() {
            this.canUndo = this.historyManager.canUndo();
            this.canRedo = this.historyManager.canRedo();
        },

        async undo() {
            if (!this.canUndo || !this.canvas) return;

            const state = this.historyManager.undo();
            if (state) {
                // Fabric.js 6.x loadFromJSON returns a Promise
                await this.canvas.loadFromJSON(state);
                this.canvas.renderAll();
                this.updateHistoryState();
            }
        },

        async redo() {
            if (!this.canRedo || !this.canvas) return;

            const state = this.historyManager.redo();
            if (state) {
                // Fabric.js 6.x loadFromJSON returns a Promise
                await this.canvas.loadFromJSON(state);
                this.canvas.renderAll();
                this.updateHistoryState();
            }
        },

        confirmReset() {
            // Show styled reset confirmation dialog
            this.showResetDialog = true;
        },

        async resetToOriginal() {
            this.isCanvasLoading = true;

            // Reset all editor state to defaults
            this.resetEditorState();

            // Reinitialize canvas
            await this.initializeCanvas();
            this.initializeTools();
            this.selectTool(this.activeTool);

            this.isCanvasLoading = false;
        },

        // ===================
        // Zoom Methods
        // ===================

        setZoom(level) {
            this.zoomLevel = level;
            if (level === 'fit') {
                this.cropTool?.fitToContainer();
            } else {
                this.cropTool?.setZoom(parseFloat(level));
            }
        },

        zoomIn() {
            const levels = ['fit', '0.5', '1', '2'];
            const currentIndex = levels.indexOf(this.zoomLevel);
            if (currentIndex < levels.length - 1) {
                this.setZoom(levels[currentIndex + 1]);
            }
        },

        zoomOut() {
            const levels = ['fit', '0.5', '1', '2'];
            const currentIndex = levels.indexOf(this.zoomLevel);
            if (currentIndex > 0) {
                this.setZoom(levels[currentIndex - 1]);
            }
        },

        // ===================
        // Export & Save Methods
        // ===================

        async applyChanges() {
            this.isCanvasLoading = true;

            try {
                // Export canvas to blob
                // Total rotation = 90-degree increments + fine rotation adjustment
                const totalRotation = this.currentRotation + this.fineRotation;

                const blob = await exportCanvas(
                    this.canvas,
                    this.originalImage,
                    {
                        format: this.config.output?.format || 'jpeg',
                        quality: this.config.output?.quality || 0.92,
                        maxWidth: this.config.output?.maxWidth,
                        maxHeight: this.config.output?.maxHeight,
                        cropArea: this.cropTool?.getCropArea(),
                        rotation: totalRotation,
                        flipH: this.isFlippedH,
                        flipV: this.isFlippedV,
                        filters: {
                            preset: this.currentFilter,
                            adjustments: this.adjustments,
                        },
                    }
                );

                // Create file from blob
                const filename = this.generateFilename();
                const file = new File([blob], filename, { type: blob.type });

                // Upload via Livewire
                await this.uploadFile(file);

                // Update original state to reflect successful save
                // This ensures cancel will restore to the saved state, not the pre-edit state
                this._originalHasImage = this.hasImage;
                this._originalPreviewUrl = this.previewUrl;
                this._originalState = this.state;

                // Handle multi-image mode
                if (this.isMultiImage && this.currentImageIndex < this.totalImages - 1) {
                    this.currentImageIndex++;
                    await this.loadImage(this.imageQueue[this.currentImageIndex]);
                    await this.initializeCanvas();
                    this.initializeTools();
                    this.selectTool(this.activeTool);
                    this.hasUnsavedChanges = false;
                } else {
                    // Close editor
                    this.closeEditor();
                }
            } catch (error) {
                console.error('Error applying changes:', error);
                this.showError('Failed to save image');
            } finally {
                this.isCanvasLoading = false;
            }
        },

        /**
         * Upload file via Livewire state
         */
        async uploadFile(file) {
            // Update preview URL immediately for better UX
            this.previewUrl = URL.createObjectURL(file);

            // Mark that we have an image
            this.hasImage = true;

            // Convert file to base64
            const base64Data = await this.fileToBase64(file);

            // Set the entangled state - this updates Alpine's reactive state
            this.state = base64Data;

            // Also set directly on Livewire to ensure immediate sync
            // This is necessary because $entangle may be deferred and won't sync
            // before the form is submitted if the user clicks Save immediately
            if (this.$wire && this.statePath) {
                await this.$wire.set(this.statePath, base64Data);
            }
        },

        /**
         * Convert a File to base64 string
         */
        fileToBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        },

        generateFilename() {
            const format = this.config.output?.format || 'jpeg';
            const ext = format === 'jpeg' ? 'jpg' : format;
            const timestamp = Date.now();
            return `edited-image-${timestamp}.${ext}`;
        },

        // ===================
        // UI Methods
        // ===================

        confirmClose() {
            if (this.hasUnsavedChanges) {
                // Show styled discard confirmation dialog
                this.showDiscardDialog = true;
            } else {
                this.closeEditor();
            }
        },

        closeEditor() {
            this.deactivateCurrentTool();

            // Clear any CSS filters from the canvas before disposing
            if (this.canvas) {
                if (this.canvas.lowerCanvasEl) {
                    this.canvas.lowerCanvasEl.style.filter = 'none';
                }
                if (this.canvas.upperCanvasEl) {
                    this.canvas.upperCanvasEl.style.filter = 'none';
                }
                this.canvas.dispose();
                this.canvas = null;
            }

            // Restore original state (before editing started)
            this.hasImage = this._originalHasImage;
            this.previewUrl = this._originalPreviewUrl;
            this.state = this._originalState;

            // Reset the flag for next edit session
            this._originalStateSaved = false;

            this.isEditorOpen = false;
            this.hasUnsavedChanges = false;
        },

        removeImage() {
            this.hasImage = false;
            this.previewUrl = null;
            this.state = null;
        },

        handleEscape() {
            // Close any open dialogs first
            if (this.showDiscardDialog) {
                this.showDiscardDialog = false;
                return;
            }
            if (this.showResetDialog) {
                this.showResetDialog = false;
                return;
            }
            // Then try to close editor
            if (this.isEditorOpen) {
                this.confirmClose();
            }
        },

        handleExternalOpen(detail) {
            // Handle external open requests (programmatic usage)
            if (detail?.source) {
                this.previewUrl = detail.source;
                this.hasImage = true;
            }
            if (detail?.config) {
                Object.assign(this.config, detail.config);
            }
            this.openEditor();
        },

        previousImage() {
            if (this.currentImageIndex > 0) {
                this.currentImageIndex--;
                this.loadImage(this.imageQueue[this.currentImageIndex]);
            }
        },

        // ===================
        // Keyboard Shortcuts
        // ===================

        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                if (!this.isEditorOpen) return;

                // Check if a text object is currently being edited
                const activeObject = this.canvas?.getActiveObject();
                const isEditingText = activeObject &&
                    ['i-text', 'text', 'textbox'].includes(activeObject.type) &&
                    activeObject.isEditing;

                // If editing text, only capture Ctrl/Cmd shortcuts, let other keys through
                if (isEditingText) {
                    // Only handle undo/redo when editing text, let all other keys go to the text editor
                    if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
                        e.preventDefault();
                        this.undo();
                        return;
                    }
                    if ((e.ctrlKey || e.metaKey) && (e.key === 'y' || (e.key === 'z' && e.shiftKey))) {
                        e.preventDefault();
                        this.redo();
                        return;
                    }
                    // Let all other keys pass through to the text editor
                    return;
                }

                // Ctrl/Cmd + Z = Undo
                if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
                    e.preventDefault();
                    this.undo();
                }

                // Ctrl/Cmd + Y or Ctrl/Cmd + Shift + Z = Redo
                if ((e.ctrlKey || e.metaKey) && (e.key === 'y' || (e.key === 'z' && e.shiftKey))) {
                    e.preventDefault();
                    this.redo();
                }

                // Delete/Backspace = Delete selected
                if ((e.key === 'Delete' || e.key === 'Backspace') && this.hasSelection) {
                    e.preventDefault();
                    this.deleteSelection();
                }

                // Ctrl/Cmd + D = Duplicate
                if ((e.ctrlKey || e.metaKey) && e.key === 'd' && this.hasSelection) {
                    e.preventDefault();
                    this.duplicateSelection();
                }
            });
        },

        // ===================
        // Utility Methods
        // ===================

        showError(message) {
            // Use Filament's notification system if available
            if (window.$wireui) {
                window.$wireui.notify({
                    title: 'Error',
                    description: message,
                    icon: 'error',
                });
            } else {
                console.error(message);
                alert(message);
            }
        },

        showWarning(message) {
            console.warn(message);
        },

        getTranslation(key, replacements = {}) {
            // Simple translation lookup - in production, use proper i18n
            let text = key;

            for (const [search, replace] of Object.entries(replacements)) {
                text = text.replace(`:${search}`, replace);
            }

            return text;
        },

        formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        },
    };
}
