<?php

declare(strict_types=1);

namespace InfinityUiKit;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use InfinityUiKit\Console\PublishCommand;
use Livewire\Livewire;
use Illuminate\Support\Str;
use Tooinfinity\InfinityUiKit\InfinityUiKit;

class InfinityUiKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/infinity-ui-kit.php', 'infinity-ui-kit');

        if ($this->app->runningInConsole()) {
            $this->commands([
                PublishCommand::class,
            ]);
        }
    }

    public function boot(): void
    {
        $this->bootResources();
        $this->bootBladeComponents();
        $this->bootLivewireComponents();
        $this->bootDirectives();
        $this->bootPublishing();
    }

    private function bootResources(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'infinity-ui-kit');
    }

    private function bootBladeComponents(): void
    {
        $this->callAfterResolving(BladeCompiler::class, function (BladeCompiler $blade) {
            $prefix = config('infinity-ui-kit.prefix', '');
            $assets = config('infinity-ui-kit.assets', []);

            /** @var BladeComponent $component */
            foreach (config('infinity-ui-kit.components', []) as $alias => $component) {
                $blade->component($component, $alias, $prefix);

                $this->registerAssets($component, $assets);
            }
        });
    }

    private function bootLivewireComponents(): void
    {
        // Skip if Livewire isn't installed.
        if (!class_exists(Livewire::class)) {
            return;
        }

        $prefix = config('infinity-ui-kit.prefix', '');
        $assets = config('infinity-ui-kit.assets', []);

        /** @var LivewireComponent $component */
        foreach (config('infinity-ui-kit.livewire', []) as $alias => $component) {
            $alias = $prefix ? "$prefix-$alias" : $alias;

            Livewire::component($alias, $component);

            $this->registerAssets($component, $assets);
        }
    }

    private function registerAssets($component, array $assets): void
    {
        foreach ($component::assets() as $asset) {
            $files = (array)($assets[$asset] ?? []);

            collect($files)->filter(function (string $file) {
                return Str::endsWith($file, '.css');
            })->each(function (string $style) {
                InfinityUiKit::addStyle($style);
            });

            collect($files)->filter(function (string $file) {
                return Str::endsWith($file, '.js');
            })->each(function (string $script) {
                InfinityUiKit::addScript($script);
            });
        }
    }

    private function bootDirectives(): void
    {
        Blade::directive('bukStyles', function (string $expression) {
            return "<?php echo InifinityUiKit\\InifinityUiKit::outputStyles($expression); ?>";
        });

        Blade::directive('bukScripts', function (string $expression) {
            return "<?php echo InifinityUiKit\\InifinityUiKit::outputScripts($expression); ?>";
        });
    }

    private function bootPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/infinity-ui-kit.php' => $this->app->configPath('infinity-ui-kit.php'),
            ], 'blade-ui-kit-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => $this->app->resourcePath('views/vendor/infinity-ui-kit'),
            ], 'infinity-ui-kit-views');
        }
    }
}
