<?php

namespace Pjedesigns\FilamentImageEditor;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Livewire;
use Pjedesigns\FilamentImageEditor\Livewire\ImageEditorModal;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentImageEditorServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-image-editor';

    public static string $viewNamespace = 'filament-image-editor';

    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-image-editor')
            ->hasTranslations()
            ->hasViews(static::$viewNamespace)
            ->hasAssets()
            ->hasConfigFile();

        // Register publishable tests
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../tests/Feature' => base_path('tests/Feature/FilamentImageEditor'),
            ], 'filament-image-editor-tests');
        }
    }

    public function packageRegistered(): void
    {
        //
    }

    public function packageBooted(): void
    {
        // Register Livewire component
        Livewire::component('image-editor-modal', ImageEditorModal::class);

        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );
    }

    protected function getAssetPackageName(): string
    {
        return 'pjedesigns/filament-image-editor';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        $packageDist = __DIR__.'/../resources/dist';

        $manifestPath = $packageDist.'/manifest.json';

        $manifest = is_file($manifestPath)
            ? json_decode(file_get_contents($manifestPath), true)
            : [];

        $js = $manifest['js'] ?? 'filament-image-editor.js';
        $css = $manifest['css'] ?? 'filament-image-editor.css';

        return [
            AlpineComponent::make('image-editor-component', $packageDist.'/'.$js),
            Css::make('filament-image-editor-styles', $packageDist.'/'.$css),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [
            'imageEditor' => [
                'config' => config('filament-image-editor'),
            ],
        ];
    }
}
