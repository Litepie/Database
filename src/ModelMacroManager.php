<?php

namespace Litepie\Database;

use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReflectionFunction;

class ModelMacroManager
{
    /**
     * Collection of registered macros.
     *
     * @var array
     */
    private array $macros = [];

    /**
     * Collection of global macros that apply to all models.
     *
     * @var array
     */
    private array $globalMacros = [];

    public function __construct()
    {
        $this->registerDefaultMacros();
    }

    /**
     * Register default macros for all models.
     *
     * @return void
     */
    protected function registerDefaultMacros(): void
    {
        // Advanced search macro
        Builder::macro('search', function (string $term, array $columns = []) {
            $model = $this->getModel();
            $searchColumns = !empty($columns) ? $columns : ($model->getSearchFields() ?? []);
            
            if (empty($searchColumns)) {
                return $this;
            }

            return $this->where(function ($query) use ($term, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $query->orWhere($column, 'LIKE', "%{$term}%");
                }
            });
        });

        // Full-text search macro
        Builder::macro('fullTextSearch', function (string $term, array $columns = []) {
            $model = $this->getModel();
            $searchColumns = !empty($columns) ? $columns : ($model->getFullTextSearchFields() ?? []);
            
            if (empty($searchColumns)) {
                return $this;
            }

            $columnsString = implode(',', $searchColumns);
            return $this->whereRaw("MATCH({$columnsString}) AGAINST(? IN NATURAL LANGUAGE MODE)", [$term]);
        });

        // Advanced filtering macro
        Builder::macro('filter', function (array $filters) {
            foreach ($filters as $field => $value) {
                if (is_null($value) || $value === '') {
                    continue;
                }

                if (is_array($value)) {
                    $this->whereIn($field, $value);
                } elseif (str_contains($field, ':')) {
                    [$field, $operator] = explode(':', $field, 2);
                    $this->where($field, $operator, $value);
                } else {
                    $this->where($field, $value);
                }
            }

            return $this;
        });

        // Batch processing macro
        Builder::macro('batch', function (int $size, Closure $callback) {
            $this->chunk($size, function (Collection $models) use ($callback) {
                $callback($models);
            });
        });

        // Advanced pagination with metadata
        Builder::macro('paginateWithMeta', function (int $perPage = 15, array $columns = ['*']) {
            $paginator = $this->paginate($perPage, $columns);
            
            $paginator->appends([
                'total_records' => $this->toBase()->getCountForPagination(),
                'per_page' => $perPage,
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'has_more_pages' => $paginator->hasMorePages(),
            ]);

            return $paginator;
        });

        // Cache with tags macro
        Builder::macro('cacheWithTags', function (array $tags, int $minutes = 60, string $key = null) {
            $key = $key ?: $this->getCacheKey();
            
            return cache()->tags($tags)->remember($key, now()->addMinutes($minutes), function () {
                return $this->get();
            });
        });

