<?php
namespace OnePagePHP;
/**
 * 
 */
class Sandbox 
{
	function __construct(string $path,array $variables=[],OnePage &$OnePage)
	{
		require_once $path;
	}
}