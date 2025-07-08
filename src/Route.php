<?php

namespace Garavel\Routing;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use Garavel\Support\Str;
use InvalidArgumentException;
use Garavel\Support\Facades\Response;
use App\Providers\RouteParamsResolvers;
use Garavel\Support\Facades\JsonResponse;
use Garavel\Http\Response as HttpResponse;
use Garavel\Routing\Exceptions\UnknownController;
use Garavel\Routing\Exceptions\UnknownControllerMethod;

/**
 * Represents a route.
 */
class Route
{
	/**
	 * Route name.
	 * 
	 * @var string
	 */
	public string $name;

	/**
	 * The URI pattern responds to.
	 * 
	 * @var string
	 */
	public string $uri;

	/**
	 * Http methods route supports.
	 * 
	 * @var array
	 */
	public array $methods = [];

	/**
	 * Route expression segment requirements stack.
	 * 
	 * @var array
	 */
	public array $wheres = [];

	/**
	 * Middleware list should be applied to the route.
	 * 
	 * @var array
	 */
	public array $middlewares = [];

	/**
	 * Route controller namespace prefix.
	 * 
	 * @var string
	 */
	public string $namespace = '';

	/**
	 * Router instance.
	 * 
	 * @var Router
	 */
	public Router $router;

	/**
	 * Route URI as regular expression.
	 * 
	 * @var RouteExpression
	 */
	public RouteExpression $expression;

	/**
	 * Constructs a new Route instance.
	 *
	 * @param array $methods HTTP methods this route responds to.
	 * @param string $uri The URI pattern responds to.
	 * @param string|array|Closure $handler The route handler.
	 */
	public function __construct(
		array $methods,
		string $uri,
		public string|array|Closure $handler
	)
	{
		$this->uri = $uri;
		$this->methods = $methods;
		$this->expression = new RouteExpression( $this, $uri );
	}

	/**
	 * Sets the router instance to the route.
	 *
	 * @param Router $router The router instance.
	 * @return Route The route instance.
	 */
	public function setRouter( Router $router ): Route
	{
		$this->router = $router;
		return $this;
	}

	/**
	 * Sets the route wheres.
	 *
	 * @param array $wheres
	 * 	- key: segment name
	 * 	- value: regex pattern
	 *
	 * @return Route The route instance.
	 */
	public function setWheres( array $wheres ): Route
	{
		$this->wheres = $wheres;
		return $this;
	}

	/**
	 * Sets the route controller namespace.
	 * 
	 * @param string $ns The namespace prefix.
	 * @return Route The route instance.
	 */
	public function setNamespace( string $ns ): Route
	{
		$this->namespace = $ns;
		return $this;
	}

	/**
	 * Sets the route middlewares.
	 *
	 * @param array $middlewares The list of middlewares to be set.
	 * @return Route The route instance.
	 */
	public function setMiddlewares( array $middlewares ): Route
	{
		$this->middlewares = $middlewares;
		return $this;
	}

	/**
	 * Adds a middleware to the middleware stack.
	 *
	 * @param string $name The fully qualified name of the middleware class.
	 * @return Route The route instance.
	 */

	public function middleware( string $name ): Route
	{
		$this->middlewares[] = $name;
		return $this;
	}

	/**
	 * Sets route name.
	 *
	 * @param string $name The name of the route.
	 * @return Route The route instance.
	 */
	public function name( string $name ): Route
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * Checks if the given HTTP method is supported by this route.
	 *
	 * @param string $methodName The HTTP method name to check.
	 * @return bool True if the method is supported, false otherwise.
	 */
	public function supports( string $methodName ): bool
	{
		return in_array( $methodName, $this->methods );
	}

	/**
	 * Runs the route controller or action.
	 *
	 * This method will resolve the action controller and method, resolve
	 * arguments to their type-hinted classes, execute the action and flush
	 * the result to the output.
	 *
	 * @param Matches $matches The matches found in the given path.
	 */
	public function run( Matches $matches ): void
	{
		$this->flushActionResult(
			$this->executeAction( $matches )
		);
	}

	/**
	 * Executes the action and returns properly what returned by the action.
	 *
	 * This method will execute the action whether it is a
	 * string (controller@method), invokable class (controller),
	 * an array ([controller, method]) or a callable.
	 *
	 * @param Matches $matches The matches found in the given path.
	 * @return mixed The result returned by the action.
	 */
	private function executeAction( Matches $matches ): mixed
	{
		$handler = $this->handler;

		if( is_string( $handler ))
		{
			if( ! Str::contains( $handler, '@' ))
			{
				$handler .= '@__invoke';
			}

			[ $controllerName, $method ] = Str::split( $handler, '@' );

			return $this->executeController(
				$matches,
				$this->fullyQualifiedControllerNS( $controllerName ),
				$method
			);
		}
		else if( is_array( $handler ))
		{
			return $this->executeController( $matches, ...$handler );
		}
		else if( is_callable( $handler ))
		{
			return $this->runMiddlewares(
				$matches,
				fn() =>
					$handler(
						...$this->resolveArguments(
							$this->getParamReflections( $handler ),
							$matches
						),
						...$matches->values
					)
			);
		}
	}

	/**
	 * Takes a controller name and produces full namespace path for that controller.
	 *
	 * If the route has a namespace, this method will use that namespace.
	 * Otherwise, it will use the default namespace which is `app\Http\Controllers`.
	 *
	 * @param string $localControllerName The local name of the controller.
	 * @return string The fully qualified namespace path of the controller.
	 */
	public function fullyQualifiedControllerNS( string $localControllerName ): string
	{
		$ns = 'App\Http\Controllers';
		$ns = $this->namespace
			? "$ns\\$this->namespace\\"
			: "$ns\\";
		
		return Str::startWith( $localControllerName, $ns );
	}

