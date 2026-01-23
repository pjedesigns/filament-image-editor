@php
    $statePath = $getStatePath();
    $imageUrl = $getImageUrl();
    $originalImageUrl = $getOriginalImageUrl();
    $config = $getEditorConfig();
    $acceptedTypes = $getAcceptedFileTypesString();
    $maxFileSize = $getMaxFileSizeBytes();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-load
        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('image-editor-component', 'pjedesigns/filament-image-editor') }}"
        x-data="imageEditorComponent({
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            statePath: '{{ $statePath }}',
            config: @js($config),
            imageUrl: @js($imageUrl),
            originalImageUrl: @js($originalImageUrl),
        })"
        x-on:open-image-editor.window="handleExternalOpen($event.detail)"
        class="filament-image-editor"
    >
        {{-- Dropzone / Upload Area --}}
        <div
            x-show="!hasImage"
            x-on:dragover.prevent="isDragging = true"
            x-on:dragleave.prevent="isDragging = false"
            x-on:drop.prevent="handleDrop($event)"
            x-on:click="$refs.fileInput.click()"
            :class="{ 'ring-2 ring-primary-500 border-primary-500': isDragging }"
            class="relative flex flex-col items-center justify-center w-full min-h-[200px] px-6 py-10 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 transition-colors"
        >
            <div class="flex flex-col items-center justify-center text-center">
                <x-filament::icon
                    icon="heroicon-o-photo"
                    class="w-12 h-12 mb-4 text-gray-400 dark:text-gray-500"
                />
                <p class="mb-2 text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-semibold text-primary-600 dark:text-primary-400">
                        {{ __('filament-image-editor::editor.upload.click') }}
                    </span>
                    {{ __('filament-image-editor::editor.upload.or_drag') }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-500">
                    {{ __('filament-image-editor::editor.upload.accepted_types') }}
                </p>
            </div>

            <input
                x-ref="fileInput"
                type="file"
                accept="{{ $acceptedTypes }}"
                class="sr-only"
                x-on:change="handleFileSelect($event)"
            />
        </div>

        {{-- Image Preview with Edit Button --}}
        <div
            x-show="hasImage"
            x-cloak
            class="relative group"
        >
            <div class="relative overflow-hidden bg-gray-100 rounded-lg dark:bg-gray-800 aspect-video">
                <img
                    x-ref="previewImage"
                    :src="previewUrl"
                    alt="Preview"
                    class="object-contain w-full h-full"
                />

                {{-- Overlay with Edit/Remove buttons --}}
                <div class="absolute inset-0 flex items-center justify-center gap-2 transition-opacity bg-black/50 opacity-0 group-hover:opacity-100">
                    <x-filament::button
                        type="button"
                        x-on:click="openEditor()"
                        icon="heroicon-o-pencil-square"
                        size="sm"
                    >
                        {{ __('filament-image-editor::editor.buttons.edit') }}
                    </x-filament::button>

                    <x-filament::button
                        type="button"
                        x-on:click="removeImage()"
                        icon="heroicon-o-trash"
                        size="sm"
                        color="danger"
                    >
                        {{ __('filament-image-editor::editor.buttons.remove') }}
                    </x-filament::button>
                </div>
            </div>

            {{-- Change Image Link --}}
            <div class="mt-2 text-center">
                <button
                    type="button"
                    x-on:click="$refs.fileInput.click()"
                    class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                >
                    {{ __('filament-image-editor::editor.buttons.change') }}
                </button>
            </div>
        </div>

        {{-- Loading State --}}
        <div
            x-show="isLoading"
            x-cloak
            class="absolute inset-0 flex items-center justify-center bg-white/80 dark:bg-gray-900/80 rounded-lg"
        >
            <x-filament::loading-indicator class="w-8 h-8" />
        </div>

        {{-- Editor Modal --}}
        <div
            x-show="isEditorOpen"
            x-cloak
            x-trap="isEditorOpen && !isEditingText"
            x-on:keydown.escape.window="handleEscape()"
            x-effect="document.body.style.overflow = isEditorOpen ? 'hidden' : ''"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto"
        >
            {{-- Backdrop --}}
            <div
                x-show="isEditorOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-gray-900/75"
                x-on:click="confirmClose()"
            ></div>

            {{-- Modal Content --}}
            <div
                x-show="isEditorOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="relative w-full max-w-7xl max-h-[95vh] mx-4 bg-white dark:bg-gray-900 rounded-xl shadow-xl overflow-hidden flex flex-col"
            >
                @include('filament-image-editor::components.modal')
            </div>
        </div>
    </div>
</x-dynamic-component>
