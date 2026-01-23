<div>
    @if($isOpen)
        <div
            x-data="imageEditorComponent({
                state: $wire.entangle('editedImage'),
                statePath: 'editedImage',
                config: @js($config),
                imageUrl: @js($source),
            })"
            x-init="openEditor()"
            class="filament-image-editor"
        >
            {{-- The modal content is rendered by the Alpine component --}}
        </div>
    @endif
</div>
