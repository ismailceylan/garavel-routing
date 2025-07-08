<?php

namespace Garavel\Routing\Exceptions;

use Exception;

/**
 * Handles exceptions when a controller method is not exist.
 */
class UnknownControllerMethod extends Exception
{
    /**
     * Constructs the exception for an unknown controller method.
     *
     * @param string $namespace The namespace of the controller.
     * @param string $methodName The name of the method that doesn't exist.
     */
	public function __construct( string $namespace, string $methodName )
	{
		parent::__construct(
			"$methodName method doesn't exists in $namespace controller."
		);
	}
}
