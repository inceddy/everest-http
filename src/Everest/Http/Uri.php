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
use InvalidArgumentException;

/**
 * Uri representation.
 * @author Philipp Steingrebe <philipp@steingrebe.de>
 * @package Everest\Http
 */

class Uri {

  private const PORT_SCHEME_MAP = [
    21  => 'ftp',
    80  => 'http',
    110 => 'pop',
    143 => 'imap',
    389 => 'ldap',
    443 => 'https',
  ];

  private const SCHEME_PORT_MAP = [
    'ftp'   => 21,
    'http'  => 80,
    'pop'   => 110,
    'imap'  => 143,
    'ldap'  => 389,
    'https' => 443,
  ];

  private const IP_ADDRESS_PATTERN = '/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/';

  private const HOSTNAME_PATTERN = '/^(([a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])\.)*([a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])$/i';


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

  private static function validatePath(string $path) : array
  {
    $path = trim($path, "\t\n /");
    return $path === '' ? [] : explode('/', $path);
  }

  private static function validateQuery(string $query) : array
  {
    $queryArray = [];
    parse_str($query, $queryArray);

    return $queryArray;
  }

  private static function validatePort(int $port = null) :? int
  {
    if ($port === null) {
      return null;
    }
    
    if (1 > $port || 0xffff < $port) {
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
      0 === preg_match(self::HOSTNAME_PATTERN, $host) &&
      0 === preg_match(self::IP_ADDRESS_PATTERN, $host)
    ) {
      throw new InvalidArgumentException(sprintf(
        '%s is not a valid host name or ip-address.', 
        $host
      ));
    }

    return $host;
  }


  /**
   * Factory method from string
   *
   * @throws InvalidArgumentException If the string is not a valid uri
   * @param string $uri The string to be parsed into uri object.
   * @return Everest\Http\Uri
   */
  
  public static function fromString(string $uri) : Uri
  {
    $parts = parse_url($uri);

    if (false === $parts) {
      throw new InvalidArgumentException('Error parsing uri ' . $uri);
    }

    return self::fromArray($parts);
  }


  /**
   * Factory method from array
   *
   * @param string $uri The array to be parsed into uri object.
   * @return Everest\Http\Uri
   */

  public static function fromArray(array $parts) : Uri
  {
    return new self($parts);
  }

  /**
   * General factory method.
   * Calls a specific factory method based on the
   * supplied argument type.
   *
   * @throws InvalidArgumentException
   *   If supplied argument can not be casted to an iri object
   *
   * @param mixed $uri 
   *   The parameter to be casted to an uri object
   *   
   * @return Everest\Http\Uri
   */

  public static function from($uri)
  {
    switch (true) {
      case $uri instanceof Uri:
        return $uri;
      case is_string($uri):
        return self::fromString($uri);
      case is_array($uri):
        return self::fromArray($uri);
    }

    throw new InvalidArgumentException(sprintf(
      'Can\'t create uri from %s.',
      is_object($uri) ? get_class($uri) : gettype($uri)
    ));
  }


  /**
   * Constructor
   * @param array $options the options for this instance
   */
  
  public function __construct(array $parts = [])
  {
    if (isset($parts['password'])) {
      $parts['pass'] = $parts['password'];
    }

    $parts = array_merge([
      'scheme'   => 'http',
      'host'     => 'localhost',
      'port'     => null,
      'user'     => null,
      'pass'     => null,
      'path'     => '',
      'query'    => '',
      'fragment' => null
    ], $parts);

    $this->scheme   = strtolower($parts['scheme']);
    $this->userInfo = $parts['pass'] ? $parts['user'] . ':' . $parts['pass'] : $parts['user'];
    $this->host     = $parts['host'];
    $this->port     = $parts['port'] ?: null;
    $this->path     = self::validatePath($parts['path']);
    $this->query    = self::validateQuery($parts['query']);
    $this->fragment = $parts['fragment'];
  }


  /**
   * {@inheritDoc}
   */

  public function withScheme(string $scheme, bool $autoPort = true)
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


  /**
   * {@inheritDoc}
   */

  public function getScheme() : string
  {
    return $this->scheme;
  }


  /**
   * {@inheritDoc}
   */

  public function getAuthority() : string
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


  /**
   * {@inheritDoc}
   */
  
