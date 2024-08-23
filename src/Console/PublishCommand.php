<?php

declare(strict_types=1);

namespace InfinityUiKit\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

final class PublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'buk:publish {component}
                            {--view : Publish only the view of the component}
                            {--class : Publish only the class of the component}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish a component\'s view and class.';

    public function handle(Filesystem $filesystem): int
    {
        $components = config('infinity-ui-kit.components');
        $alias = $this->argument('component');

        if (! $component = $components[$alias] ?? null) {
            $this->error("Cannot find the given [$alias] component.");

            return 1;
        }

        $class = str_replace('InfinityUiKit\\Components\\', '', $component);
        $view = str_replace(['_', '.-'], ['-', '/'], Str::snake(str_replace('\\', '.', $class))).'.blade.php';

        // Publish the view
        if ($this->option('view') || ! $this->option('class')) {
            $originalView = __DIR__.'/../../resources/views/components/'.$view;
            $publishedView = $this->laravel->resourcePath('views/vendor/infinity-ui-kit/components/'.$view);
            $path = Str::beforeLast($publishedView, '/');

            if (! $this->option('force') && $filesystem->exists($publishedView)) {
                $this->error("The view at [$publishedView] already exists.");

                return 1;
            }

            $filesystem->ensureDirectoryExists($path);

            $filesystem->copy($originalView, $publishedView);

            $this->info('Successfully published the component view!');
        }

        // Publish the class
        if ($this->option('class') || ! $this->option('view')) {
            $path = $this->laravel->basePath('app/View/Components');
            $destination = $path.'/'.str_replace('\\', '/', $class).'.php';

            if (! $this->option('force') && $filesystem->exists($destination)) {
                $this->error("The class at [$destination] already exists.");

                return 1;
            }

            $filesystem->ensureDirectoryExists(Str::beforeLast($destination, '/'));

            $stub = $filesystem->get(__DIR__.'/stubs/Component.stub');
            $namespace = Str::beforeLast($class, '\\');
            $name = Str::afterLast($class, '\\');
            $alias = 'Original'.$name;

            $stub = str_replace(
                ['{{ namespace }}', '{{ name }}', '{{ parent }}', '{{ alias }}'],
                [$namespace, $name, $component, $alias],
                $stub,
            );

            $filesystem->put($destination, $stub);

            $this->info('Successfully published the component class!');

            // Update config entry of component to new class.
            if ($filesystem->missing($config = $this->laravel->configPath('infinity-ui-kit.php'))) {
                $this->call('vendor:publish', ['--tag' => 'infinity-ui-kit-config']);
            }

            $originalConfig = $filesystem->get($config);

            $modifiedConfig = str_replace($component, 'App\\View\\Components\\'.$class, $originalConfig);

            $filesystem->put($config, $modifiedConfig);
        }

        // Publish CSS files if the --css option is provided
        if ($this->option('css')) {
            $originalCssPath = __DIR__.'/../../resources/css/'.$alias;
            $publishedCssPath = $this->laravel->resourcePath('css/vendor/infinity-ui-kit/'.$alias);

            // Check if the CSS files already exist and if force option is not provided
            if (! $this->option('force') && $filesystem->exists($publishedCssPath)) {
                $this->error("The CSS files at [$publishedCssPath] already exist.");

                return 1;
            }

            // Ensure the directory exists
            $filesystem->ensureDirectoryExists($publishedCssPath);

            // Copy CSS files to the target location
            $filesystem->copyDirectory($originalCssPath, $publishedCssPath);

            $this->info('Successfully published the component CSS files!');
        }

        // Publish JS files if the --js option is provided
        if ($this->option('js')) {
            $originalJsPath = __DIR__.'/../../resources/js/'.$alias;
            $publishedJsPath = $this->laravel->resourcePath('js/vendor/infinity-ui-kit/'.$alias);

            // Check if the JS files already exist and if the force option is not provided
            if (! $this->option('force') && $filesystem->exists($publishedJsPath)) {
                $this->error("The JS files at [$publishedJsPath] already exist.");

                return 1;
            }

            // Ensure the directory exists
            $filesystem->ensureDirectoryExists($publishedJsPath);

            // Copy JS files to the target location
            $filesystem->copyDirectory($originalJsPath, $publishedJsPath);

            $this->info('Successfully published the component JS files!');
        }

        // Publish assets (e.g., images, fonts)
        if ($this->option('asset')) {
            $originalAssetPath = __DIR__.'/../../public/'.$alias;
            $publishedAssetPath = $this->laravel->publicPath('vendor/infinity-ui-kit/'.$alias);

            if (! $this->option('force') && $filesystem->exists($publishedAssetPath)) {
                $this->error("The asset at [$publishedAssetPath] already exists.");

                return 1;
            }

            $filesystem->ensureDirectoryExists($publishedAssetPath);
            $filesystem->copyDirectory($originalAssetPath, $publishedAssetPath);

            $this->info('Successfully published the component assets!');
        }

        return 0;
    }
}