<?php

namespace Litepie\Database\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ModelMakeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'litepie:make-model {name : The name of the model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Eloquent model with Litepie traits';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');
        
        // Get the fully qualified class name
        $className = $this->qualifyClass($name);
        
        // Get the path where the model should be created
        $path = $this->getPath($className);
        
        // Check if the model already exists
        if ($this->files->exists($path)) {
            $this->error('Model already exists!');
            return 1;
        }

        // Make directory if it doesn't exist
        $this->makeDirectory($path);

        // Generate the model content
        $stub = $this->getStub();
        $content = $this->buildClass($className, $stub);

        // Write the file
        $this->files->put($path, $content);

        $this->info('Model created successfully!');
        $this->line("<info>Created Model:</info> {$className}");

        return 0;
    }

    /**
     * Get the stub file content.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->files->get(__DIR__.'/stubs/model.stub');
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @param  string  $stub
     * @return string
     */
    protected function buildClass($name, $stub)
    {
        $namespace = $this->getNamespace($name);
        $class = $this->getClassName($name);

        return str_replace(
            ['DummyNamespace', 'DummyClass'],
            [$namespace, $class],
            $stub
        );
    }

    /**
     * Get the full namespace for the given class name.
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        $name = ltrim($name, '\\/');

        $rootNamespace = $this->laravel->getNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        return $this->getDefaultNamespace($rootNamespace).'\\'.str_replace('/', '\\', $name);
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'Models';
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->laravel->getNamespace(), '', $name);

        return $this->laravel['path'].'/'.str_replace('\\', '/', $name).'.php';
    }

    /**
     * Get the namespace from the full class name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getNamespace($name)
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Get the class name from the full class name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getClassName($name)
    {
        return class_basename($name);
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }
}
