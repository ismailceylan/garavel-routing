<?php

namespace Garavel\Routing;

use Closure;
use App\Http\Kernel;
use Garavel\Http\Request;
use Garavel\Support\Facades\Response;
use Garavel\Support\Facades\JsonResponse;
use Garavel\Routing\Exceptions\MethodNotAllowed;
use Garavel\Routing\Exceptions\NoRouteForRequest;

/**
 * Represents a router.
 */
class Router
{
	/**
	 * Group options stack.
	 * 
	 * @var RouteGroupCollection
	 */
	public RouteGroupCollection $groups;

	/**
	 * Keeps all the routes.
	 * 
	 * @var RouteCollection
	 */
	public RouteCollection $routes;

	/**
	 * Constructs a new Router instance.
	 */
	public function __construct()
	{
		$this->routes = new RouteCollection;
		$this->groups = new RouteGroupCollection;
	}

	/**
	 * Adds given route to the stack with GET and HEAD methods.
	 * 
	 * @param string $route The route to match.
	 * @param string|array|Closure $handler The handler for the route.
	 * @return Route The route that was created.
	 */
	public function get( string $route, string|array|Closure $handler ): Route
	{
		return $this->addRoute([ 'GET', 'HEAD' ], $route, $handler );
	}

	/**
	 * Adds given route to the stack with POST method.
	 * 
	 * @param string $route The route to match.
	 * @param string|array|Closure $handler The handler for the route.
	 * @return Route The route that was created.
	 */
	public function post( string $route, string|array|Closure $handler ): Route
	{
		return $this->addRoute([ 'POST' ], $route, $handler );
	}

	/**
	 * Adds given route to the stack with PUT method.
	 * 
	 * @param string $route The route to match.
	 * @param string|array|Closure $handler The handler for the route.
	 * @return Route The route that was created.
	 */
	public function put( string $route, string|array|Closure $handler ): Route
	{
		return $this->addRoute([ 'PUT' ], $route, $handler );
	}

	/**
	 * Adds given route to the stack with PATCH method.
	 * 
	 * @param string $route The route to match.
	 * @param string|array|Closure $handler The handler for the route.
	 * @return Route The route that was created.
	 */
	public function patch( string $route, string|array|Closure $handler ): Route
	{
		return $this->addRoute([ 'PATCH' ], $route, $handler );
	}

	/**
	 * Adds given route to the stack with DELETE method.
	 * 
	 * @param string $route The route to match.
	 * @param string|array|Closure $handler The handler for the route.
	 * @return Route The route that was created.
	 */
	public function delete( string $route, string|array|Closure $handler ): Route
	{
		return $this->addRoute([ 'DELETE' ], $route, $handler );
	}

	/**
	 * Adds given route to the stack with OPTIONS method.
	 * 
	 * @param string $route The route to match.
	 * @param string|array|Closure $handler The handler for the route.
	 * @return Route The route that was created.
	 */
	public function options( string $route, string|array|Closure $handler ): Route
	{
		return $this->addRoute([ 'OPTIONS' ], $route, $handler );
	}

	/**
	 * Adds a route to the stack.
	 * 
	 * @param array|string $method HTTP method(s) to match.
	 * @param string $route The route to match.
	 * @param string|array|Closure $handler The handler for the route.
	 * @return Route The route that was created.
	 */
	protected function addRoute( $method, $route, $handler ): Route
	{
		return $this->routes->add(
			$this->createRoute( $method, $route, $handler )
		);
	}

	/**
	 * Creates a new Route instance with the given parameters and group attributes.
	 *
	 * Applies group prefix, namespace, route patterns (wheres), and middlewares to the new route.
	 * Also sets the router instance on the route.
	 *
	 * @param string $method The HTTP method for the route (e.g., 'GET', 'POST')
	 * @param string $route The URI pattern for the route
	 * @param mixed $handler The route handler (callable, controller@method, etc.)
	 * @return Route The newly created Route instance
	 */
	protected function createRoute( $method, $route, $handler ): Route
	{
		$route = $this->groups->prefix( $route );

		return ( new Route( $method, $route, $handler ))
			->setNamespace( $this->groups->namespace())
			->setWheres( $this->groups->wheres())
			->setMiddlewares( $this->groups->middlewares())
			->setRouter( $this );
	}