	/**
	 * Executes the given controller.
	 *
	 * This method will execute the given controller whether it is
	 * a class or an invokable class.
	 *
	 * It will also resolve the given matches to their corresponding
	 * type-hinted classes.
	 *
	 * @param Matches $matches The matches found in the given path.
	 * @param string $namespace The namespace of the controller.
	 * @param string $methodName The method name of the controller.
	 * @return mixed The result returned by the controller.
	 */
	public function executeController( Matches $matches, string $namespace, string $methodName ): mixed
	{
		if( class_exists( $namespace, autoload: true ) === false )
		{
			throw new UnknownController( $namespace );
		}

		$controller = new $namespace;

		if( method_exists( $controller, $methodName ) === false )
		{
			throw new UnknownControllerMethod( $namespace, $methodName );
		}

		return $this->runMiddlewares( fn() =>
			$controller->{ $methodName }(
				...$this->resolveArguments(
					$this->getParamReflections( $controller, $methodName ),
					$matches
				),
				...$matches->values
			)
		);
	}

	/**
	 * Runs the middlewares.
	 *
	 * This method will run the registered middlewares and if there are no
	 * middlewares registered, it will execute the given final action.
	 *
	 * @param Closure $finalAction The final action to be executed.
	 * @return mixed The result returned by the final action.
	 */
	public function runMiddlewares( Closure $finalAction ): mixed
	{
		if( ! $this->middlewares )
		{
			return $finalAction();
		}

		return $this
			->router
			->resolveMiddlewares( $this->middlewares )[ 0 ]
			->run( $finalAction );
	}

	/**
	 * Returns the reflection parameters of the given callable or controller.
	 *
	 * If the given argument is a controller, it will return the parameters of the
	 * given method name. Otherwise, it will return the parameters of the given
	 * callable.
	 *
	 * @param callable|Controller $callableOrController The callable or controller
	 * @param ?string $methodName The method name if the given argument is a controller.
	 * @return array The reflection parameters.
	 */
	public function getParamReflections(
		callable|Controller $callableOrController,
		?string $methodName = null
	): array
	{
		if( $callableOrController instanceof Controller )
		{
			return ( new ReflectionClass( $callableOrController ))
				->getMethod( $methodName )
				->getParameters();
		}
		else if( is_callable( $callableOrController ))
		{
			return ( new ReflectionFunction( $callableOrController ))
				->getParameters();
		}

		return [];
	}

	/**
	 * Resolves the given parameters with the given matches.
	 *
	 * This method will try to resolve the given parameters with the given matches.
	 * 
	 * If the given parameter has a type-hinted class that has a resolver defined,
	 * it will use that resolver to resolve the parameter value.
	 * 
	 * Otherwise, it will use the value from the matches if the parameter name
	 * exists in the matches.
	 * 
	 * If the parameter name does not exist in the matches and the
	 * parameter does not have a default value, it will be null.
	 *
	 * @param array $args The parameters to resolve.
	 * @param Matches $matches The matches to use to resolve the parameters.
	 * @return array The resolved parameters.
	 */
	public function resolveArguments( array $args, Matches $matches ): array
	{
		$stack = [];
		$resolve = new RouteParamsResolvers;
		$resolve->registerDefaults()->register();

		foreach( $args as $index => $arg )
		{
			$name = $arg->getName();
			$ns = (string) $arg->getType();

			if( RouteParamsResolvers::resolves( $ns ))
			{
				$stack[] = $resolve( $ns, [ $matches->{ $name } ?? null, $matches, $name, $index ]);
			}
			else if( $ns !== '' )
			{
				throw new InvalidArgumentException( "There is no resolver defined for \"$ns\" type." );
			}
			else if( isset( $matches->{ $name }))
			{
				$stack[] = $matches->{ $name };
			}
		}

		return $stack;
	}

	/**
	 * Flushes the result to the output.
	 *
	 * If the given result is an instance of `HttpResponse`, it will call the
	 * `flush` method on that instance.
	 *
	 * If the given result is a string or numeric, it will write the result to
	 * the output using `Response::write` and flush the response.
	 *
	 * If the given result is a boolean, array or object, it will write the
	 * result to the output using `JsonResponse::write` and flush the response.
	 */
	private function flushActionResult( mixed $result ): void
	{
		if( $result instanceof HttpResponse )
		{
			$result->flush();
		}
		else if( is_string( $result ) || is_numeric( $result ))
		{
			Response::write( $result )->flush();
		}
		else if( is_bool( $result ) || is_array( $result ) || is_object( $result ))
		{
			JsonResponse::write( $result )->flush();
		}
	}

	/**
	 * Adds a where clause to the route.
	 *
	 * The pattern argument is a regular expression that will be used to
	 * match the value of the given parameter.
	 *
	 * The required argument is a boolean that indicates whether the
	 * parameter is required or not. If it is required, the route will
	 * throw an error if the parameter does not exist in the route
	 * parameters.
	 *
	 * @param string $name The parameter name.
	 * @param string $pattern The regular expression to match the value.
	 * @param ?bool $required Whether the parameter is required or not.
	 * @return Route The current route instance.
	 */
	public function where( string $name, string $pattern, ?bool $required = null ): Route
	{
		$this->wheres[ $name ] =
		[
			trim( $pattern, '/~@;%`#' ),
			$required
		];
		
		return $this;
	}

	/**
	 * Matches the given path against the route expression.
	 *
	 * This method creates a new Matches object using the provided path
	 * and the current route expression to check if the path conforms to
	 * the defined route pattern.
	 *
	 * @param string $path The path to be matched.
	 * @return Matches A Matches object containing the result of the match.
	 */
	public function match( string $path ): Matches
	{
		return new Matches( $path, $this->expression );
	}
}
