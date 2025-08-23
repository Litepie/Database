<?php

namespace Litepie\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;
use Litepie\Database\Commands\ModelMakeCommand;
use Litepie\Database\Facades\ModelMacro;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/database.php' => config_path('litepie-database.php'),
        ], 'litepie-database-config');

        $this->configureMacros();
        $this->registerCommands();
    }

    /**
     * Configure the schema macros to be used.
     *
     * @return void
     */
    protected function configureMacros(): void
    {
        // Archive related macros
        Blueprint::macro('archivedAt', function ($column = 'archived_at', $precision = 0) {
            return $this->timestamp($column, $precision)->nullable();
        });

        Blueprint::macro('dropArchivedAt', function ($column = 'archived_at') {
            return $this->dropColumn($column);
        });

        // Audit trail macros
        Blueprint::macro('auditColumns', function () {
            $this->timestamp('created_at')->nullable();
            $this->timestamp('updated_at')->nullable();
            $this->timestamp('deleted_at')->nullable();
            $this->timestamp('archived_at')->nullable();
            $this->string('created_by')->nullable();
            $this->string('updated_by')->nullable();
            $this->string('deleted_by')->nullable();
            $this->string('archived_by')->nullable();
            return $this;
        });

        // Status column macro
        Blueprint::macro('status', function ($column = 'status', $default = 'active') {
            return $this->enum($column, ['active', 'inactive', 'pending', 'draft', 'published'])
                        ->default($default);
        });

        // UUID primary key macro
        Blueprint::macro('uuidPrimary', function ($column = 'id') {
            return $this->uuid($column)->primary();
        });

        // Slug column macro
        Blueprint::macro('slug', function ($column = 'slug', $unique = true) {
            $column = $this->string($column)->index();
            if ($unique) {
                $column->unique();
            }
            return $column;
        });

        // Position/order column macro
        Blueprint::macro('position', function ($column = 'position') {
            return $this->unsignedInteger($column)->default(0)->index();
        });

        // SEO columns macro
        Blueprint::macro('seoColumns', function () {
            $this->string('meta_title')->nullable();
            $this->text('meta_description')->nullable();
            $this->text('meta_keywords')->nullable();
            $this->string('og_title')->nullable();
            $this->text('og_description')->nullable();
            $this->string('og_image')->nullable();
            return $this;
        });

        // JSON column with index macro
        Blueprint::macro('jsonWithIndex', function ($column, $indexColumns = []) {
            $this->json($column);
            foreach ($indexColumns as $indexColumn) {
                $this->index("({$column}->>'$.{$indexColumn}')");
            }
            return $this;
        });
    }

    /**
     * Register artisan commands.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ModelMakeCommand::class,
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/database.php',
            'litepie-database'
        );

        $this->app->singleton('litepie.model-macro', function ($app) {
            return new ModelMacroManager();
        });

        $this->app->alias('litepie.model-macro', ModelMacroManager::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            'litepie.model-macro',
            ModelMacroManager::class,
        ];
    }
}
