<?php

namespace Garavel\Routing;

/**
 * Represents a collection of route groups.
 */
class RouteGroupCollection
{
	/**
	 * Groups stack.
	 * 
	 * @var array
	 */
	public array $groups = [];

	/**
	 * Pushes a new route group to the stack.
	 * 
	 * @param array $options The options of the group.
	 */
	public function push( array $options )
	{
		$this->groups[] = new RouteGroup( $options );
	}

	/**
	 * Removes the latest group from the stack and returns it.
	 *
	 * @return RouteGroup
	 */
	public function pop(): RouteGroup
	{
		return array_pop( $this->groups );
	}

	/**
	 * Returns the latest group, or null if the stack is empty.
	 * 
	 * @return null|RouteGroup
	 */
	public function latest(): null|RouteGroup
	{
		return $this->groups[ count( $this->groups ) - 1 ] ?? null;
	}

	/**
	 * Merges all the group prefixes and returns it.
	 *
	 * The prefixes of the groups are merged in reverse order of the stack.
	 * If the given rest is not empty, it will be merged with the first prefix
	 * of the stack. If the stack is empty, the given rest will be returned as is.
	 *
	 * @param string $rest The rest of the prefix to merge.
	 * @return string The merged prefix.
	 */
	public function prefix( string $rest = '' ): string
	{
		$prefix = $rest;

		foreach( array_reverse( $this->groups ) as $group )
		{
			$prefix = $group->prefix( rest: $prefix );
		}

		return $prefix;
	}

	/**
	 * Merges all the group namespaces and returns it.
	 *
	 * The namespaces of the groups are merged in reverse order of the stack.
	 * If the given rest is not empty, it will be merged with the first namespace
	 * of the stack. If the stack is empty, the given rest will be returned as is.
	 *
	 * @param string $rest The rest of the namespace to merge.
	 * @return string The merged namespace.
	 */
	public function namespace( string $rest = '' ): string
	{
		$ns = $rest;

		foreach( array_reverse( $this->groups ) as $group )
		{
			$ns = $group->namespace( rest: $ns );
		}

		return $ns;
	}

	/**
	 * Merges all the group where clauses and returns it.
	 *
	 * The where clauses of the groups are merged in reverse order of the stack.
	 * If the given stack is not empty, it will be merged with the first where
	 * clauses of the stack. If the stack is empty, the given stack will be
	 * returned as is.
	 *
	 * @param array $stack The stack of where clauses to merge.
	 * @return array The merged where clauses.
	 */
	public function wheres( array $stack = []): array
	{
		foreach( array_reverse( $this->groups ) as $group )
		{
			$stack = array_merge( $stack, ( array ) $group->where );
		}

		return $stack;
	}

	/**
	 * Merges all the group middlewares and returns it.
	 *
	 * The middlewares of the groups are merged in the order of the stack.
	 * If the given stack is not empty, it will be merged with the first middleware
	 * of the stack. If the stack is empty, the given stack will be returned as is.
	 *
	 * @param array $stack The stack of middlewares to merge.
	 * @return array The merged middlewares.
	 */
	public function middlewares( array $stack = []): array
	{
		foreach( $this->groups as $group )
		{
			$stack = array_merge( $stack, ( array ) $group->middleware );
		}

		return $stack;
	}
}
