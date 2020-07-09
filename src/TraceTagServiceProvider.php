<?php declare(strict_types=1);

namespace Bonsi\TraceTag;

use Illuminate\Container\Container;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Support\Facades\Log;
use \Illuminate\Support\ServiceProvider;
use Bonsi\TraceTag\Generators\RandomIntGenerator;
use Bonsi\TraceTag\Middleware\TraceTagMiddleware;

/**
 * Class TraceTagServiceProvider
 *
 * @package Bonsi\TraceTag
 */
class TraceTagServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $source = realpath(__DIR__ . '/../config/trace-tag.php');
        $this->publishes([$source => config_path('trace-tag.php')]);
        $this->mergeConfigFrom($source, 'trace-tag');

        if (config('trace-tag.middleware.enabled', false))
        {
            if ($this->isLumenApp())
            {
                Log::warning(sprintf('%s middleware needs to be registered in app.php for an Lumen app'));
            }
            else
            {
                $kernel = $this->app[Kernel::class];
                $kernel->pushMiddleware(TraceTagMiddleware::class);
            }
        }
    }

    /**
     * Check against the app version to determine whether or not the app is Laravel or Lumen.
     *
     */
    private function isLumenApp(): bool
    {
        return stripos(strtolower($this->app->version()), 'lumen') !== false;
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        Log::getLogger()->pushProcessor(new \Bonsi\TraceTag\Integrations\MonologProcessor);

        $this->app->singleton('tracetag', function(Container $app) {
            $generatorClass = $app->config->get('tracetag.generator', RandomIntGenerator::class);
            return new TraceTag(new $generatorClass);
        });

        $this->app->alias('tracetag', TraceTag::class);
    }

}
