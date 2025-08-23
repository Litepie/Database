<?php

namespace Litepie\Database\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getAllMacros()
 * @method static array getGlobalMacros()
 * @method static void addMacro(string|array $models, string $name, \Closure $closure)
 * @method static void addGlobalMacro(string $name, \Closure $closure)
 * @method static bool removeMacro(string|array $models, string $name)
 * @method static bool removeGlobalMacro(string $name)
 * @method static bool modelHasMacro(string $model, string $name)
 * @method static array modelsThatImplement(string $name)
 * @method static array macrosForModel(string $model)
 * @method static array getStatistics()
 *
 * @see \Litepie\Database\ModelMacroManager
 */
class ModelMacro extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'litepie.model-macro';
    }
}
