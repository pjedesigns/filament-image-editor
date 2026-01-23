{{-- Drawing Tools --}}
<div>
    <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
        {{ __('filament-image-editor::editor.draw.tools') }}
    </h3>
    <div class="flex flex-wrap gap-1">
        <template x-for="tool in config.draw.tools" :key="tool">
            <button
                type="button"
                x-on:click="selectDrawingTool(tool)"
                :title="getDrawingToolLabel(tool)"
                :class="{
                    'bg-primary-500 text-white border-primary-500': currentDrawingTool === tool,
                    'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-primary-500 hover:bg-gray-50 dark:hover:bg-gray-700': currentDrawingTool !== tool
                }"
                class="flex items-center justify-center w-9 h-9 border rounded-lg transition-colors"
            >
                <svg x-show="tool === 'select'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672 13.684 16.6m0 0-2.51 2.225.569-9.47 5.227 7.917-3.286-.672ZM12 2.25V4.5m5.834.166-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243-1.59-1.59" />
                </svg>
                <svg x-show="tool === 'freehand'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                </svg>
                <svg x-show="tool === 'eraser'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    {{-- Eraser icon - classic eraser shape at 45 degree angle --}}
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 18.75 9 13.5l4.5 4.5-5.25 5.25a1.5 1.5 0 0 1-2.122 0l-2.378-2.378a1.5 1.5 0 0 1 0-2.122ZM9 13.5l9-9a1.5 1.5 0 0 1 2.122 0l2.378 2.378a1.5 1.5 0 0 1 0 2.122l-9 9" />
                </svg>
                <svg x-show="tool === 'line'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                </svg>
                <svg x-show="tool === 'arrow'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                </svg>
                <svg x-show="tool === 'rectangle'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 7.5A2.25 2.25 0 0 1 7.5 5.25h9a2.25 2.25 0 0 1 2.25 2.25v9a2.25 2.25 0 0 1-2.25 2.25h-9a2.25 2.25 0 0 1-2.25-2.25v-9Z" />
                </svg>
                <svg x-show="tool === 'ellipse'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <svg x-show="tool === 'text'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                </svg>
            </button>
        </template>
    </div>
</div>

{{-- Stroke Options (show when using stroke tools OR when a shape with stroke is selected) --}}
<div x-show="['freehand', 'line', 'arrow', 'rectangle', 'ellipse'].includes(currentDrawingTool) || isShapeSelected()">
    <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
        {{ __('filament-image-editor::editor.draw.stroke') }}
    </h3>

    {{-- Stroke Color --}}
    <div class="mb-3">
        <label class="block mb-2 text-xs text-gray-600 dark:text-gray-400">
            {{ __('filament-image-editor::editor.draw.stroke_color') }}
        </label>
        <div class="flex flex-wrap gap-1">
            <template x-for="color in config.draw.colorPalette" :key="'stroke-' + color">
                <button
                    type="button"
                    x-on:click="strokeColor = color; applyStrokeSettings()"
                    :class="{
                        'ring-2 ring-primary-500 ring-offset-1': strokeColor === color,
                    }"
                    :style="color === 'transparent' ? 'background: linear-gradient(45deg, #ccc 25%, transparent 25%), linear-gradient(-45deg, #ccc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #ccc 75%), linear-gradient(-45deg, transparent 75%, #ccc 75%); background-size: 8px 8px; background-position: 0 0, 0 4px, 4px -4px, -4px 0px;' : 'background-color: ' + color"
                    class="w-6 h-6 border border-gray-300 rounded dark:border-gray-600"
                ></button>
            </template>

            {{-- Custom Color Input --}}
            <input
                type="color"
                x-model="strokeColor"
                x-on:input="applyStrokeSettings()"
                class="w-6 h-6 border border-gray-300 rounded cursor-pointer dark:border-gray-600"
            />
        </div>
    </div>

    {{-- Stroke Width (hide for arrows/groups since width changes break them) --}}
    <div x-show="!isGroupSelected()">
        <div class="flex items-center justify-between mb-1">
            <label class="text-xs text-gray-600 dark:text-gray-400">
                {{ __('filament-image-editor::editor.draw.stroke_width') }}
            </label>
            <span class="text-xs font-medium text-gray-900 dark:text-white" x-text="strokeWidth + 'px'"></span>
        </div>
        <div class="flex gap-2">
            <template x-for="width in [1, 2, 4, 8, 12, 16]" :key="'width-' + width">
                <button
                    type="button"
                    x-on:click="strokeWidth = width; applyStrokeSettings()"
                    :class="{
                        'bg-primary-500 text-white': strokeWidth === width,
                        'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300': strokeWidth !== width
                    }"
                    class="flex-1 px-2 py-1 text-xs font-medium border border-gray-300 rounded dark:border-gray-600"
                    x-text="width"
                ></button>
            </template>
        </div>
    </div>
