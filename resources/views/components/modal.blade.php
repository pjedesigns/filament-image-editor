{{-- Modal Header --}}
<div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 shrink-0">
    <div class="flex items-center gap-4">
        {{-- Back button for multi-image mode --}}
        <button
            x-show="isMultiImage && currentImageIndex > 0"
            x-on:click="previousImage()"
            type="button"
            class="p-2 text-gray-500 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800"
        >
            <x-filament::icon icon="heroicon-o-arrow-left" class="w-5 h-5" />
        </button>

        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ __('filament-image-editor::editor.modal.title') }}
            <span x-show="isMultiImage" x-text="'(' + (currentImageIndex + 1) + '/' + totalImages + ')'"></span>
        </h2>
    </div>

    <div class="flex items-center gap-2">
        <x-filament::button
            type="button"
            x-on:click="confirmClose()"
            color="gray"
            size="sm"
        >
            {{ __('filament-image-editor::editor.buttons.cancel') }}
        </x-filament::button>

        <x-filament::button
            type="button"
            x-on:click="applyChanges()"
            size="sm"
            :icon="$isMultiImage ?? false ? null : 'heroicon-o-check'"
        >
            <span x-show="!isMultiImage || currentImageIndex === totalImages - 1">
                {{ __('filament-image-editor::editor.buttons.apply') }}
            </span>
            <span x-show="isMultiImage && currentImageIndex < totalImages - 1">
                {{ __('filament-image-editor::editor.buttons.next') }}
            </span>
        </x-filament::button>
    </div>
</div>

{{-- Tool Tabs & History Controls --}}
<div class="flex items-center justify-between px-6 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 shrink-0">
    {{-- Tool Tabs --}}
    <div class="flex items-center gap-1">
        <template x-for="tool in availableTools" :key="tool">
            <button
                type="button"
                x-on:click="selectTool(tool)"
                :class="{
                    'bg-primary-500 text-white': activeTool === tool,
                    'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600': activeTool !== tool
                }"
                class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors"
            >
                <x-filament::icon
                    x-bind:icon="getToolIcon(tool)"
                    class="w-4 h-4"
                />
                <span x-text="getToolLabel(tool)"></span>
            </button>
        </template>
    </div>

    {{-- History Controls --}}
    <div class="flex items-center gap-1">
        <button
            type="button"
            x-on:click="undo()"
            :disabled="!canUndo"
            :class="{ 'opacity-50 cursor-not-allowed': !canUndo }"
            class="p-2 text-gray-600 rounded-lg dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
            title="{{ __('filament-image-editor::editor.history.undo') }}"
        >
            <x-filament::icon icon="heroicon-o-arrow-uturn-left" class="w-5 h-5" />
        </button>

        <button
            type="button"
            x-on:click="redo()"
            :disabled="!canRedo"
            :class="{ 'opacity-50 cursor-not-allowed': !canRedo }"
            class="p-2 text-gray-600 rounded-lg dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
            title="{{ __('filament-image-editor::editor.history.redo') }}"
        >
            <x-filament::icon icon="heroicon-o-arrow-uturn-right" class="w-5 h-5" />
        </button>

        <div class="w-px h-6 mx-2 bg-gray-300 dark:bg-gray-600"></div>

        <button
            type="button"
            x-on:click="confirmReset()"
            class="p-2 text-gray-600 rounded-lg dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
            title="{{ __('filament-image-editor::editor.history.reset') }}"
        >
            <x-filament::icon icon="heroicon-o-arrow-path" class="w-5 h-5" />
        </button>
    </div>
</div>

