<?php
namespace OnePagePHP;
/**
 * 
 */
class Sandbox 
{
	function __construct(string $path,array $params=[])
	{
		require_once $path;
	}
}