<?php

namespace Garavel\Routing;

use Garavel\Support\Arr;
use Garavel\Http\Request;
use Garavel\Support\Facades\Response;
use Garavel\Support\Facades\JsonResponse;
use Garavel\Routing\Exceptions\MethodNotAllowed;
use Garavel\Routing\Exceptions\NoRouteForRequest;

/**
 * Represents a collection of routes.
 */
class RouteCollection
{
	/**
	 * Route list.
	 *
	 * @var array
	 */
	public array $routes = [];

	/**
	 * Adds a route to the collection.
	 *
	 * @param Route $route Route to be added.
	 * @return Route Added route.
	 */
	public function add( Route $route ): Route
	{
		return $this->routes[] = $route;
	}

	/**
	 * Tries to match the given request with the routes in the collection.
	 *
	 * If a route matches, it will be run. If the route does not support the
	 * request method, it will be added to the $matches array. If the $matches
	 * array is not empty after the loop, and the request method is OPTIONS, it
	 * will respond with the allowed methods. If the request method is not
	 * OPTIONS, it will throw a MethodNotAllowed exception. If no route matches
	 * the request, it will throw a NoRouteForRequest exception.
	 *
	 * @param Request $request Request to be matched.
	 * @throws NoRouteForRequest when not found a route matches with request
	 * @throws MethodNotAllowed when found a route but the request method is not supported by them
	 */
	public function match( Request $request ): void
	{
		$matches = [];
		$requestMethod = $request->method();

		foreach( $this->routes as $route )
		{
			$match = $route->match(
				$request->path()
			);

			// if route matches with requested path
			if( $match->hasMatched )
			{
				// if route supports the requested method
				if( $route->supports( $requestMethod ))
				{
					$route->run( $match );
					return;
				}

				$matches[] = $route;
			}
		}

		// If the $matches array is not empty, this means that there are route(s)
		// that match the requested path, but none of them support the request method
		if( ! empty( $matches ))
		{
			// if the requested method OPTIONS
			if( $requestMethod === 'OPTIONS' )
			{
				$this->responseToOptionsRequest( $matches, $request );
				return;
			}

			throw new MethodNotAllowed(
				"$requestMethod method not allowed. You can only use: " .
				Arr::join( Arr::flat( Arr::pluck( $matches, 'methods' )))
			);
		}

		throw new NoRouteForRequest;
	}

	/**
	 * Responds to an OPTIONS request with allowed HTTP methods.
	 *
	 * This function generates a response listing the HTTP methods
	 * allowed for the requested route. If the request is via AJAX,
	 * a JSON response is returned; otherwise, a plain text response
	 * is provided. The response includes headers indicating the allowed
	 * methods.
	 *
	 * @param array $matches Array of matched routes.
	 * @param Request $request The incoming HTTP request.
	 */
	public function responseToOptionsRequest( array $matches, Request $request ): void
	{
		$methods = Arr::flat( Arr::pluck( $matches, 'methods' ));
		$strMethods = Arr::join( $methods );

		if( $request->ajax())
		{
			$responser = JsonResponse::write( $methods );
		}
		else
		{
			$responser = Response::write( $strMethods );
		}

		$responser
			->status( 200 )
			->header( 'Allow', $strMethods )
			->header( 'Access-Control-Allow-Methods', $strMethods )
			->flush();
	}
}
