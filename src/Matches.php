<?php

namespace Garavel\Routing;

/**
 * Handles route matching.
 */
class Matches
{
	/**
	 * Named matches.
	 * 
	 * @var array
	 */
	public array $matches = [];

	/**
	 * Ordinary matches.
	 * 
	 * @var array
	 */
	public array $values = [];

	/**
	 * Indicates if the expression has matched.
	 * 
	 * @var bool
	 */
	public bool $hasMatched = false;

	/**
	 * Constructs a new Matches object.
	 * 
	 * @param string $haystack The haystack to search in.
	 * @param RouteExpression $expression The expression to match.
	 */
	public function __construct(
		public string $haystack,
		public RouteExpression $expression
	)
	{
		$this->fullfillSegments(
			$this->search()
		);
	}

	/**
	 * Returns segment value.
	 * 
	 * @param string $key The segment key.
	 * @return string The segment value.
	 */
	public function __get( string $key ): string
	{
		return $this->matches[ $key ] ?? null;
	}

	/**
	 * Sets segment value.
	 * 
	 * @param string $key The segment key.
	 * @param string $val The segment value.
	 */
	public function __set( string $key, mixed $val )
	{
		$this->matches[ $key ] = $val;
	}

	/**
	 * Checks if the given key exists.
	 * 
	 * @param string $key The key to check.
	 * @return bool True if the key exists, false otherwise.
	 */
	public function __isset( string $key ): bool
	{
		return array_key_exists( $key, $this->matches );
	}

	/**
	 * Executes the search against the haystack using the expression
	 * pattern.
	 * 
	 * @return array
	 */
	private function search(): array
	{
		$flags = PREG_OFFSET_CAPTURE;
		$haystack = $this->haystack;
		$pattern = '#^' . $this->expression->pattern() . '$#u';

		$this->hasMatched = preg_match( $pattern, $haystack, $matches, $flags ) === 1;

		return $matches;
	}

	/**
	 * Fullfills segments.
	 * 
	 * @param array $matches
	 */
	private function fullfillSegments( array $matches ): void
	{
		foreach( $this->expression->segments as $segmentName )
		{
			$this->matches[ $segmentName ] =
				$matches[ $segmentName ][ 0 ] ?? null;
		}

		$this->values = array_values( $this->matches );
	}
}
