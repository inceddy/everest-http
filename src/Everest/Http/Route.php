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

use Everest\Http\Requests\Request;
use Stringable;

/**
 * Route describing an uri path
 * e.g. /test/{user_id}/edit
 *
 * @author Philipp Steingrebe <development@steingrebe.de>
 */


class Route implements Stringable
{
    // user/{id|[0-9+]?}
    private const VAR_PATTERN = '/\{([a-z0-9\-_]+)(?:|([^\}]+))?\}/i';

    private const ALLOWED_CHARS = '[a-z0-9_\.~\-%]+';

    /**
     * Prefix for this route
     * @var string|null
     */
    protected $prefix;

    /**
     * The route description
     * @var string
     */
    protected $route;

    /**
     * The request methods handled by this route
     * @see Everest\Http\Request\Request
     * @var integer
     */
    protected $methods;

    /**
     * Names of all parameters found in route description
     * @var array<string>
     */
    protected $parameter = [];

    /**
     * Parameter validation pattern
     * @var array
     */
    protected $parameterPattern = [];

    /**
     * Indicates if the route pattern is terminated with '$'.
     * @var boolean
     */
    protected $terminated = true;

    /**
     * Constructor
     *
     * @param string $route
     *    The route description
     * @param int $methods
     *    The http method bitmask
     */
    public function __construct(string $route, int $methods = Request::HTTP_ALL)
    {
        $route = trim($route, " \t\n\r"); // Remove leading and tailing whitespaces

        // Check for none termination
        if ($route !== '' && str_ends_with($route, '*')) {
            $route = substr($route, 0, -1);
            $this->setTermination(false);
        }

        $route = trim($route, '/'); // Remove leading and tailing slashes

        $this->route = $route;
        $this->methods = $methods;
    }

    public function __toString(): string
    {
        return $this->route;
    }

    /**
     * Returns the pattern an URI path must match to be handled by this route.
     * @return string
     */
    public function buildRoutePattern()
    {
        $path = $this->prefix ? rtrim($this->prefix . '/' . $this->route, '/') : $this->route;

        return '~^' . preg_replace_callback(self::VAR_PATTERN, function ($matches) {
            $this->parameter[] = $key = $matches[1];

            // Shorthand validation only if not set using the validation-method
            if (isset($matches[2]) && ! isset($this->parameterPattern[$key])) {
                $this->parameterPattern[$key] = $matches[2];
            }
            return $this->buildParameterPattern($key);
        }, $path) . ($this->terminated ? '$' : '') . '~i';
    }

    /**
     * Set whether this route is terminated or not
     *
     * @return self
     */
    public function setTermination(bool $terminated = true)
    {
        $this->terminated = $terminated;
        return $this;
    }

    /**
     * Returns whether or not this route is terminated or not
     */
    public function getTermination(): bool
    {
        return $this->terminated;
    }

    /**
     * Sets the route prefix
     *
     * @param string|null $prefix
     *    The new route rpefix or null to unset
     *
     * @return self
     */
    public function setPrefix(string $prefix = null)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Returns the route prefix
     */
    public function getPrefix(): ? string
    {
        return $this->prefix;
    }

    /**
     * Trys to extract all route variables of a given Uri path.
     * When the route doesen match the Uri `null` will be returned.
     *
     *    The Uri to parse
     *
     * @return array<string>|null
     *    The array of route parameters or null if not matching
     */
    public function parse(Uri $uri): ? array
    {
        // Get path without possible file
        $path = $uri->getPath();

        // Build route pattern
        $pattern = $this->buildRoutePattern();

        if (preg_match($pattern, $path, $matches) === 1) {
            $variables = [];
            $length = sizeof($matches);

            for ($i = 1; $i < $length; $i++) {
                $variables[$this->parameter[$i - 1]] = urldecode($matches[$i]);
            }

            return $variables;
        }

        return null;
    }

    /**
     * Setzt ein Validierungsmuster für einen
     * Variablen-Schlüssel
     *
     * @param  string $key      der Schlüssel der Variablen
     * @param  string $pattern  das Muster für den Variablenwert
     *
     * @return self
     */
    public function validate($key, $pattern)
    {
        $this->parameterPattern[$key] = $pattern;
        return $this;
    }

    /**
     * Returns the valid HTTP request methods for this route
     * in binary decoded.
     *
     * @return integer
     *    The valid methods
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * Returns the pattern a variable in the URI path must match.
     *
     * @param string $key
     *    The parameter key
     */
    protected function buildParameterPattern(string $key): string
    {
        return '(' . str_replace('~', '\\~', $this->parameterPattern[$key] ?? self::ALLOWED_CHARS) . ')';
    }
}