{{-- Main Content Area --}}
<div class="flex flex-1 min-h-0 overflow-hidden">
    {{-- Canvas Area --}}
    <div class="relative flex-1 flex items-center justify-center bg-gray-100 dark:bg-gray-950 overflow-hidden">
        {{-- Checkered Background for Transparency --}}
        <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2220%22 height=%2220%22><rect width=%2210%22 height=%2210%22 fill=%22%23ccc%22/><rect x=%2210%22 y=%2210%22 width=%2210%22 height=%2210%22 fill=%22%23ccc%22/></svg>');"></div>

        {{-- Fabric.js Canvas Container --}}
        {{-- Crop overlay is now handled entirely within Fabric.js canvas --}}
        <div
            x-ref="canvasContainer"
            class="relative"
        >
            <canvas x-ref="fabricCanvas"></canvas>
        </div>

        {{-- Canvas Loading Indicator --}}
        <div
            x-show="isCanvasLoading"
            class="absolute inset-0 flex items-center justify-center bg-white/80 dark:bg-gray-900/80"
        >
            <x-filament::loading-indicator class="w-10 h-10" />
        </div>
    </div>

    {{-- Tool Options Panel (Right Sidebar) --}}
    <div class="w-72 border-l border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-y-auto shrink-0">
        {{-- Crop Options --}}
        <div x-show="activeTool === 'crop'" class="p-4 space-y-6">
            @include('filament-image-editor::components.panels.crop')
        </div>

        {{-- Filter Options --}}
        <div x-show="activeTool === 'filter'" x-cloak class="p-4 space-y-6">
            @include('filament-image-editor::components.panels.filter')
        </div>

        {{-- Draw Options --}}
        <div x-show="activeTool === 'draw'" x-cloak class="p-4 space-y-6">
            @include('filament-image-editor::components.panels.draw')
        </div>
    </div>
</div>

{{-- Zoom Controls (Footer) --}}
<div class="flex items-center justify-center gap-4 px-6 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 shrink-0">
    <button
        type="button"
        x-on:click="zoomOut()"
        class="p-2 text-gray-600 rounded-lg dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
    >
        <x-filament::icon icon="heroicon-o-minus" class="w-5 h-5" />
    </button>

    <select
        x-model="zoomLevel"
        x-on:change="setZoom($event.target.value)"
        class="px-3 py-1.5 text-sm bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg"
    >
        <option value="fit">{{ __('filament-image-editor::editor.zoom.fit') }}</option>
        <option value="0.5">50%</option>
        <option value="1">100%</option>
        <option value="2">200%</option>
    </select>

    <button
        type="button"
        x-on:click="zoomIn()"
        class="p-2 text-gray-600 rounded-lg dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
    >
        <x-filament::icon icon="heroicon-o-plus" class="w-5 h-5" />
    </button>
</div>

{{-- Confirmation Dialog for Discard Changes --}}
<div
    x-show="showDiscardDialog"
    x-cloak
    x-transition:enter="ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/75"
    x-on:click.self="showDiscardDialog = false"
>
    <div
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="w-full max-w-md p-6 mx-4 bg-white rounded-xl dark:bg-gray-800 shadow-xl"
    >
        <div class="flex items-start gap-4">
            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-danger-100 dark:bg-danger-500/20 shrink-0">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-5 h-5 text-danger-600 dark:text-danger-400" />
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('filament-image-editor::editor.dialogs.discard.title') }}
                </h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('filament-image-editor::editor.dialogs.discard.message') }}
                </p>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
            <x-filament::button
                type="button"
                x-on:click="showDiscardDialog = false"
                color="gray"
            >
                {{ __('filament-image-editor::editor.dialogs.discard.cancel') }}
            </x-filament::button>

            <x-filament::button
                type="button"
                x-on:click="showDiscardDialog = false; closeEditor()"
                color="danger"
            >
                {{ __('filament-image-editor::editor.dialogs.discard.confirm') }}
            </x-filament::button>
        </div>
    </div>
</div>

{{-- Confirmation Dialog for Reset --}}
<div
    x-show="showResetDialog"
    x-cloak
    x-transition:enter="ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/75"
    x-on:click.self="showResetDialog = false"
>
    <div
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="w-full max-w-md p-6 mx-4 bg-white rounded-xl dark:bg-gray-800 shadow-xl"
    >
        <div class="flex items-start gap-4">
            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-warning-100 dark:bg-warning-500/20 shrink-0">
                <x-filament::icon icon="heroicon-o-arrow-path" class="w-5 h-5 text-warning-600 dark:text-warning-400" />
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('filament-image-editor::editor.dialogs.reset.title') }}
                </h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('filament-image-editor::editor.dialogs.reset.message') }}
                </p>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
            <x-filament::button
                type="button"
                x-on:click="showResetDialog = false"
                color="gray"
            >
                {{ __('filament-image-editor::editor.dialogs.reset.cancel') }}
            </x-filament::button>

            <x-filament::button
                type="button"
                x-on:click="showResetDialog = false; resetToOriginal()"
                color="warning"
            >
                {{ __('filament-image-editor::editor.dialogs.reset.confirm') }}
            </x-filament::button>
        </div>
    </div>
</div>
