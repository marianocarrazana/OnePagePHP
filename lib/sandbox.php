<?php
namespace OnePagePHP;
/**
 * Used for load the controllers
 */
class Sandbox 
{
	/**
	 * @param string $path Path to the controller
	 * @param array $params URL parameters
	 */
	function __construct(string $path,array $params=[])
	{
		require_once $path;
	}
}