<?php

namespace Pjedesigns\FilamentImageEditor\Livewire;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImageEditorModal extends Component
{
    use WithFileUploads;

    public ?string $source = null;

    public array $config = [];

    public $editedImage = null;

    public bool $isOpen = false;

    public ?string $originalFilename = null;

    public function mount(?string $source = null, array $config = []): void
    {
        $this->source = $source;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    protected function getDefaultConfig(): array
    {
        return config('filament-image-editor', [
            'tools' => ['crop', 'filter', 'draw'],
            'openOnSelect' => true,
            'modalSize' => '6xl',
        ]);
    }

    #[On('open-image-editor')]
    public function open(array $data = []): void
    {
        if (isset($data['source'])) {
            $this->source = $data['source'];
        }

        if (isset($data['config'])) {
            $this->config = array_merge($this->config, $data['config']);
        }

        if (isset($data['originalFilename'])) {
            $this->originalFilename = $data['originalFilename'];
        }

        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function save(string $imageData): void
    {
        // Decode base64 image data
        $imageData = preg_replace('#^data:image/\w+;base64,#i', '', $imageData);
        $imageData = base64_decode($imageData, strict: true);

        if ($imageData === false || empty($imageData)) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to process image data',
            ]);

            return;
        }

        // Generate filename
        $filename = $this->generateFilename();
        $directory = $this->config['storage']['directory'] ?? 'images';
        $path = trim($directory, '/').'/'.$filename;

        // Save to storage
        $disk = $this->config['storage']['disk'] ?? 'public';
        Storage::disk($disk)->put($path, $imageData, $this->config['storage']['visibility'] ?? 'public');

        // Get URL
        $url = Storage::disk($disk)->url($path);

        // Emit event with result
        $this->dispatch('image-edited', [
            'path' => $path,
            'url' => $url,
            'disk' => $disk,
        ]);

        $this->editedImage = $url;
        $this->close();
    }

    protected function generateFilename(): string
    {
        $format = $this->config['output']['format'] ?? 'jpeg';
        $ext = $format === 'jpeg' ? 'jpg' : $format;

        $preserveFilenames = $this->config['preserveFilenames'] ?? false;

        if ($preserveFilenames && $this->originalFilename) {
            $baseName = pathinfo($this->originalFilename, PATHINFO_FILENAME);
            $baseName = Str::slug($baseName);

            return $baseName.'.'.$ext;
        }

        return Str::uuid().'.'.$ext;
    }

    public function render()
    {
        return view('filament-image-editor::livewire.image-editor-modal');
    }
}
