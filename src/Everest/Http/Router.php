<?php

/*
 * This file is part of Everest.
 *
 * (c) 2017 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\Http;
use Everest\Http\Responses\Response;
use Everest\Http\Responses\JsonResponse;
use Everest\Http\Responses\RedirectResponse;
use Everest\Http\Requests\Request;
use Everest\Http\Requests\ServerRequest;

use InvalidArgumentException;
use Exception;
use Closure;

/**
 * Class for routing http requests
 */

class Router {

	private const DEFAULT_OPTIONS = [
		'debug' => false
	];

	/**
	 * Options for this router
	 * @var array
	 */
	
	private $options;

	/**
	 * Root routing context
	 * @var Everest\Http\RoutingContext
	 */

	protected $rootContext;


	/**
	 * Current routing context
	 * @var Everest\Http\RoutingContext
	 */

	protected $currentContext;


	/**
	 * Constructor
	 * Invokes a new router object
	 *
	 * @return self
	 */
	
	public function __construct(array $options = [])
	{
		$this->options = array_merge(self::DEFAULT_OPTIONS, $options);

		$this->rootContext =
		$this->currentContext = new RoutingContext;
	}


	/**
	 * Adds a new routing context
	 *
	 * @param  string $prefix
	 *    The path prefix for this context
	 *
	 * @param Closure $invoker
	 *    The closure invoking this context
	 *
	 * @return self
	 */
	
	public function context(string $prefix, $invoker) {

		if (!$invoker instanceof Closure) {
			throw new InvalidArgumentException('Context invoker must be instance of Closure.');
		}

		$this->currentContext->addSubContext($prefix, $invoker);

		return $this;
	}

	/**
	 * Adds middleware to the current context
	 *
	 * @param array $middlewares
	 *    The middlewares to ad
	 *
	 * @return self
	 */
	
	public function middleware(...$middlewares) 
	{
		foreach ($middlewares as $middleware) {
			$this->currentContext->addMiddleware($middleware);
		}

		return $this;
	}


	/**
	 * Add new route to current context
	 *
	 * @param  Everest\Http\Route  $route 
	 *    The route to add
	 * @param  callable $handler
	 *    The route handler
	 *
	 * @return self
	 */
	
	public function route(Route $route, $handler) 
	{
		if (!is_callable($handler)) {
			throw new Exception('Route handler must be callable.');
		}

		$this->currentContext->addRoute($route, $handler);
		return $this;
	}

	/**
	 * Adds a new route with given path to the current context
	 * that accepts the given methods requests.
	 *
	 * @param string $path
	 *    The route path
	 * @param int $methods
	 *    The accepted route methods 
	 * @param callable $handler
	 *    The route handler
	 *
	 * @return self
	 */
	
	public function request(string $path, int $methods, $handler)
	{
		$path = trim($path, "\n\r\t/ ");
		return $this->route(new Route($path, $methods), $handler);
	}


	/**
	 * Adds a new route with given path to the current context
	 * that accepts GET and HEAD requests.
	 *
	 * @param string $path
	 *    The route path
	 * @param callable $handler
	 *    The route handler
	 *
	 * @return self
	 */

	public function get(string $path, $handler)
	{
		return $this->request($path, Request::HTTP_GET | Request::HTTP_HEAD, $handler);
	}


	/**
	 * Adds a new route with given path to the current context
	 * that accepts POST requests.
	 *
	 * @param string $path
	 *    The route path
	 * @param callable $handler
	 *    The route handler
	 *
	 * @return self
	 */

	public function post(string $path, $handler)
	{
		return $this->request($path, Request::HTTP_POST, $handler);
	}


	/**
	 * Adds a new route with given path to the current context
	 * that accepts PUT requests.
	 *
	 * @param string $path
	 *    The route path
	 * @param callable $handler
	 *    The route handler
	 *
	 * @return self
	 */

	public function put(string $path, $handler)
	{
		return $this->request($path, Request::HTTP_PUT, $handler);
	}


	/**
	 * Adds a new route with given path to the current context
	 * that accepts DELETE requests.
	 *
	 * @param string $path
	 *    The route path
	 * @param callable $handler
	 *    The route handler
	 *
	 * @return self
	 */

	public function delete(string $path, $handler)
	{
		return $this->request($path, Request::HTTP_DELETE, $handler);
	}


	/**
	 * Adds a new route with given path to the current context
	 * that accepts all requests.
	 *
	 * @param string $path
	 *    The route path
	 * @param callable $handler
	 *    The route handler
	 *
	 * @return self
	 */

	public function any(string $path, $handler)
	{
		return $this->request($path, Request::HTTP_ALL, $handler);
	}

	/**
	 * Sets a global variable pattern for the
	 * given name.
	 *
	 * @param  string $name
	 *     The variable name to validate
	 * @param  string $pattern
	 *     The pattern the variable must match
	 *
	 * @return self
	 */
	
	public function validate(string $name, string $pattern)
	{
		$this->currentContext->addPattern($name, $pattern);
		return $this;
	}


	/**
	 * Sets a default handler that is uses if no
	 * route matches the request.
	 *
	 * @param  callable $defaultHandler 
	 *    The handler
	 *
	 * @return self
	 * 
	 */
	
	public function otherwise($defaultHandler)
	{
		$this->currentContext->setDefault($defaultHandler);
		return $this;
	}