  public function withUserInfo(string $user, string $password = null) : Uri
  {
    $userInfo = $password ? $user . ':' . $password : $user;

    if ($userInfo === $this->getUserInfo()) {
      return $this;
    }

    $uri = clone $this;
    $uri->userInfo = $userInfo;

    return $uri;
  }

  public function getUserInfo() : string 
  {
    return $this->userInfo;
  }


  /**
   * {@inheritDoc}
   */

  public function withHost(string $host) : Uri
  {
    if ($host === $this->host) {
      return $this;
    }

    $uri = clone $this;
    $uri->host = self::validateHost($host);

    return $uri;
  }

  public function getHost() : string
  {
    return $this->host;
  }

  public function withPort(int $port = null) : Uri
  {
    if ($port === $this->port) {
      return $this;
    }

    $uri = clone $this;
    $uri->port = self::validatePort($port);

    return $uri;
  }

  public function getPort() :? int
  {
    return $this->port;
  }


  /**
   * {@inheritDoc} 
   */
  
  public function withPath(string $path) : Uri
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
  
  public function withPathPrepend(string $path) : Uri
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
 
  public function withPathAppend(string $path) : Uri
  {
    $uri = clone $this;
    $uri->path = array_merge($uri->path, self::validatePath($path));

    return $uri;
  }


  /**
   * {@inheritDoc} 
   */
  
  public function getPath() : string
  {
    return implode('/', $this->path); 
  }


  /**
   * Returns all path segments as an array
   * @return array<string>
   */
  
  public function getPathArray() : array
  {
    return $this->path;
  }


  /**
   * [withQueryString description]
   *
   * @param  string       $query [description]
   * @param  bool|boolean $merge [description]
   *
   * @return [type]              [description]
   */
  
  public function withQueryString(string $query, bool $merge = false) : Uri
  {
    return $this->withQueryArray(
      self::validateQuery($query),
      $merge
    );
  }

  public function withQueryArray(array $query, bool $merge = false) : Uri
  {
    if (empty(array_diff_assoc($query, $this->query))) {
      return $this;
    }

    $uri = clone $this;
    $uri->query = $merge ? array_merge($this->query, $query) : $query;

    return $uri;
  }

  public function withQuery($query) : Uri
  {
    if (is_string($query)) {
      return $this->withQueryString($query, false);
    }

    if (is_array($query)) {
      return $this->withQueryArray($query, false);
    }

    throw new InvalidArgumentException(sprintf(
      'Can\'t resolve query from given argument of type %s, use string or array instead.',
      is_object($query) ? get_class($query) : gettype($query)
    ));
  }

  public function withMergedQuery($query) : Uri
  {
    if (is_string($query)) {
      return $this->withQueryString($query, true);
    }

    if (is_array($query)) {
      return $this->withQueryArray($query, true);
    }

    throw new InvalidArgumentException(sprintf(
      'Can\'t resolve query from given argument of type %s, use string or array instead.',
      is_object($query) ? get_class($query) : gettype($query)
    ));
  }


  /**
   * Returns the query as array
   * @return array
   */
  
  public function getQueryArray() : array
  {
    return $this->query;
  }


  /**
   * {@inheritDoc}
   */
  
  public function getQuery()
  {
    return http_build_query($this->query);
  }


  /**
   * {@inheritDoc}
   */
  
  public function withFragment(string $fragment) : Uri
  {
    if ($fragment === $this->fragment) {
      return $this;
    }

    $uri = clone $this;
    $uri->fragment = $fragment;

    return $uri;
  }


  /**
   * {@inheritDoc}
   */
  
  public function getFragment()
  {
    return $this->fragment;
  }


  public function toString()
  {
    $uri = '';

    if ($this->scheme != '') {
      $uri .= $this->scheme . ':';
    }

    if ('' != ($authority = $this->getAuthority()) || $this->scheme === 'file') {
      $uri .= '//' . $authority;
    }

    if ('' != $path = $this->getPath()) {
      $uri .= '/' . $path;
    }

    if ('' != $query = $this->getQuery()) {
      $uri .= '?' . $query;
    }

    if ('' != $fragment = $this->getFragment()) {
      $uri .= '#' . $fragment;
    }

    return $uri;
  }

  public function equals(Uri $uri) : bool
  {
    return (string) $uri === $this->toString();
  }


  /**
   * Converts this object back to an URL string
   *
   * @return string
   * 
   */
  
  public function __toString()
  {
    return $this->toString();
  }
}