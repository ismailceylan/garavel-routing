<?php

namespace Garavel\Routing;

use Closure;

/**
 * Middleware base class.
 */
class Middleware
{
	/**
	 * Next middleware.
	 * 
	 * @var Middleware
	 */
	public Middleware $next;

	/**
	 * Fully qualified namespace of middleware class.
	 * 
	 * @var string
	 */
	public string $middleware;

	/**
	 * Middleware.
	 * 
	 * @param string $middleware Fully qualified namespace of middleware class
	 */
	public function __construct( string $middleware )
	{
		$this->middleware = $middleware;
	}

	/**
	 * Links this and the given middleware to each other.
	 * 
	 * @param Middleware $next Next middleware
	 */
	public function link( Middleware $next )
	{
		$this->next = $next;
	}

	/**
	 * Runs the represented middleware.
	 * 
	 * @param Closure $final Controller action
	 * @return mixed
	 */
	public function run( Closure $final ): mixed
	{
		// handle methods can decide whether to approve or reject the request
		// so, we will explain how the process of moving forward is performed
		return ( new $this->middleware )->handle( function() use ( $final )
		{
			if( isset( $this->next ))
			{
				// handle method already called this closure and
				// there is another middleware after this one,
				// so we are gonna call the next middleware
				return $this->next->run( $final );
			}

			// we consumed all the middlewares and still we
			// are here so we should call the controller action
			return $final();
		});
	}
}
