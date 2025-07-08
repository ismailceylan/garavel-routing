<?php

namespace Garavel\Routing;

use Garavel\Support\Str;

/**
 * Represents a route group.
 */
class RouteGroup
{
	/**
	 * The route group options.
	 *
	 * @var array
	 */
	public array $options;

	/**
	 * Constructs a new RouteGroup instance.
	 *
	 * @param array $options The options for the route group.
	 */
	public function __construct( array $options )
	{
		$this->options = $options;
	}

	/**
	 * Magic getter to access route group options.
	 *
	 * @param string $key The key of the option to retrieve.
	 * @return mixed The value of the option, or null if not set.
	 */
	public function __get( string $key ): mixed
	{
		return $this->options[ $key ] ?? null;
	}

	/**
	 * Returns the merged route prefix with the given rest.
	 *
	 * If the group has a prefix, it will be merged with the given rest.
	 * If the group does not have a prefix, the rest will be returned as is.
	 *
	 * @param string $rest The rest of the prefix to merge.
	 * @param string $glue The glue to use for merging.
	 * @return string The merged prefix.
	 */
	public function prefix( string $rest, string $glue = '/' ): string
	{
		return Str::mergeWith( $glue, $this->prefix ?? '', $rest );
	}

	/**
	 * Returns the merged route namespace with the given rest.
	 *
	 * If the group has a namespace, it will be merged with the given rest.
	 * If the group does not have a namespace, the rest will be returned as is.
	 *
	 * @param string $rest The rest of the namespace to merge.
	 * @param string $glue The glue to use for merging.
	 * @return string The merged namespace.
	 */
	public function namespace( string $rest, string $glue = '\\' ): string
	{
		return Str::mergeWith( $glue, $this->namespace ?? '', $rest );
	}
}