</div>

{{-- Fill Options (for shapes - show when using shape tools OR when a fillable shape is selected) --}}
<div x-show="['rectangle', 'ellipse'].includes(currentDrawingTool) || isFillableShapeSelected()">
    <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
        {{ __('filament-image-editor::editor.draw.fill') }}
    </h3>

    <div class="mb-3">
        <label class="block mb-2 text-xs text-gray-600 dark:text-gray-400">
            {{ __('filament-image-editor::editor.draw.fill_color') }}
        </label>
        <div class="flex flex-wrap gap-1">
            <template x-for="color in config.draw.colorPalette" :key="'fill-' + color">
                <button
                    type="button"
                    x-on:click="fillColor = color; applyFillSettings()"
                    :class="{
                        'ring-2 ring-primary-500 ring-offset-1': fillColor === color,
                    }"
                    :style="color === 'transparent' ? 'background: linear-gradient(45deg, #ccc 25%, transparent 25%), linear-gradient(-45deg, #ccc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #ccc 75%), linear-gradient(-45deg, transparent 75%, #ccc 75%); background-size: 8px 8px; background-position: 0 0, 0 4px, 4px -4px, -4px 0px;' : 'background-color: ' + color"
                    class="w-6 h-6 border border-gray-300 rounded dark:border-gray-600"
                ></button>
            </template>

            {{-- Custom color picker - only bind when fillColor is not transparent --}}
            <input
                type="color"
                :value="fillColor === 'transparent' ? '#ffffff' : fillColor"
                x-on:input="fillColor = $event.target.value; applyFillSettings()"
                class="w-6 h-6 border border-gray-300 rounded cursor-pointer dark:border-gray-600"
            />
        </div>
    </div>
</div>

