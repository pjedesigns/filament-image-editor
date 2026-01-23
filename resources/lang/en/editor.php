<?php

return [
    'upload' => [
        'click' => 'Click to upload',
        'or_drag' => 'or drag and drop',
        'accepted_types' => 'PNG, JPG, WebP or GIF',
    ],

    'buttons' => [
        'edit' => 'Edit',
        'remove' => 'Remove',
        'change' => 'Change image',
        'cancel' => 'Cancel',
        'apply' => 'Apply',
        'next' => 'Next',
        'previous' => 'Previous',
        'done' => 'Done',
    ],

    'modal' => [
        'title' => 'Edit Image',
    ],

    'tools' => [
        'crop' => 'Crop',
        'filter' => 'Filter',
        'draw' => 'Draw',
    ],

    'history' => [
        'undo' => 'Undo',
        'redo' => 'Redo',
        'reset' => 'Reset to original',
    ],

    'zoom' => [
        'fit' => 'Fit',
    ],

    'crop' => [
        'aspect_ratio' => 'Aspect Ratio',
        'rotation' => 'Rotation',
        'fine_rotation' => 'Fine rotation',
        'flip' => 'Flip',
        'horizontal' => 'Horizontal',
        'vertical' => 'Vertical',
        'dimensions' => 'Crop Dimensions',
        'width' => 'Width',
        'height' => 'Height',
    ],

    'filters' => [
        'presets' => 'Filter Presets',
        'adjustments' => 'Adjustments',
        'brightness' => 'Brightness',
        'contrast' => 'Contrast',
        'saturation' => 'Saturation',
        'exposure' => 'Exposure',
        'warmth' => 'Warmth',
        'reset' => 'Reset Adjustments',

        'preset_labels' => [
            'original' => 'Original',
            'grayscale' => 'Grayscale',
            'sepia' => 'Sepia',
            'vintage' => 'Vintage',
            'warm' => 'Warm',
            'cool' => 'Cool',
            'high-contrast' => 'High Contrast',
            'fade' => 'Fade',
            'dramatic' => 'Dramatic',
            'vivid' => 'Vivid',
        ],
    ],

    'draw' => [
        'tools' => 'Drawing Tools',
        'stroke' => 'Stroke',
        'stroke_color' => 'Stroke Color',
        'stroke_width' => 'Stroke Width',
        'fill' => 'Fill',
        'fill_color' => 'Fill Color',
        'text_options' => 'Text Options',
        'font_family' => 'Font Family',
        'font_size' => 'Font Size',
        'text_color' => 'Text Color',
        'eraser_size' => 'Eraser',
        'size' => 'Size',
        'selection' => 'Selection',
        'bring_to_front' => 'Bring to Front',
        'send_to_back' => 'Send to Back',
        'duplicate' => 'Duplicate',
        'delete' => 'Delete',

        'tool_labels' => [
            'select' => 'Select',
            'freehand' => 'Draw',
            'eraser' => 'Eraser',
            'line' => 'Line',
            'arrow' => 'Arrow',
            'rectangle' => 'Rectangle',
            'ellipse' => 'Ellipse',
            'text' => 'Text',
        ],

        'text_help' => 'Click on the image to add text. Double-click to edit.',
        'text_edit_help' => 'Double-click the text to edit it. Change settings below to update the selected text.',
    ],

    'dialogs' => [
        'unsaved_changes' => [
            'title' => 'Discard changes?',
            'message' => 'You have unsaved changes. Are you sure you want to close without saving?',
            'confirm' => 'Discard',
            'cancel' => 'Keep editing',
        ],
        'discard' => [
            'title' => 'Discard changes?',
            'message' => 'You have unsaved changes. Are you sure you want to close without saving?',
            'confirm' => 'Discard',
            'cancel' => 'Keep editing',
        ],
        'reset' => [
            'title' => 'Reset to original?',
            'message' => 'This will undo all changes and reset the image to its original state.',
            'confirm' => 'Reset',
            'cancel' => 'Cancel',
        ],
    ],

    'validation' => [
        'file_too_large' => 'The file is too large. Maximum size is :max.',
        'invalid_type' => 'The file type is not supported. Please upload a valid image.',
        'min_dimensions' => 'The image is smaller than the recommended :width x :height pixels.',
    ],
];
