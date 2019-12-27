<?php
namespace OnePagePHP;

/**
 * Global store shared between the server and client
 * 
 * The global store, accesible from the server(PHP) with templates variables 
 * and from the client(javascript) with OnePage.store object.
 */
class Store
{
	private $variables = [];
	private $serverVariables = [];
	private $clientVariables = [];
	
	/**
	 * This class is autoloaded by the Loader class
	 * 
	 * @param array $globalVariables Initial global variables 
	 */
	public function __construct(array $globalVariables)
	{
		$this->variables = $globalVariables;
	}

	/**
	 * @param string $name the name accesible in php and js
	 * @param $value The content
	 */
	public function addVariable(string $name, $value){
		$this->variables[$name] = $value;
	}


}