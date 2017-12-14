<?php
use Everest\Http\Requests\ServerRequest;

class Middleware {

	private $extraArgument;

	public function __construct($extraArgument)
	{
		$this->extraArgument = $extraArgument;
	}

	public function __invoke(Closure $next, ServerRequest $request, ...$args)
	{
		array_push($args, $this->extraArgument);
		return $next($request, ... $args);
	}
}