        // Soft delete with reason
        Builder::macro('softDeleteWithReason', function (string $reason = null) {
            $models = $this->get();
            
            foreach ($models as $model) {
                if (method_exists($model, 'delete')) {
                    if ($reason && method_exists($model, 'setDeletionReason')) {
                        $model->setDeletionReason($reason);
                    }
                    $model->delete();
                }
            }

            return $models->count();
        });
    }

    /**
     * Get all registered macros.
     *
     * @return array
     */
    public function getAllMacros(): array
    {
        return $this->macros;
    }

    /**
     * Get all global macros.
     *
     * @return array
     */
    public function getGlobalMacros(): array
    {
        return $this->globalMacros;
    }

    /**
     * Add a macro for specific model(s).
     *
     * @param string|array $models
     * @param string $name
     * @param Closure $closure
     * @return void
     * @throws Exception
     */
    public function addMacro(string|array $models, string $name, Closure $closure): void
    {
        $models = is_array($models) ? $models : [$models];

        foreach ($models as $model) {
            $this->checkModelSubclass($model);

            if (!isset($this->macros[$name])) {
                $this->macros[$name] = [];
            }
            
            $this->macros[$name][$model] = $closure;
        }

        $this->syncMacros($name);
    }

    /**
     * Add a global macro that applies to all models.
     *
     * @param string $name
     * @param Closure $closure
     * @return void
     */
    public function addGlobalMacro(string $name, Closure $closure): void
    {
        $this->globalMacros[$name] = $closure;
        Builder::macro($name, $closure);
    }

    /**
     * Remove a macro from specific model(s).
     *
     * @param string|array $models
     * @param string $name
     * @return bool
     */
    public function removeMacro(string|array $models, string $name): bool
    {
        $models = is_array($models) ? $models : [$models];
        $removed = false;

        foreach ($models as $model) {
            $this->checkModelSubclass($model);

            if (isset($this->macros[$name][$model])) {
                unset($this->macros[$name][$model]);
                $removed = true;
            }
        }

        if ($removed) {
            if (empty($this->macros[$name])) {
                unset($this->macros[$name]);
            }
            $this->syncMacros($name);
        }

        return $removed;
    }

    /**
     * Remove a global macro.
     *
     * @param string $name
     * @return bool
     */
    public function removeGlobalMacro(string $name): bool
    {
        if (isset($this->globalMacros[$name])) {
            unset($this->globalMacros[$name]);
            return true;
        }

        return false;
    }

    /**
     * Check if a model has a specific macro.
     *
     * @param string $model
     * @param string $name
     * @return bool
     */
    public function modelHasMacro(string $model, string $name): bool
    {
        $this->checkModelSubclass($model);
        return isset($this->macros[$name][$model]) || isset($this->globalMacros[$name]);
    }

    /**
     * Get models that implement a specific macro.
     *
     * @param string $name
     * @return array
     */
    public function modelsThatImplement(string $name): array
    {
        return isset($this->macros[$name]) ? array_keys($this->macros[$name]) : [];
    }

    /**
     * Get macros for a specific model.
     *
     * @param string $model
     * @return array
     */
    public function macrosForModel(string $model): array
    {
        $this->checkModelSubclass($model);

        $macros = [];

        // Add model-specific macros
        foreach ($this->macros as $macro => $models) {
            if (isset($models[$model])) {
                $params = (new ReflectionFunction($this->macros[$macro][$model]))->getParameters();
                $macros[$macro] = [
                    'name' => $macro,
                    'parameters' => $params,
                    'type' => 'model-specific',
                ];
            }
        }

        // Add global macros
        foreach ($this->globalMacros as $macro => $closure) {
            $params = (new ReflectionFunction($closure))->getParameters();
            $macros[$macro] = [
                'name' => $macro,
                'parameters' => $params,
                'type' => 'global',
            ];
        }

        return $macros;
    }

    /**
     * Get macro usage statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $stats = [
            'total_macros' => count($this->macros),
            'global_macros' => count($this->globalMacros),
            'model_specific_macros' => 0,
            'models_with_macros' => [],
        ];

        foreach ($this->macros as $macro => $models) {
            $stats['model_specific_macros'] += count($models);
            foreach ($models as $model => $closure) {
                if (!in_array($model, $stats['models_with_macros'])) {
                    $stats['models_with_macros'][] = $model;
                }
            }
        }

        $stats['unique_models_count'] = count($stats['models_with_macros']);

        return $stats;
    }

    /**
     * Sync macros with the Builder.
     *
     * @param string $name
     * @return void
     */
    private function syncMacros(string $name): void
    {
        $models = $this->macros[$name] ?? [];

        if (empty($models)) {
            return;
        }

        Builder::macro($name, function (...$args) use ($name, $models) {
            $class = get_class($this->getModel());

            if (!isset($models[$class])) {
                throw new \BadMethodCallException(
                    sprintf('Call to undefined method %s::%s()', $class, $name)
                );
            }

            $closure = $models[$class]->bindTo($this->getModel());

            return call_user_func($closure, ...$args);
        });
    }

    /**
     * Check if the given class is a Model subclass.
     *
     * @param string $model
     * @return void
     * @throws Exception
     */
    private function checkModelSubclass(string $model): void
    {
        if (!is_subclass_of($model, Model::class)) {
            throw new \InvalidArgumentException(
                '$model must be a subclass of Illuminate\\Database\\Eloquent\\Model'
            );
        }
    }

    /**
     * Generate a cache key for the query.
     *
     * @return string
     */
    private function getCacheKey(): string
    {
        // This method would be called on the Builder instance
        // We'll implement it as a simple hash of the SQL and bindings
        return 'query_cache_' . md5(serialize([
            'sql' => 'placeholder',
            'bindings' => 'placeholder'
        ]));
    }
}
