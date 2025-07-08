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
	 * @param Matches $matches Route matches
	 * @param Closure $final Controller action
	 * @return mixed
	 */
	public function run( Matches $matches, Closure $final ): mixed
	{
		// boot methods can decide whether to move the request forward or to fail
		// so we will pack it how the process of moving forward is performed
		$next = fn() => isset( $this->next )
			// boot method called this closure and
			// there is another middleware after this
			// we are gonna call the next middleware
			? $this->next->run( $matches, $final )
			// we consumed all the middlewares and still we
			// are here so we should call the controller action
			: $final();
		
		return ( new $this->middleware )->handle( $next, $matches );
	}
}