	/**
	 * Get a chain of middlewares that pre process 
	 * arguments before entering the current route handler
	 *
	 * @param array $middlewares
	 *    The middlewares to compose
	 *
	 * @return Closure
	 *    The middleware chain
	 */
	
	private function composeMiddleware(array $middlewares)
	{
		$curryedMiddlewares = array_map(function($middleware) {
			return function ($next) use ($middleware) {
				return function (...$args) use ($middleware, $next) {
					return call_user_func($middleware, $next, ...$args);
				};
			};
		}, array_reverse($middlewares));

		return function ($handler) use ($curryedMiddlewares) {
			$handler = function(...$args) use ($handler) {
				return call_user_func_array($handler, $args);
			};
			return array_reduce($curryedMiddlewares, function($state, $middleware){
				return function(...$args) use($state, $middleware) {
					return $middleware($state)(...$args);
				};
			}, $handler);
		};
	}

	/**
	 * Transforms any skalar handler result to a Everest\Http\Response object
	 *
	 * @param  mixed $result
	 *    The handler result to be transformed
	 *
	 * @return Everest\Http\Response
	 *    The response
	 */
	
	private function resultToResponse($result) : Response
	{
		switch (true) {
			// Response object
			case $result instanceof Response:
				return $result;
			// String -> transform to response
			case is_string($result) || is_numeric($result):
				return new Response($result);
			// Uri -> transform to redirect response
			case $result instanceof Uri:
				return new RedirectResponse($uri);
			// Array -> transform to json response
			case is_array($result) || is_object($result):
				return new JsonResponse($result);
			default:
				throw new Exception('Invalid route handler return value');
		}
	}

	private function handleContext(ServerRequest $request, RoutingContext $context, bool $isRoot = false) :? Response
	{
		$uri  = $request->getUri();
		$host = $context->getHost();

		// Early return if context host does not match the request host
		if ($host && strcasecmp($host, $uri->getHost()) == 0) {
			return null;
		}

		$path   = $uri->getPath();
		$prefix = $context->getPrefixedPath();

		// Early return if context prefix does not match the request path
		if ($prefix && strpos($path, $prefix) !== 0) {
			return null;
		}

		// Save current context to restore it after this context is handled
		$prevContext = $this->currentContext;

		// Set new current context and invoke it
		$this->currentContext = $context;
		$context($this);


		$subContexts = $this->currentContext->getSubContexts();


		// Check if any sub context wants to handle the request 
		foreach ($subContexts as $subContext) {
			if ($subResult = $this->handleContext($request, $subContext)) {
				return $subResult;
			}
		}


		// Holds any exception caught during route handling
		$error = null;

		$routes = $this->currentContext->getRoutes();

		if (empty($subContexts) && empty($routes) && !$isRoot) {
			throw new \LogicException('Context without subcontext and routes found');
		}

		// Handle routes
		foreach ($routes as $routeAndHandler) {
			[$route, $handler] = $routeAndHandler;

			// Test for method (eg. HTTP_GET, HTTP_POST, ...)
			if (!$request->isMethod($route->getMethods())) {
				continue;
			}

			// Prefix route
			$route->setPrefix($prefix);


			// Test for local pattern
			if (null === $parameter = $route->parse($uri)) {
				if ($this->options['debug']) fwrite(STDERR, "\nPrefix $prefix -> {$route->buildRoutePattern()}\n");
				continue;
			}

			// Test for global pattern
			foreach (array_intersect_key($this->currentContext->getPattern(), $parameter) as $name => $pattern) {
				if (0 === preg_match($pattern , $parameter[$name])) {
					continue 2;
				}
			}
			
			// Execute route handler
			try {
				$result = $this->composeMiddleware(
					$this->currentContext->getMiddlewares()
				)($handler)($request, $parameter);

				// Call next handler if `null` was returned
				if (null === $result) {
					continue;
				}

				return $this->resultToResponse($result);

			} catch(Exception $e) {
				$error = $e;
				break;
			}
		}

		// Use context default handler to handle errors occured
		if ($error && $defaultHandler = $this->currentContext->getDefault()) {
			if (null === $result = call_user_func($defaultHandler, $request, $error)) {
				throw $error;
			}

			return $this->resultToResponse($result);
		}

		// Restore context
		$this->currentContext = $prevContext;

		// Throw unhandled errors
		if ($error) {
			throw $error;
		}

		return null;
	}


	/**
	 * Trys to match a route against the current request.
	 *
	 * @throws \Exception
	 *    If no route matches the request
	 *
	 * @param Everest\Http\Request $request
	 *    The request to be handled
	 *
	 * @return Everest\Http\Response
	 *    The response of the matching handler
	 * 
	 */
	
	public function handle(Request $request) : Response
	{
		try {
			if ($result = $this->handleContext($request, $this->rootContext, true)) {
				return $result;
			}
		}
		catch (Exception $error) {
			if ($defaultHandler = $this->rootContext->getDefault()) {
				return $this->resultToResponse(
					call_user_func($defaultHandler, $request, $error)
				);
			}

			throw new Exception('An unhandled exception occured. Set a default handler on root context to cover this case.', null, $error);
		}
		
		if ($defaultHandler = $this->rootContext->getDefault()) {
			return $this->resultToResponse(
				call_user_func($defaultHandler, $request, null)
			);
		}
		
		throw new Exception('No matching route found. Set a default handler on root context to cover this case.');
	}
}
