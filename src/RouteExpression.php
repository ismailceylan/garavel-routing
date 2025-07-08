<?php

namespace Garavel\Routing;

/**
 * Represents a route expression.
 */
class RouteExpression
{
	/**
	 * The route expression compiled to regexp.
	 * 
	 * @var string
	 */
	public string $pattern = '//';

	/**
	 * Expression segments.
	 * 
	 * @var array
	 */
	public array $segments = [];
	
	/**
	 * The route associated with the expression.
	 * 
	 * @var Route
	 */
	public Route $route;

	/**
	 * The original route expression.
	 *
	 * @var string
	 */
	public string $expression;

	/**
	 * Constructs a new RouteExpression instance.
	 * 
	 * @param Route $route The route associated with the expression.
	 * @param string $expression The original route expression.
	 */
	public function __construct( Route $route, string $expression )
	{
		$this->route = $route;
		$this->expression = $expression;
	}

	/**
	 * Compiles the route expression to a regular expression pattern.
	 *
	 * This method uses the route's where clauses to determine the regular
	 * expression for each segment. If a segment does not have a where
	 * clause, it is treated as a required parameter.
	 *
	 * The compiled regular expression pattern is also stored in the
	 * `pattern` property of the instance.
	 *
	 * @return string The compiled regular expression pattern.
	 */
	public function pattern(): string
	{
		$segmentPattern = '/\\\{(\??)(\w+)\\\}/';
		$eatEverything = '\w+';
		$optional = '?';
		$required = '';

		$segmentHandler = function( $match ) use ( $eatEverything, $optional, $required )
		{
			list(, $requiredFlagFromRouteExpr, $segmentName ) = $match;

			list( $pattern, $isRequired ) = 
				$this->route->wheres[ $segmentName ]
				??
				[ $eatEverything, $requiredFlagFromRouteExpr === $required ];

			$isRequired = $isRequired === null
				? $requiredFlagFromRouteExpr === $required
				: $isRequired;

			$this->segments[] = $segmentName;

			return "(?P<{$segmentName}>$pattern)" . ( $isRequired? $required : $optional );
		};

		return preg_replace_callback(
			$segmentPattern,
			$segmentHandler,
			preg_quote( $this->expression )
		);
	}
}
