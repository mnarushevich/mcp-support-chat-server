<?php

declare(strict_types=1);

namespace App\Core;

use Exception;
use ReflectionClass;
use ReflectionException;

class Container
{
    private array $bindings = [];
    
    private array $instances = [];

    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function singleton(string $abstract, callable $factory): void
    {
        $this->instances[$abstract] = $factory($this);
    }

    /**
     * @throws ReflectionException
     */
    public function make(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]($this);
        }

        return $this->autoResolve($abstract);
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function autoResolve(string $class)
    {
        try {
            $reflector = new ReflectionClass($class);
        } catch (ReflectionException $reflectionException) {
            throw new Exception(sprintf('Class [%s] does not exist or is not accessible.', $class), 0, $reflectionException);
        }

        if (! $reflector->isInstantiable()) {
            throw new Exception(sprintf('Class [%s] is not instantiable.', $class));
        }

        $constructor = $reflector->getConstructor();
        if (! $constructor) {
            return new $class;
        }

        $params = $constructor->getParameters();
        $dependencies = [];

        foreach ($params as $param) {
            $dependencyClass = $param->getType()?->getName();
            if ($dependencyClass === null) {
                throw new Exception(sprintf('Unresolvable dependency for [%s]', $class));
            }
            
            $dependencies[] = $this->make($dependencyClass);
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
