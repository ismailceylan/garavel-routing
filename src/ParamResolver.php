<?php

namespace Garavel\Routing;

use RuntimeException;
use Garavel\Support\Arr;
use Garavel\Support\Str;

/**
 * Handles parameter resolvers.
 */
class ParamResolver
{
	/**
	 * Keeps resolver callables for classes.
	 * 
	 * @static
	 * @var array
	 */
	public static array $resolvers = [];

	/**
	 * Resolves the given class with given arguments.
	 * 
	 * @param  string $class the class name to resolve.
	 * @param  array  $args  the arguments to pass to the resolver.
	 * @return mixed
	 * @throws RuntimeException if there is no resolver defined for the given class.
	 */
	public function __invoke( string $class, array $args ): mixed
	{
		if( ! self::resolves( $class ))
		{
			throw new RuntimeException(
				"There is no resolver defined for $class class."
			);
		}

		$resolver = self::$resolvers[ $class ];

		return is_callable( $resolver )
			? $resolver( ...$args )
			: $resolver;
	}

	/**
	 * Checks if the given class has a resolver method or not.
	 * 
	 * @param string $class the class name to check.
	 * @return bool true if the class has a resolver, false otherwise.
	 */
	public static function resolves( string $class ): bool
	{
		return array_key_exists( $class, self::$resolvers );
	}

	/**
	 * Adds a resolver for a specific class.
	 *
	 * @param string $class The class name for which the resolver is being added.
	 * @param mixed $resolver The resolver function or value for the class.
	 * @return void
	 */
	public static function resolve( string $class, mixed $resolver ): void
	{
		self::$resolvers[ $class ] = $resolver;
	}

	/**
	 * Registers resolvers to classes. With that Garavel
	 * can know how to resolve when it see that classes.
	 *
	 * @return void
	 */
	public function register()
	{
		// 
	}

	/**
	 * Registers default resolvers.
	 */
	
	/**
	 * Registers default resolvers for classes.
	 * 
	 * This method registers the following resolvers:
	 * 
	 * - `Garavel\Http\Request` with `request()` helper.
	 * - `Garavel\Http\Response` with `response()` helper.
	 * - `Garavel\Http\JsonResponse` with `jsonResponse()` helper.
	 * - `string` with a function that just returns the given value.
	 * - `int` with a function that casts the given value to int.
	 * - `bool` with a function that parses the given value to boolean.
	 * - `array` with a function that normalizes the given value to an array,
	 *   if the value is not an array already.
	 * 
	 * @return ParamResolver
	 */
	public function registerDefaults(): ParamResolver
	{
		self::resolve( \Garavel\Http\Request::class, request());
		self::resolve( \Garavel\Http\Response::class, response());
		self::resolve( \Garavel\Http\JsonResponse::class, jsonResponse());

		self::resolve( 'string', fn( string $value ) => $value );
		self::resolve( 'int', fn( string $value ) => (int) $value );
		self::resolve( 'bool', fn( string $value ) => Str::parseBool( $value ));
		self::resolve( 'array', fn( string $value ) =>
			Str::isArrayable( $value )
				? Arr::normalize( Str::split( $value, Str::splitter( $value )))
				: Arr::normalize([ $value ])
		);

		return $this;
	}
}
