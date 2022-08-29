<?php

declare(strict_types=1);

/*
 * This file is part of Everest.
 *
 * (c) 2017 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\Http;

use Closure;

/**
 * A routing context frames a group of
 *  - routes
 *  - route parameter pattern
 *  - host (optional)
 *  - middlewares (before and after handling)
 *  - sub contexts
 *
 * Each context (except the root context) has
 * a parent context, a path prefix (to distinguish the context relevance
 * on the currently handled request path) and an invoker closure
 * that sets up this context.
 *
 * The first argument of the invoker is allways the router object
 * handling the request, so that you can use all routing methods
 * from withing the invoker.
 *
 * @author Philipp Steingrebe <development@steingrebe.de>
 */


class RoutingContext
{
    public const BEFORE = 'before';

    public const AFTER = 'after';

    private const MIDDLEWARE_TYPES = [
        self::BEFORE,
        self::AFTER,
    ];

    /**
     * Callable that invokes this context
     * by adding routes, middlewares, pattern and
     * default handlers.
     * @var Closure
     */
    private $invoker;

    /**
     * The parent routing context
     * @var Everest\Http\RoutingContext
     */
    private $parent;

    /**
     * The context preifx
     * if this context has no prefix
     * @var string
     */
    private $prefix;

    /**
     * Middlewares used by this context
     *
     * @var array[]
     */
    private $middlewares = [
        self::BEFORE => [],
        self::AFTER => [],
    ];

    /**
     * The routes with their handlers
     * controlled by this context
     * @var array[array[Everest\Http\Route, callable]]
     */
    private $routes = [];

    /**
     * Subcontexts owned by this context
     * @var array[Everest\Http\RoutingContext]
     */
    private $contexts = [];

    /**
     * Pattern von route parameter in this context
     * @var array[string]
     */
    private $pattern = [];

    /**
     * A host the request URI has to match
     * @var string|null
     */
    private $host = null;

    /**
     * The default handler for this context.
     * These handler get's called when an error
     * is thrown during the handling of this
     * context.
     * @var callable|null
     */
    private $default;

    /**
     * The error handler for this context.
     * These handler get's called when an error
     * is thrown during the handling of this
     * context.
     * @var callable
     */
    private $error;

    public function __construct(string $prefix = null, Closure $invoker = null, self $parent = null)
    {
        $this->prefix = $prefix ? trim($prefix, "\n\r\t/ ") : '';

        $this->invoker = $invoker;
        $this->parent = $parent;
    }

    /**
     * Invokes this context
     *
     * @param  Everest\Http\Router $router
     *    The router invoking this context
     *
     * @return self
     */
    public function __invoke(Router $router)
    {
        if ($this->invoker) {
            ($this->invoker)($router);
        }
    }

    /**
     * Gets path prefix of this context and all its parents
     *
     * @param  string $path (optional)
     *    The parents prefix
     *
     * @return string
     *    The prefixed path
     */
    public function getPrefixedPath(string $path = ''): string
    {
        return trim($this->parent ?
      ($this->parent->getPrefixedPath($this->prefix ? $this->prefix . '/' . $path : $path)) :
      ($this->prefix ? $this->prefix . '/' . $path : $path), '/');
    }

    /**
     * Adds a new parameter to this context
     *
     * @param string $name
     *    The parameter name that must match this pattern
     * @param string $pattern
     *    The regex pattern
     */
    public function addPattern(string $name, string $pattern)
    {
        $this->pattern[$name] = $pattern;
    }

    /**
     * Gets all parameter pattern of this context
     *
     * @return array[string]
     *   The pattern
     */
    public function getPattern(): array
    {
        return $this->pattern;
    }

    /**
     * Adds a new middleware to this context
     *
     * @param callable $middleware
     *    The middleware
     */
    public function addMiddleware(callable $middleware, string $type = self::BEFORE)
    {
        $type = self::validateMiddlewareType($type);
        $this->middlewares[$type][] = $middleware;
    }

    /**
     * Gets all middlewares of this context
     * and its parent contexts
     *
     * @param  array[callable]  $middlewares
     *    Middlewares to merge into this contexts middlewares
     *
     * @return array[callable]
     *   The middlewares of this context and its parent contexts
     */
    public function getMiddlewares(string $type = self::BEFORE): array
    {
        $type = self::validateMiddlewareType($type);
        $middlewares = $this->middlewares[$type];

        if ($this->parent) {
            $middlewares = array_merge($middlewares, $this->parent->getMiddlewares($type));
        }

        return $middlewares;
    }

    /**
     * Adds a new route and its handler to this context
     *
     * @param Everest\Http\Route $route
     *    The new route
     * @param callable $handler
     *    The route handler
     */
    public function addRoute(Route $route, $handler)
    {
        $this->routes[] = [$route, $handler];
    }

    /**
     * Gets all routes of this context
     *
     * @return array[Everest\Http\Route]
     *    The routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Adds a new sub context to this context
     *
     * @param string  $prefix
     *    The path prefix of this context
     * @param Closure $invoker
     *    The closure invoking the new sub context
     *
     * @return Everest\Http\RoutingContext
     *    The new sub context
     */
    public function addSubContext(string $prefix, Closure $invoker): self
    {
        return $this->contexts[] = new self($prefix, $invoker, $this);
    }

    /**
     * Gets all sub contexts of this context
     *
     * @return array[Everest\Http\RoutingContext]
     *    The sub contexts
     */
    public function getSubContexts(): array
    {
        return $this->contexts;
    }

    /**
     * Sets the host name of this context
     *
     * @param string|null $host
     *    The new host name of this context or `null` to unset
     *
     * @return self
     */
    public function setHost(string $host = null)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Returns this host name of this context
     * or `null` if none is set
     */
    public function getHost(): ? string
    {
        return $this->host;
    }

    /**
     * Sets the default handler for this context
     *
     * @param callable $defaultHandler
     *    The default handler
     */
    public function setDefault(callable $defaultHandler)
    {
        $this->default = $defaultHandler;
    }

    /**
     * Gets the default handler for this context or null
     * if none is set
     *
     * @return callable|null
     *    The default handler or null
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Sets the error handler for this context
     *
     * @param callable $errorHandler
     *    The error handler
     */
    public function setError(callable $errorHandler)
    {
        $this->error = $errorHandler;
    }

    /**
     * Gets the error handler for this context or null
     * if none is set
     *
     * @return callable|null
     *    The error handler or null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @throws \InvalidArgumentException I
     *   If provided middleware type does not exist.
     *
     * @param string $type
     *   The middleware type to validate
     *
     * @return string
     *   The middleware type
     */
    private static function validateMiddlewareType(string $type): string
    {
        if (! in_array($type, self::MIDDLEWARE_TYPES, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Provided middleware type does not exist. Use one of %s instead.',
                implode(', ', self::MIDDLEWARE_TYPES)
            ));
        }

        return $type;
    }
}
