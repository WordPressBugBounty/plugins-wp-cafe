<?php

namespace WpCafe\Container;

use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use WpCafe\Container\Exception\Dependency_Has_No_Default_Value_Exception;
use WpCafe\Container\Exception\Dependency_Is_Not_Instantiable_Exception;

class Container {

    /**
     * Store all services
     *
     * @var array
     */
    protected $services = [];

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface|ReflectionException Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get( string $key ): object {
        if ( ! $this->has( $key ) ) {
            // translators: %s is the name of the class that is not instantiable.
            throw new \Exception( sprintf( esc_html__( 'Service provider %s not found.', 'wp-cafe' ), esc_html( $key ) ) );

        }

        $class_name = $this->services[ $key ];
        return $this->resolve( $class_name );
    }

    /**
     * Sets an entry of the container by its identifier
     *
     * @param string $id
     * @param string $class_name
     *
     * @return void
     */
    public function add_service_provider( string $key, $class_name ): void {
        $this->services[ $key ] = $class_name;
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($key)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $key Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has( string $key ): bool {
        return array_key_exists( $key, $this->services );
    }


    /**
     * Resolves the service object by its name.
     *
     * @param string $class_name
     *
     * @throws Dependency_Has_No_Default_Value_Exception
     * @throws Dependency_Is_Not_Instantiable_Exception
     * @throws ReflectionException|Exception
     *
     * @return object
     */
    public function resolve( string $class_name ): object {
        if ( ! class_exists( $class_name ) ) {
            // translators: %s is the name of the class that is not instantiable.
            throw new Exception( sprintf( esc_html__( 'Class: %s does not exist.', 'wp-cafe' ), esc_html( $class_name ) ) );

        }

        $reflection_class = new ReflectionClass( $class_name );

        if ( ! $reflection_class->isInstantiable() ) {
            throw new Dependency_Is_Not_Instantiable_Exception(
                // translators: %s is the name of the class that is not instantiable.
                sprintf( esc_html__( 'Class: %s is not instantiable.', 'wp-cafe' ), esc_html( $class_name ) )
            );
        }

        if ( null === $reflection_class->getConstructor() ) {
            return $reflection_class->newInstance();
        }

        $parameters = $reflection_class->getConstructor()->getParameters();

        $dependencies = $this->build_dependencies( $parameters );

        return $reflection_class->newInstanceArgs( $dependencies );
    }


    /**
     * Builds the dependencies for the given parameters.
     *
     * @param ReflectionParameter[] $parameters
     *
     * @throws ReflectionException
     * @throws Dependency_Has_No_Default_Value_Exception
     *
     * @return array
     */
    public function build_dependencies( array $parameters ): array {
        $dependencies = [];

        foreach ( $parameters as $parameter ) {
            $dependency = $parameter->getType() ? $parameter->getType()->getName() : null;

            if ( is_null( $dependency ) ) {
                if ( $parameter->isDefaultValueAvailable() ) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Dependency_Has_No_Default_Value_Exception(
                        // translators: %s is the name of the class that is not instantiable.
                        sprintf( esc_html__( 'Class: %s dependency cannot be resolved.', 'wp-cafe' ), esc_html( $parameter->name ) )
                    );
                }
            } else {
                $dependencies[] = $this->get( $dependency );
            }
        }

        return $dependencies;
    }
}