{{-- Text Options (show when text tool selected OR when editing a text object) --}}
<div x-show="currentDrawingTool === 'text' || isTextSelected()">
    <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
        {{ __('filament-image-editor::editor.draw.text_options') }}
    </h3>

    {{-- Help text for text tool --}}
    <div x-show="currentDrawingTool === 'text'" class="p-2 mb-3 text-xs text-gray-600 bg-gray-100 rounded-lg dark:bg-gray-800 dark:text-gray-400">
        {{ __('filament-image-editor::editor.draw.text_help') }}
    </div>

    {{-- Help text when text is selected --}}
    <div x-show="isTextSelected() && currentDrawingTool !== 'text'" class="p-2 mb-3 text-xs text-blue-600 bg-blue-50 rounded-lg dark:bg-blue-900/30 dark:text-blue-400">
        {{ __('filament-image-editor::editor.draw.text_edit_help') }}
    </div>

    {{-- Font Family --}}
    <div class="mb-3">
        <label class="block mb-1 text-xs text-gray-600 dark:text-gray-400">
            {{ __('filament-image-editor::editor.draw.font_family') }}
        </label>
        <select
            x-model="textFont"
            x-on:change="applyTextSettings()"
            class="w-full px-3 py-2 text-sm bg-white border border-gray-300 rounded-lg dark:bg-gray-800 dark:border-gray-600"
        >
            <template x-for="font in config.draw.fonts" :key="font">
                <option :value="font" x-text="font" :style="'font-family: ' + font"></option>
            </template>
        </select>
    </div>

    {{-- Font Size --}}
    <div class="mb-3">
        <div class="flex items-center justify-between mb-1">
            <label class="text-xs text-gray-600 dark:text-gray-400">
                {{ __('filament-image-editor::editor.draw.font_size') }}
            </label>
            <span class="text-xs font-medium text-gray-900 dark:text-white" x-text="textSize + 'px'"></span>
        </div>
        <input
            type="range"
            x-model="textSize"
            x-on:input="applyTextSettings()"
            min="12"
            max="72"
            step="2"
            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
        />
    </div>

    {{-- Text Color --}}
    <div class="mb-3">
        <label class="block mb-2 text-xs text-gray-600 dark:text-gray-400">
            {{ __('filament-image-editor::editor.draw.text_color') }}
        </label>
        <div class="flex flex-wrap gap-1">
            <template x-for="color in config.draw.colorPalette.filter(c => c !== 'transparent')" :key="'text-' + color">
                <button
                    type="button"
                    x-on:click="textColor = color; applyTextSettings()"
                    :class="{
                        'ring-2 ring-primary-500 ring-offset-1': textColor === color,
                    }"
                    :style="'background-color: ' + color"
                    class="w-6 h-6 border border-gray-300 rounded dark:border-gray-600"
                ></button>
            </template>

            <input
                type="color"
                x-model="textColor"
                x-on:input="applyTextSettings()"
                class="w-6 h-6 border border-gray-300 rounded cursor-pointer dark:border-gray-600"
            />
        </div>
    </div>

    {{-- Bold/Italic --}}
    <div class="flex gap-2">
        <button
            type="button"
            x-on:click="textBold = !textBold; applyTextSettings()"
            :class="{
                'bg-primary-500 text-white': textBold,
                'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300': !textBold
            }"
            class="flex-1 px-3 py-2 text-sm font-bold border border-gray-300 rounded-lg dark:border-gray-600"
        >
            B
        </button>
        <button
            type="button"
            x-on:click="textItalic = !textItalic; applyTextSettings()"
            :class="{
                'bg-primary-500 text-white': textItalic,
                'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300': !textItalic
            }"
            class="flex-1 px-3 py-2 text-sm italic border border-gray-300 rounded-lg dark:border-gray-600"
        >
            I
        </button>
    </div>
</div>

{{-- Eraser Size --}}
<div x-show="currentDrawingTool === 'eraser'">
    <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
        {{ __('filament-image-editor::editor.draw.eraser_size') }}
    </h3>
    <div class="flex items-center justify-between mb-1">
        <label class="text-xs text-gray-600 dark:text-gray-400">
            {{ __('filament-image-editor::editor.draw.size') }}
        </label>
        <span class="text-xs font-medium text-gray-900 dark:text-white" x-text="eraserSize + 'px'"></span>
    </div>
    <input
        type="range"
        x-model="eraserSize"
        x-on:input="drawTool?.updateBrushFromEditor()"
        min="5"
        max="50"
        step="5"
        class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
    />
</div>

{{-- Selection Actions --}}
<div x-show="currentDrawingTool === 'select' && hasSelection">
    <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
        {{ __('filament-image-editor::editor.draw.selection') }}
    </h3>
    <div class="space-y-2">
        <button
            type="button"
            x-on:click="bringToFront()"
            class="w-full px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
        >
            {{ __('filament-image-editor::editor.draw.bring_to_front') }}
        </button>
        <button
            type="button"
            x-on:click="sendToBack()"
            class="w-full px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
        >
            {{ __('filament-image-editor::editor.draw.send_to_back') }}
        </button>
        <button
            type="button"
            x-on:click="duplicateSelection()"
            class="w-full px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
        >
            {{ __('filament-image-editor::editor.draw.duplicate') }}
        </button>
        <button
            type="button"
            x-on:click="deleteSelection()"
            class="w-full px-3 py-2 text-sm font-medium text-white bg-danger-500 rounded-lg hover:bg-danger-600"
        >
            {{ __('filament-image-editor::editor.draw.delete') }}
        </button>
    </div>
</div>
