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

use ArrayAccess;
use BadMethodCallException;
use InvalidArgumentException;
use Stringable;

/**
 * Uri representation.
 * @author Philipp Steingrebe <philipp@steingrebe.de>
 * @package Everest\Http
 */

class Uri implements ArrayAccess, Stringable
{
    private const PORT_SCHEME_MAP = [
        21 => 'ftp',
        80 => 'http',
        110 => 'pop',
        143 => 'imap',
        389 => 'ldap',
        443 => 'https',
    ];

    private const SCHEME_PORT_MAP = [
        'ftp' => 21,
        'http' => 80,
        'pop' => 110,
        'imap' => 143,
        'ldap' => 389,
        'https' => 443,
    ];

    private const IP_ADDRESS_PATTERN = '/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/';

    private const HOSTNAME_PATTERN = '/^(([a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])\.)*([a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])$/i';

    public static $defaultScheme = 'http';

    public static $defaultHost = 'localhost';

    /**
     * The scheme of this URL eg. `http`.
     * @var string
     */
    protected $scheme = '';

    /**
     * The username and password of this uri.
     * @var string
     */
    protected $userInfo = '';

    /**
     * The host of the URL eg. `www.acme.com`
     * @var string
     */
    protected $host = '';

    /**
     * The port of the eg. `443` or `null` if not set.
     * If no port is provided it can be determined from the port-scheme-map.
     * @var int|null
     */
    protected $port = null;

    /**
     * The path partials.
     * http://comp.com/a/b/c -> ['a', 'b', 'c']
     * @var array<string>
     */
    protected $path = [];

    /**
     * The query
     * @var array<string>
     */
    protected $query = [];

    /**
     * The fragment
     * @var string
     */
    protected $fragment = '';

    /**
     * Constructor
     */
    public function __construct(array $parts = [])
    {
        if (isset($parts['password'])) {
            $parts['pass'] = $parts['password'];
        }

        $parts = array_merge([
            'scheme' => self::$defaultScheme,
            'host' => self::$defaultHost,
            'port' => null,
            'user' => null,
            'pass' => null,
            'path' => '',
            'query' => '',
            'fragment' => null,
        ], $parts);

        $this->scheme = strtolower((string) $parts['scheme']);
        $this->userInfo = $parts['pass'] ? $parts['user'] . ':' . $parts['pass'] : $parts['user'];
        $this->host = $parts['host'];
        $this->port = $parts['port'] ?: null;
        $this->path = self::validatePath($parts['path']);
        $this->query = self::validateQuery($parts['query']);
        $this->fragment = $parts['fragment'];
    }

    /**
     * Converts this object back to an URL string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Factory method from string
     *
     * @throws InvalidArgumentException If the string is not a valid uri
     * @param string $uri The string to be parsed into uri object.
     */
    public static function fromString(string $uri): self
    {
        $parts = parse_url($uri);

        if ($parts === false) {
            throw new InvalidArgumentException('Error parsing uri ' . $uri);
        }

        return self::fromArray($parts);
    }

    /**
     * Factory method from array
     */
    public static function fromArray(array $parts): self
    {
        return new self($parts);
    }

    /**
     *  General factory method.
     *  Calls a specific factory method based on the
     *  supplied argument type.
     *
     * @throws InvalidArgumentException
     *   If supplied argument can not be casted to an iri object
     *
     * @param mixed $uri
     *   The parameter to be casted to an uri object
     */
    public static function from(mixed $uri): self
    {
        return match (true) {
            $uri instanceof self => $uri,
            is_string($uri) => self::fromString($uri),
            is_array($uri) => self::fromArray($uri),
            default => throw new InvalidArgumentException(sprintf(
                'Can\'t create uri from %s.',
                get_debug_type($uri)
            )),
        };
    }

