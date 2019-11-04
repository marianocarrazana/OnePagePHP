<?php
namespace OnePagePHP;
/**
 * 
 */
class Sandbox 
{
	function __construct(string $path,array $variables=[],OnePage &$OnePage)
	{
		$renderer = $OnePage->getRenderer();
		require_once $path;
	}
}