	/**
	 * Define a route group.
	 *
	 * @param array $options Array of group attributes. The keys are 'prefix', 'namespace', 'wheres', and 'middlewares'.
	 * @param callable $callback Callback that defines the route(s) in the group.
	 * @return Router The router instance.
	 */
	public function group( array $options, callable $callback ): Router
	{
		$this->groups->push( $options );

			$callback();

		$this->groups->pop();

		return $this;
	}

	/**
	 * Matches the given request against the routes in the collection.
	 *
	 * If a route matches, it will be run. If the route does not support the
	 * request method, it will throw a MethodNotAllowed exception. If no route matches
	 * the request, it will throw a NoRouteForRequest exception.
	 *
	 * @param Request $request Request to be matched.
	 * @throws NoRouteForRequest when not found a route matches with request
	 * @throws MethodNotAllowed when found a route but the request method is not supported by them
	 */
	public function match( Request $request ): void
	{
		try
		{
			$this->routes->match( $request );
		}
		catch( NoRouteForRequest $e )
		{
			$this->responseAsNotFound( $request );
		}
		catch( MethodNotAllowed $e )
		{
			$this->responseAsMethodNotAllowed( $request, $e->getMessage());
		}
	}

	/**
	 * Sends to the client a 404 not found response.
	 *
	 * If the request is via AJAX, a JSON response is returned; otherwise, a plain text response
	 * is provided. The response includes headers indicating the not found status.
	 *
	 * @param Request $request The incoming HTTP request.
	 */
	public function responseAsNotFound( Request $request ): void
	{
		if( $request->ajax())
		{
			$responser = JsonResponse::fail( 'Unknown resource.', status: 404 );
		}
		else
		{
			$responser = Response::status( 404 )->write( 'Not found.' );
		}

		$responser->flush();
	}

    /**
     * Sends a 405 Method Not Allowed response to the client.
     *
     * If the request is via AJAX, a JSON response with the provided message
     * is returned; otherwise, a plain text response with the message is provided.
     * The response includes headers indicating the method not allowed status.
     *
     * @param Request $request The incoming HTTP request.
     * @param string $msg The message to include in the response.
     */
	public function responseAsMethodNotAllowed( Request $request, string $msg ): void
	{
		if( $request->ajax())
		{
			$responser = JsonResponse::fail( $msg, status: 405 );
		}
		else
		{
			$responser = Response::status( 405 )->write( $msg );
		}

		$responser->flush();
	}

	/**
	 * Resolves the given middleware stack and returns it.
	 *
	 * The method processes the given middleware stack and resolves any aliases or groups.
	 * It will also link each middleware to the next one in the stack.
	 *
	 * @param array $middlewares The middleware stack to resolve.
	 * @return array The resolved middleware stack.
	 */
	public function resolveMiddlewares( array $middlewares ): array
	{
		$stack = [];

		foreach( $middlewares as $mw )
		{
			// if middleware points to a middleware group
			if( array_key_exists( $mw, Kernel::$groupedMiddlewares ))
			{
				$stack =
				[
					...$stack,
					...$this->resolveMiddlewares( Kernel::$groupedMiddlewares[ $mw ])
				];
			}
			else
			{
				// if middleware points to an alias
				if( array_key_exists( $mw, Kernel::$middlewareAliases ))
				{
					$stack[] = new Middleware( Kernel::$middlewareAliases[ $mw ]);
				}
				// its just fully qualified namespace
				else
				{
					$stack[] = new Middleware( $mw );
				}

				$len = count( $stack );

				if( isset( $stack[ $len - 2 ]))
				{
					$stack[ $len - 2 ]->link( $stack[ $len - 1 ]);
				}
			}
		}

		return $stack;
	}
}
