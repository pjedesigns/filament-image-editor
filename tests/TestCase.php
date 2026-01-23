<?php

namespace Pjedesigns\FilamentImageEditor\Tests;

use Filament\FilamentServiceProvider;
use Filament\Support\SupportServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Pjedesigns\FilamentImageEditor\FilamentImageEditorServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Share an empty error bag with views to avoid Livewire issues
        $errorBag = new ViewErrorBag;
        $errorBag->put('default', new MessageBag);
        View::share('errors', $errorBag);
    }

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            FilamentServiceProvider::class,
            FilamentImageEditorServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        // Set app key for encryption
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        // Database config
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Storage config - use $app['config'] to ensure proper bootstrapping
        $app['config']->set('filesystems.default', 'public');
        $app['config']->set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => sys_get_temp_dir().'/filament-image-editor-tests/public',
            'url' => '/storage',
            'visibility' => 'public',
        ]);
        $app['config']->set('filesystems.disks.local', [
            'driver' => 'local',
            'root' => sys_get_temp_dir().'/filament-image-editor-tests/local',
        ]);

        // Package config defaults
        $app['config']->set('filament-image-editor.storage.disk', 'public');
        $app['config']->set('filament-image-editor.storage.directory', 'images');
        $app['config']->set('filament-image-editor.storage.visibility', 'public');
        $app['config']->set('filament-image-editor.output.format', 'jpeg');
        $app['config']->set('filament-image-editor.output.quality', 0.92);
        $app['config']->set('filament-image-editor.tools', ['crop', 'filter', 'draw']);

        // Livewire config
        $app['config']->set('livewire.class_namespace', 'Pjedesigns\\FilamentImageEditor\\Livewire');
    }
}