    public function withScheme(string $scheme, bool $autoPort = true): static
    {
        $scheme = strtolower($scheme);

        if ($this->scheme === $scheme) {
            return $this;
        }

        $uri = clone $this;
        $uri->scheme = $scheme;

        if ($autoPort && isset(self::SCHEME_PORT_MAP[$scheme])) {
            $uri->port = self::SCHEME_PORT_MAP[$scheme];
        }

        return $uri;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getAuthority(): string
    {
        $authority = $this->host;

        if ($this->userInfo) {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->port && (self::SCHEME_PORT_MAP[$this->scheme] ?? null) !== $this->port) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    public function withUserInfo(string $user, string $password = null): self
    {
        $userInfo = $password ? $user . ':' . $password : $user;

        if ($userInfo === $this->getUserInfo()) {
            return $this;
        }

        $uri = clone $this;
        $uri->userInfo = $userInfo;

        return $uri;
    }

    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    public function withHost(string $host): self
    {
        if ($host === $this->host) {
            return $this;
        }

        $uri = clone $this;
        $uri->host = self::validateHost($host);

        return $uri;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function withPort($port = null): self
    {
        if ($port !== null) {
            $port = (int) $port;
        }

        if ($port === $this->port) {
            return $this;
        }

        $uri = clone $this;
        $uri->port = self::validatePort($port);

        return $uri;
    }

    public function getPort(): ? int
    {
        return $this->port;
    }

    public function withPath(string $path): self
    {
        // Absolute path
        if ($path !== '' && $path[0] === '/') {
            return $this->withPathPrepend($path);
        }

        if ($path === $this->getPath()) {
            return $this;
        }

        $uri = clone $this;
        $uri->path = self::validatePath($path);

        return $uri;
    }

    /**
     * Return an instance with the specified path prepended to the currend one.
     *
     * @param string $path The path to prepend to the new instance.
     * @return static A new instance with the specified path prepended.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPathPrepend(string $path): self
    {
        $uri = clone $this;
        $uri->path = array_merge(self::validatePath($path), $uri->path);

        return $uri;
    }

    /**
     * Return an instance with the specified path appended to the currend one.
     *
     * @param string $path The path to append to the new instance.
     * @return static A new instance with the specified path appended.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPathAppend(string $path): self
    {
        $uri = clone $this;
        $uri->path = array_merge($uri->path, self::validatePath($path));

        return $uri;
    }

    public function getPath(): string
    {
        return implode('/', $this->path);
    }

    /**
     * Returns all path segments as an array
     * @return array<string>
     */
    public function getPathArray(): array
    {
        return $this->path;
    }

    public function withQueryString(string $query, bool $merge = false): self
    {
        return $this->withQueryArray(
            self::validateQuery($query),
            $merge
        );
    }

    public function withQueryArray(array $query, bool $merge = false): self
    {
        if (
            empty(
                array_merge(
                    array_diff_assoc($query, $this->query),
                    array_diff_assoc($this->query, $query)
                )
            )
        ) {
            return $this;
        }

        $uri = clone $this;
        $uri->query = $merge ? array_merge($this->query, $query) : $query;

        return $uri;
    }

    public function withQuery($query): self
    {
        if (is_string($query)) {
            return $this->withQueryString($query, false);
        }

        if (is_array($query)) {
            return $this->withQueryArray($query, false);
        }

        throw new InvalidArgumentException(sprintf(
            'Can\'t resolve query from given argument of type %s, use string or array instead.',
            get_debug_type($query)
        ));
    }

    public function withMergedQuery($query): self
    {
        if (is_string($query)) {
            return $this->withQueryString($query, true);
        }

        if (is_array($query)) {
            return $this->withQueryArray($query, true);
        }

        throw new InvalidArgumentException(sprintf(
            'Can\'t resolve query from given argument of type %s, use string or array instead.',
            get_debug_type($query)
        ));
    }

    /**
     * Returns the query as array
     */
    public function getQueryArray(): array
    {
        return $this->query;
    }

    public function getQuery(): string
    {
        return http_build_query($this->query);
    }

    public function withFragment(string $fragment): self
    {
        if ($fragment === $this->fragment) {
            return $this;
        }

        $uri = clone $this;
        $uri->fragment = $fragment;

        return $uri;
    }

    public function getFragment(): string
    {
        return $this->fragment ?? '';
    }

    public function toString(): string
    {
        $uri = '';

        if ($this->scheme) {
            $uri .= $this->scheme . ':';
        }

        if (($authority = $this->getAuthority()) || $this->scheme === 'file') {
            $uri .= '//' . $authority;
        }

        if ($path = $this->getPath()) {
            $uri .= '/' . $path;
        }

        if ($query = $this->getQuery()) {
            $uri .= '?' . $query;
        }

        if ($fragment = $this->getFragment()) {
            $uri .= '#' . $fragment;
        }

        return $uri;
    }

    public function equals(self $uri): bool
    {
        return (string) $uri === $this->toString();
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->path[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->path[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): never
    {
        throw new BadMethodCallException('Not implemented');
    }

    public function offsetUnset(mixed $offset): never
    {
        throw new BadMethodCallException('Not implemented');
    }

    private static function validatePath(string $path): array
    {
        $path = trim($path, "\t\n /");
        return $path === '' ? [] : explode('/', $path);
    }

    private static function validateQuery(string $query): array
    {
        $queryArray = [];
        parse_str($query, $queryArray);

        return $queryArray;
    }

    private static function validatePort(int $port = null): ? int
    {
        if ($port === null) {
            return null;
        }

        if ($port < 1 || $port > 0xffff) {
            throw new \InvalidArgumentException(sprintf(
                '%d is not a valid port. Expecting integer between 1 and 65535',
                $port
            ));
        }

        return $port;
    }

    /**
     * Validates a host name
     * @param  string $host The host name to validate
     * @return string The host name
     */
    private static function validateHost(string $host)
    {
        if (
            preg_match(self::HOSTNAME_PATTERN, $host) === 0 &&
            preg_match(self::IP_ADDRESS_PATTERN, $host) === 0
        ) {
            throw new InvalidArgumentException(sprintf(
                '%s is not a valid host name or ip-address.',
                $host
            ));
        }

        return $host;
    }
}
