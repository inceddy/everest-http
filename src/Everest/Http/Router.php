<?php

/*
 * This file is part of Everest.
 *
 * (c) 2018 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\Http;

use Everest\Http\Responses\ResponseInterface;
use Everest\Http\Responses\Response;
use Everest\Http\Responses\JsonResponse;
use Everest\Http\Responses\RedirectResponse;

use Everest\Http\Requests\RequestInterface;
use Everest\Http\Requests\Request;
use Everest\Http\Requests\ServerRequest;


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

    if (!$invoker instanceof \Closure) {
      throw new \InvalidArgumentException('Context invoker must be instance of Closure.');
    }

    $this->currentContext->addSubContext($prefix, $invoker);

    return $this;
  }

  /**
   * Adds middleware to the current context
   *
   * @deprecated 1.0.0 No longer used by internal code and not recommended.
   *
   * @param array $middlewares
   *    The middlewares to add
   *
   * @return self
   */
  
  public function middleware(...$middlewares) 
  {
    trigger_error('Method ' . __METHOD__ . ' is deprecated. Use ::before() and ::after() instead.', E_USER_DEPRECATED);
    $this->before(... $middlewares);
  }

  /**
   * Adds middleware to the current context that is executed after 
   * route handling.
   *
   * @param array $middlewares
   *    The middlewares to add
   *
   * @return self
   */

  public function before(... $middlewares)
  {
    foreach ($middlewares as $middleware) {
      $this->currentContext->addMiddleware($middleware, RoutingContext::BEFORE);
    }

    return $this;
  }

  /**
   * Adds middleware to the current context that is executed after 
   * route handling.
   *
   * @param array $middlewares
   *    The middlewares to add
   *
   * @return self
   */

  public function after(... $middlewares)
  {
    foreach ($middlewares as $middleware) {
      $this->currentContext->addMiddleware($middleware, RoutingContext::AFTER);
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
      throw new \InvalidArgumentException('Route handler must be callable.');
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
   * that accepts PATCH requests.
   *
   * @param string $path
   *    The route path
   * @param callable $handler
   *    The route handler
   *
   * @return self
   */

  public function patch(string $path, $handler)
  {
    return $this->request($path, Request::HTTP_PATCH, $handler);
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
      return function (\Closure $next) use ($middleware) {
        return function (MessageInterface $requestOrResponse) use ($middleware, $next) {
          return call_user_func($middleware, $next, $requestOrResponse);
        };
      };
    }, array_reverse($middlewares));

    return array_reduce($curryedMiddlewares, function($state, $middleware){
      return function(MessageInterface $requestOrResponse) use ($state, $middleware) {
        return $middleware($state)($requestOrResponse);
      };
    }, function($requestOrResponse) {
      return $requestOrResponse;
    });
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
        throw new \InvalidArgumentException('Invalid route handler return value');
    }
  }

  private function handleRoute(Route $route, callable $handler, ServerRequest $request, ... $args)
  {
    // Test for method (eg. HTTP_GET, HTTP_POST, ...)
    if (!$request->isMethod($route->getMethods())) {
      return null;
    }

    // Test for local pattern
    if (null === $parameter = $route->parse($request->getUri())) {
      return null;
    }

    // Test for global pattern
    foreach (array_intersect_key($this->currentContext->getPattern(), $parameter) as $name => $pattern) {
      if (0 === preg_match($pattern , $parameter[$name])) {
        return null;
      }
    }

    foreach ($parameter as $name => $value) {
      $request = $request->withAttribute($name, $value);
    }

    return call_user_func($handler, $request->withAttribute('parameter', $parameter), ... $args);
  }

  private function handleContext(ServerRequest $request, RoutingContext $context, bool $isRoot = false) :? Response
  {
    $uri  = $request->getUri();
    $host = $context->getHost();

    // Early return if context host does not match the request host
    if ($host && strcasecmp($host, $uri->getHost()) == 0) {
      return null;
    }

    $path = $uri->getPath();
    $prefix = $context->getPrefixedPath();

    // Early return if context prefix does not match the request path
    if ($prefix && strpos($path, $prefix) !== 0) {
      return null;
    }

    // Save current context to restore it after this context is handled
    $prevContext = $this->currentContext;

    // Set new current context and invoke it
    $this->currentContext = $context;

    // Invoke context
    $context($this);

    // Save orginal request
    $orgRequest = $request;

    // Compose before middlewares
    $beforeResult = $this->composeMiddleware(
      $this->currentContext->getMiddlewares(RoutingContext::BEFORE)
    )($request);

    // Handle middleware result
    if ($beforeResult instanceof Responses\ResponseInterface) {
      return $beforeResult;
    }
    else if ($beforeResult instanceof Requests\RequestInterface) {
      $request = $beforeResult;
    }
    else {
      throw new \RuntimeException(sprintf(
        'Expected before-middleware to return %s or %s but %s was returned.',
        Responses\ResponseInterface::CLASS,
        Requests\RequestInterface::CLASS,
        is_object($beforeResult) 
          ? get_class($beforeResult)
          : gettype($beforeResult)
      ));
      
    }


    $subContexts = $this->currentContext->getSubContexts();


    // Check if any sub context wants to handle the request 
    foreach ($subContexts as $subContext) {
      if ($subResult = $this->handleContext($request, $subContext)) {
        return $subResult;
      }
    }

    // Context routes
    $routes = $this->currentContext->getRoutes();

    if (empty($subContexts) && empty($routes) && !$isRoot) {
      throw new \LogicException('Context without subcontext and routes found');
    }

    // Compose after middlewares
    $composedMiddlewareAfter = $this->composeMiddleware(
      $this->currentContext->getMiddlewares(RoutingContext::AFTER)
    );

    // Handle routes
    foreach ($routes as $routeAndHandler) {
      [$route, $handler] = $routeAndHandler;

      // Prefix route with current context prefix
      $route->setPrefix($prefix);
      
      // Execute route handler and before middleware
      //$result = $composedMiddlewareBefore($handler)($request, $route);

      $result = $this->handleRoute($route, $handler, $request);

      // Call next handler if `null` was returned
      if (null === $result) {
        continue;
      }

      // Execute result handler and after middleware
      return $composedMiddlewareAfter(
        $this->resultToResponse($result)
      );
    }

    // Use context default handler to handle errors occured
    if ($defaultHandler = $this->currentContext->getDefault()) {
      return $composedMiddlewareAfter(
        $this->resultToResponse(call_user_func(
          $defaultHandler, 
          $request, 
          $orgRequest
        ))
      );
    }

    // Restore context
    $this->currentContext = $prevContext;
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
  
  public function handle(Request $request) : ResponseInterface
  {
    // Reset current context to root context
    $this->currentContext = $this->rootContext;
    if (!$result = $this->handleContext($request, $this->rootContext, true)) {
      throw new HttpException(404);
    }

    return $result;
  }
}
