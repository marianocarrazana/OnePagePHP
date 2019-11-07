<?php
namespace OnePagePHP;
/**
 * 
 */
class Sandbox 
{
	function __construct(string $path,array $params=[],OnePage &$OnePage)
	{
		$renderer = $OnePage->getRenderer();
		require_once $path;
	}
}