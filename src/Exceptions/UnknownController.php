<?php

namespace Garavel\Routing\Exceptions;

use Exception;

/**
 * Handles exceptions when a controller is not exist.
 */
class UnknownController extends Exception
{
	/**
	 * The controller namespace.
	 *
	 * @var string
	 */
	public string $controllerNS;

	/**
	 * Handles exceptions when a controller is not exist.
	 *
	 * @param string $controllerNS namespace of the controller that doesn't exist
	 */
	public function __construct( string $controllerNS )
	{
		parent::__construct( "Controller $controllerNS doesn't exist." );

		$this->controllerNS = $controllerNS;
	}
}
