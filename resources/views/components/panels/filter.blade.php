{{-- Filter Presets --}}
<div x-show="!config.filters.disabled">
    <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
        {{ __('filament-image-editor::editor.filters.presets') }}
    </h3>
    <div class="grid grid-cols-3 gap-2">
        <template x-for="preset in config.filters.presets" :key="preset">
            <button
                type="button"
                x-on:click="applyFilterPreset(preset)"
                :class="{
                    'ring-2 ring-primary-500': currentFilter === preset,
                }"
                class="relative overflow-hidden rounded-lg aspect-square group"
            >
                {{-- Preview thumbnail with filter applied --}}
                <div
                    class="absolute inset-0 bg-cover bg-center"
                    :style="'background-image: url(' + thumbnailUrl + '); filter: ' + getFilterCss(preset)"
                ></div>

                {{-- Label overlay --}}
                <div class="absolute inset-x-0 bottom-0 px-1 py-0.5 text-xs font-medium text-center text-white bg-black/60">
                    <span x-text="getFilterLabel(preset)"></span>
                </div>
            </button>
        </template>
    </div>
</div>

{{-- Adjustments --}}
<div x-show="!config.filters.adjustmentsDisabled" class="space-y-4">
    <h3 class="text-sm font-medium text-gray-900 dark:text-white">
        {{ __('filament-image-editor::editor.filters.adjustments') }}
    </h3>

    {{-- Brightness --}}
    <div x-show="config.filters.adjustments.includes('brightness')">
        <div class="flex items-center justify-between mb-1">
            <label class="text-xs text-gray-600 dark:text-gray-400">
                {{ __('filament-image-editor::editor.filters.brightness') }}
            </label>
            <span class="text-xs font-medium text-gray-900 dark:text-white" x-text="adjustments.brightness"></span>
        </div>
        <input
            type="range"
            x-model.number="adjustments.brightness"
            x-on:input="applyAdjustments()"
            min="-100"
            max="100"
            step="1"
            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
        />
    </div>

    {{-- Contrast --}}
    <div x-show="config.filters.adjustments.includes('contrast')">
        <div class="flex items-center justify-between mb-1">
            <label class="text-xs text-gray-600 dark:text-gray-400">
                {{ __('filament-image-editor::editor.filters.contrast') }}
            </label>
            <span class="text-xs font-medium text-gray-900 dark:text-white" x-text="adjustments.contrast"></span>
        </div>
        <input
            type="range"
            x-model.number="adjustments.contrast"
            x-on:input="applyAdjustments()"
            min="-100"
            max="100"
            step="1"
            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
        />
    </div>

    {{-- Saturation --}}
    <div x-show="config.filters.adjustments.includes('saturation')">
        <div class="flex items-center justify-between mb-1">
            <label class="text-xs text-gray-600 dark:text-gray-400">
                {{ __('filament-image-editor::editor.filters.saturation') }}
            </label>
            <span class="text-xs font-medium text-gray-900 dark:text-white" x-text="adjustments.saturation"></span>
        </div>
        <input
            type="range"
            x-model.number="adjustments.saturation"
            x-on:input="applyAdjustments()"
            min="-100"
            max="100"
            step="1"
            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
        />
    </div>

    {{-- Exposure --}}
    <div x-show="config.filters.adjustments.includes('exposure')">
        <div class="flex items-center justify-between mb-1">
            <label class="text-xs text-gray-600 dark:text-gray-400">
                {{ __('filament-image-editor::editor.filters.exposure') }}
            </label>
            <span class="text-xs font-medium text-gray-900 dark:text-white" x-text="adjustments.exposure"></span>
        </div>
        <input
            type="range"
            x-model.number="adjustments.exposure"
            x-on:input="applyAdjustments()"
            min="-100"
            max="100"
            step="1"
            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
        />
    </div>

    {{-- Warmth --}}
    <div x-show="config.filters.adjustments.includes('warmth')">
        <div class="flex items-center justify-between mb-1">
            <label class="text-xs text-gray-600 dark:text-gray-400">
                {{ __('filament-image-editor::editor.filters.warmth') }}
            </label>
            <span class="text-xs font-medium text-gray-900 dark:text-white" x-text="adjustments.warmth"></span>
        </div>
        <input
            type="range"
            x-model.number="adjustments.warmth"
            x-on:input="applyAdjustments()"
            min="-100"
            max="100"
            step="1"
            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
        />
    </div>

    {{-- Reset Adjustments Button --}}
    <button
        type="button"
        x-on:click="resetAdjustments()"
        class="w-full px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
    >
        {{ __('filament-image-editor::editor.filters.reset') }}
    </button>
</div>
