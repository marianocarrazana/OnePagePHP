<?php
namespace OnePagePHP\Interfaces;

interface Log{
	const LOG_NONE = '';
	const LOG_ALL = 'all';
	const LOG_HTML = 'html';
	const LOG_CONSOLE = 'console';
	public function getHtmlErrors();
	public function getConsoleLog();
	public function log($message);
	public function warn($message);
	public function error($message);
	public function info($message);
}