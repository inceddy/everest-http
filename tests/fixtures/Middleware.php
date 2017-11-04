<?php

class Middleware {

	private $extraArgument;

	public function __construct($extraArgument)
	{
		$this->extraArgument = $extraArgument;
	}

	public function __invoke(Closure $next, ...$args)
	{
		return $next($this->extraArgument, ...$args);
	}
}