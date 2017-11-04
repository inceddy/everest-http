<?php

/*
 * This file is part of Everest.
 *
 * (c) 2017 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\Http\Requests;
use Everest\Http\Uri;
use Everest\Http\MessageTrait;
use Everest\Http\MessageInterface;
use Everest\Http\Stream;
use Everest\Http\Collections\ParameterCollectionInterface;
use Everest\Http\Collections\ParameterCollection;
use Everest\Http\Collections\HeaderCollection;
use Everest\Http\Collections\CookieCollectionInterface;
use Everest\Http\Collections\CookieCollection;

class Request implements MessageInterface, RequestInterface {

  use MessageTrait;


  /**
   * HTTP-method flag
   * @var int
   */
  
  protected $method;


  /**
   * Request target
   * @var string
   */
  
  protected $target;


  /**
   * The request uri
   * @var Everest\Http\Uri
   */
  
  protected $uri;


  private static function validateMethod($method) : int
  {
    if (is_string($method)) {
      if (false === $method = array_search(strtoupper($method), self::HTTP_METHOD_MAP)) {
        throw new \InvalidArgumentException(sprintf(
          'Given method string is invalid. Use one of %s.',
          implode(', ', self::HTTP_METHOD_MAP)
        ));
      }
      return $method;
    }

    if (is_int($method)) {
      if (!array_key_exists($method, self::HTTP_METHOD_MAP)) {
        throw new \InvalidArgumentException(sprintf(
          'Given method flag is invalid. Use one of %s.',
          implode(', ', array_map(function($method) {
            return __NAMESPACE__ . '\\Request::HTTP_' . $method;
          }, self::HTTP_METHOD_MAP))
        ));
      }
      return $method;
    }

    throw new InvalidArgumentException(sprintf(
      'Given argument of type %s cant be used as method',
      is_object($method) ? get_class($method) : gettype($method)
    ));
  }

  private static function validateTarget(string $target) : string
  {
    return $target;
  }


  /**
   * Creates a new request with the default super global parameters of PHP.
   *
   * @param array $parameters the array with the different parameter-arrays
   *
   * @return Everest\Http\Request
   * 
   */

  public function __construct(
    $method, 
    $uri, 
    array $headers = [], 
    $body = null, 
    string $protocolVersion = self::HTTP_VERSION_1_1
  )
  {
    // Set method flag
    $this->method = self::validateMethod($method);

    // Set protocol version
    $this->protocolVersion = self::validateProtocolVersion($protocolVersion);

    // Set uri
    $this->uri = Uri::from($uri);

    // Set body
    $this->body = Stream::from($body);

    // Set headers
    $this->headers = new HeaderCollection($headers);    
  }

  public function getRequestTarget() : string
  {
    if (isset($this->target)) {
      return $this->target;
    }

    // Per default the request target equals the path and query
    // of the reuquest uri.

    $target = '/';
    

    if ($path = $this->uri->getPath()) {
      $target .= $path;
    }

    if ($query = $this->uri->getQuery()) {
      $target .= '?' . $query;
    }
    
    return $target;
  }

  public function withRequestTarget(string $target)
  {
    if ($target === $this->target) {
      return $this;
    }

    $new = clone $this;
    $new->target = $target;

    return $new;
  }

  public function getMethod() : string
  {
    return self::HTTP_METHOD_MAP[$this->method];
  }

  public function getMethodFlag() : int
  {
    return $this->method;
  }

  public function withMethod($method)
  {
    $method = self::validateMethod($method);

    if ($method === $this->method) {
      return $this;
    }

    $new = clone $this;
    $new->method = $method;

    return $new;
  }

  public function isMethod($method) : bool
  {
    return (bool)(self::validateMethod($method) & $this->method);
  }

  /**
   * Tests if request method is POST.
   * Alias for Request::isMethod(Request::HTTP_POST).
   *
   * @return boolean
   */
  
  public function isMethodPost() : bool
  {
      return $this->isMethod(self::HTTP_POST);
  }


  /**
   * Tests if request method is GET.
   * Alias for Request::isMethod(Request::HTTP_GET).
   *
   * @return boolean
   */

  public function isMethodGet() : bool
  {
      return $this->isMethod(self::HTTP_GET);
  }

  public function getUri() : Uri
  {
    return $this->uri;
  }

  public function withUri(Uri $uri, bool $preserveHost = false)
  {
    if ($this->uri->equals($uri)) {
      return $this;
    }

    $new = clone $this;
    $new->uri = $uri;

    if ($preserveHost) {
      return $new;
    }

    if (empty($this->getHeader('host')) && $host = $this->uri->getHost()) {
      $new->headers->set('host', $host);
    }

    return $new;
  }
}