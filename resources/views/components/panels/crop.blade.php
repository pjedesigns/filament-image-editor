{{-- Aspect Ratio Selection --}}
<div>
    <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
        {{ __('filament-image-editor::editor.crop.aspect_ratio') }}
    </h3>
    <div class="grid grid-cols-3 gap-2">
        <template x-for="(ratio, label) in config.crop.aspectRatios" :key="label">
            <button
                type="button"
                x-on:click="setAspectRatio(label, ratio)"
                :class="{
                    'bg-primary-500 text-white border-primary-500': currentAspectRatio === label,
                    'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-primary-500': currentAspectRatio !== label
                }"
                class="px-3 py-2 text-xs font-medium border rounded-lg transition-colors"
                x-text="label"
            ></button>
        </template>
    </div>
</div>

{{-- Rotation Controls --}}
<div x-show="config.crop.enableRotation">
    <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
        {{ __('filament-image-editor::editor.crop.rotation') }}
    </h3>

    {{-- 90° Rotation Buttons --}}
    <div class="flex items-center gap-2 mb-4">
        <button
            type="button"
            x-on:click="rotate(-90)"
            class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
        >
            <x-filament::icon icon="heroicon-o-arrow-uturn-left" class="w-4 h-4" />
            -90°
        </button>
        <button
            type="button"
            x-on:click="rotate(90)"
            class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
        >
            <x-filament::icon icon="heroicon-o-arrow-uturn-right" class="w-4 h-4" />
            +90°
        </button>
    </div>

    {{-- Fine Rotation Slider --}}
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <label class="text-xs text-gray-600 dark:text-gray-400">
                {{ __('filament-image-editor::editor.crop.fine_rotation') }}
            </label>
            <span class="text-xs font-medium text-gray-900 dark:text-white" x-text="fineRotation + '°'"></span>
        </div>
        <input
            type="range"
            x-model="fineRotation"
            x-on:input="setFineRotation($event.target.value)"
            min="-45"
            max="45"
            step="1"
            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
        />
        <div class="flex justify-between text-xs text-gray-500">
            <span>-45°</span>
            <span>0°</span>
            <span>+45°</span>
        </div>
    </div>
</div>

{{-- Flip Controls --}}
<div x-show="config.crop.enableFlip">
    <h3 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
        {{ __('filament-image-editor::editor.crop.flip') }}
    </h3>
    <div class="flex items-center gap-2">
        <button
            type="button"
            x-on:click="flipHorizontal()"
            :class="{ 'bg-primary-100 dark:bg-primary-900 border-primary-500': isFlippedH }"
            class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
        >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 3v18M9 6l-6 6 6 6M15 6l6 6-6 6"/>
            </svg>
            {{ __('filament-image-editor::editor.crop.horizontal') }}
        </button>
        <button
            type="button"
            x-on:click="flipVertical()"
            :class="{ 'bg-primary-100 dark:bg-primary-900 border-primary-500': isFlippedV }"
            class="flex-1 flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
        >
            <svg class="w-4 h-4 rotate-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 3v18M9 6l-6 6 6 6M15 6l6 6-6 6"/>
            </svg>
            {{ __('filament-image-editor::editor.crop.vertical') }}
        </button>
    </div>
</div>

{{-- Crop Dimensions Info --}}
<div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
    <h4 class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
        {{ __('filament-image-editor::editor.crop.dimensions') }}
    </h4>
    <div class="grid grid-cols-2 gap-3 text-sm">
        <div>
            <span class="text-gray-500 dark:text-gray-400">{{ __('filament-image-editor::editor.crop.width') }}:</span>
            <span class="ml-1 font-medium text-gray-900 dark:text-white" x-text="cropWidth + 'px'"></span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">{{ __('filament-image-editor::editor.crop.height') }}:</span>
            <span class="ml-1 font-medium text-gray-900 dark:text-white" x-text="cropHeight + 'px'"></span>
        </div>
    </div>
</div>
