<?php
namespace Czim\Repository\Console\Commands;

use Czim\Repository\BaseRepository;
use Czim\Repository\RepositoryServiceProvider;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;

class MakeRepositoryCommand extends GeneratorCommand
{

    /**
     * @var string
     */
    protected $name = 'make:repository';

    /**
     * @var string
     */
    protected $description = 'Create a new Eloquent model repository class';

    /**
     * @var string
     */
    protected $namespace = 'App\\Repositories';

    /**
     * @var string
     */
    protected $base = BaseRepository::class;

    /**
     * @var string
     */
    protected $suffix = 'Repository';

    /**
     * @var string
     */
    protected $models = 'App';

    /**
     * @var string
     */
    protected $type = 'Repository';


    /**
     * @param Filesystem $fileSystem
     */
    public function __construct(Filesystem $fileSystem)
    {
        $this->loadConfig();

        parent::__construct($fileSystem);
    }


    /**
     * Load the configuration for the command.
     */
    protected function loadConfig()
    {
        $this->namespace = config('repository.generate.namespace', $this->namespace);
        $this->base      = config('repository.generate.base', $this->base);
        $this->suffix    = config('repository.generate.suffix', $this->suffix);
        $this->models    = config('repository.generate.models', $this->models);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return RepositoryServiceProvider::$packagePath . '/stubs/repository.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     * @return string
     */
    public function getDefaultNamespace($rootNamespace)
    {
        return $this->namespace;
    }

    /**
     * Build the class with the given name.
     *
     * @param  string $name
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        $modelName = $this->getModelClass($name);

        $this->replaceModelNamespace($stub, $modelName)
             ->replaceModelClass($stub, $modelName)
             ->replaceBaseRepositoryNamespace($stub, $this->base)
             ->replaceBaseRepositoryClass($stub, $this->base);

        return $stub;
    }

    /**
     * Replace the probable namespace for the given stub.
     *
     * @param  string $stub
     * @param  string $name
     * @return $this
     */
    protected function replaceModelNamespace(&$stub, $name)
    {
        $stub = str_replace('DummyModelNamespace', $name, $stub);

        return $this;
    }

    /**
     * Replace the probable model class name for the given stub.
     *
     * @param  string $stub
     * @param  string $name
     * @return $this
     */
    protected function replaceModelClass(&$stub, $name)
    {
        $names = explode('\\', $name);
        $class = array_pop($names);

        $stub = str_replace('DummyModelClass', $class, $stub);

        return $this;
    }

    /**
     * Get the class name of the probable associated model.
     *
     * @param $name
     * @return string
     */
    protected function getModelClass($name)
    {
        $modelClass = $this->getModelNameInput();

        // Generate the model class from the repository class name if not explicitly set
        if ( ! $modelClass) {

            $repositoryClass = str_replace($this->getNamespace($name) . '\\', '', $name);
            $class           = str_replace($this->suffix, '', $repositoryClass);

            $modelClass = str_singular($class);
        }

        // Append the expected models namespace if not namespaced yet
        if (false === strpos($modelClass, '\\')) {
            $modelClass = "{$this->models}\\{$modelClass}";
        }

        return $modelClass;
    }

    /**
     * Replace the default base repository class namespace for the given stub.
     *
     * @param  string $stub
     * @param  string $name
     * @return $this
     */
    protected function replaceBaseRepositoryNamespace(&$stub, $name)
    {
        $stub = str_replace('BaseRepositoryNamespace', $name, $stub);

        return $this;
    }

    /**
     * Replace the default base repository class name for the given stub.
     *
     * @param  string $stub
     * @param  string $name
     * @return $this
     */
    protected function replaceBaseRepositoryClass(&$stub, $name)
    {
        $baseClass = str_replace($this->getNamespace($name) . '\\', '', $name);
        $stub      = str_replace('BaseRepositoryClass', $baseClass, $stub);

        return $this;
    }

    /**
     * Get the desired model class name from the input.
     *
     * @return string
     */
    protected function getModelNameInput()
    {
        return trim($this->argument('model'));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the repository class'],
            ['model', InputArgument::OPTIONAL, 'The name of the model class'],
        ];
    }

}
