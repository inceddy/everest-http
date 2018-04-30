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
use Everest\Http\MessageTrait;
use Everest\Http\MessageInterface;
use Everest\Http\UploadedFile;
use Everest\Http\Stream;
use Everest\Http\Uri;
use Everest\Http\Collections\ParameterCollection;
use Everest\Http\Collections\SessionCollection;
use Everest\Http\Collections\SessionCollectionInterface;
use Everest\Http\Collections\CookieCollection;
use Everest\Http\Collections\CookieCollectionInterface;

class ServerRequest extends Request implements RequestInterface {

  /**
   * The ParameterCollection of the super global $_GET
   * @var Everest\Http\ParameterCollectionInterface
   */
  
  public $query;


  /**
   * The ParameterCollection of the super global $_POST
   * @var Everest\Http\ParameterCollectionInterface
   */

  public $parsedBody;

  /**
   * The ParameterCollection of the super global $_FILES
   * @var Everest\Http\ParameterCollectionInterface
   */

  public $files;


  /**
   * The ParameterCollection of the super global $_SERVER
   * @var Everest\Http\ParameterCollectionInterface
   */

  public $server;


  /**
   * The CookieCollection of the super global $_COOKIE
   * @var Everest\Http\CookieCollectionInterface
   */
  
  public $cookie;

  private $attributes;

  public static function getUriFromGlobals() : Uri
  {
    $uri = Uri::from(sprintf('%s://%s:%s',
      self::getHttpScheme(),
      self::getHost(),
      self::getPort()
    ));

    if (isset($_SERVER['REQUEST_URI'])) {
      $requestUriParts = explode('?', $_SERVER['REQUEST_URI']);
      $uri = $uri->withPath($requestUriParts[0]);
      if (isset($requestUriParts[1])) {
        return $uri = $uri->withQuery($requestUriParts[1]);
      }
    }

    if (isset($_SERVER['QUERY_STRING'])) {
      $uri = $uri->withQuery($_SERVER['QUERY_STRING']);
    }

    return $uri;
  }

  /**
   * Tries to detect if the server is running behind an SSL.
   * @return boolean
   */
  
  public static function isBehindSsl() : bool
  {
      // Check for proxy first
      if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
          return self::protocolWithActiveSsl($_SERVER['HTTP_X_FORWARDED_PROTO']);
      }

      if (isset($_SERVER['HTTPS'])) {
          return self::protocolWithActiveSsl($_SERVER['HTTPS']);
      }

      return $_SERVER['SERVER_PORT'] == 443;
  }


  /**
   * Detects an active SSL protocol value.
   *
   * @param string $protocol
   *
   * @return boolean
   */

  private static function protocolWithActiveSsl($protocol) : bool
  {
      return in_array(strtolower((string)$protocol), ['on', '1', 'https', 'ssl'], true);
  }


  /**
   * Return the currently active URI scheme.
   * @return string
   */
  
  public static function getHttpScheme() : string
  {
      return self::isBehindSsl() ? 'https' : 'http';
  }


  /**
   * Tries to detect the host name of the server.
   * 
   * Some elements adapted from
   * @see https://github.com/symfony/HttpEverest/blob/master/Request.php
   *
   * @return string
   */
  
  public static function getHost() : string
  {
      // Check for proxy first
      $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ?
          last(explode(',', $_SERVER['HTTP_X_FORWARDED_HOST'])) : 
          $_SERVER['HTTP_HOST'] ?? 
          $_SERVER['HTTP_SERVER_NAME'] ?? 
          $_SERVER['HTTP_SERVER_ADDR'];

      // trim and remove port number from host
      // host is lowercase as per RFC 952/2181
      return strtolower(preg_replace('/:\d+$/', '', trim($host)));
  }

  /**
   * Returns the port of this request as string.
   * @return string
   */
    
  public static function getPort() :? int
  {
      // Check for proxy first
      if (isset($_SERVER['HTTP_X_FORWARDED_PORT'])) {
          return (int) $_SERVER['HTTP_X_FORWARDED_PORT'];
      }
      if ('https' === ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) {
          return 443;
      }

      return isset($_SERVER['SERVER_PORT']) ?
        (int) $_SERVER['SERVER_PORT'] : null;
  }


  /**
   * Return an UploadedFile instance array.
   *
   * @param array $files A array which respect $_FILES structure
   * @throws InvalidArgumentException for unrecognized values
   * @return array
   */
  public static function normalizeFiles(array $files)
  {
    $normalized = [];

    foreach ($files as $key => $value) {
      if ($value instanceof UploadedFile) {
        $normalized[$key] = $value;
      } elseif (is_array($value)) {
        $normalized[$key] = isset($value['tmp_name']) ?
          self::createUploadedFileFromSpec($value) : 
          self::normalizeFiles($value);
      } else {
        throw new \InvalidArgumentException('Invalid value in files specification');
      }
    }
    return $normalized;
  }


  /**
   * Create and return an UploadedFile instance from a $_FILES specification.
   *
   * If the specification represents an array of values, this method will
   * delegate to normalizeNestedFileSpec() and return that return value.
   *
   * @param array $value $_FILES struct
   * @return array|UploadedFileInterface
   */
  private static function createUploadedFileFromSpec(array $value)
  {
    if (is_array($value['tmp_name'])) {
      return self::normalizeNestedFileSpec($value);
    }
    return new UploadedFile(
      $value['tmp_name'],
      (int) $value['size'],
      (int) $value['error'],
      $value['name'],
      $value['type']
    );
  }


  /**
   * Normalize an array of file specifications.
   *
   * Loops through all nested files and returns a normalized array of
   * UploadedFileInterface instances.
   *
   * @param array $files
   * @return UploadedFileInterface[]
   */

  private static function normalizeNestedFileSpec(array $files = [])
  {
    $normalizedFiles = [];

    foreach (array_keys($files['tmp_name']) as $key) {
      $spec = [
        'tmp_name' => $files['tmp_name'][$key],
        'size'     => $files['size'][$key],
        'error'    => $files['error'][$key],
        'name'     => $files['name'][$key],
        'type'     => $files['type'][$key],
      ];
      $normalizedFiles[$key] = self::createUploadedFileFromSpec($spec);
    }

    return $normalizedFiles;
  }


  /**
   * Creates a new request with the default super global parameters of PHP.
   *
   * @param array $parameters the array with the different parameter-arrays
   *
   * @return Everest\Http\Request
   */

  public function __construct(
    $method, 
    $uri, 
    array $headers = [], 
    $body = null, 
    string $protocolVersion = self::HTTP_VERSION_1_1,
    array $server = null
  )
  {
    parent::__construct($method, $uri, $headers, $body, $protocolVersion);

    $this->server = new ParameterCollection($server ?: $_SERVER);
    $this->attributes = [];
  }


  /**
   * Creates a new request with the default super global parameters of PHP.
   *
   * @return Everest\Http\Request
   * 
   */
  
  public static function fromGlobals()
  {
    static $instance;

    if (!isset($instance)) {

      $method   = $_SERVER['REQUEST_METHOD'] ?? 'GET';
      $headers  = function_exists('getallheaders') ? getallheaders() : [];
      $uri      = self::getUriFromGlobals();
      $body     = Stream::from(fopen('php://input', 'r+'));
      $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';
      
      $serverRequest = new ServerRequest($method, $uri, $headers, $body, $protocol, $_SERVER);

      $contentType = $serverRequest->getHeaderLine('Content-Type');

      if ($method === 'POST' && (
        false !== stripos($contentType, 'application/x-www-form-urlencoded') ||
        false !== stripos($contentType, 'multipart/form-data')
      )) {
        $parsedBody = $_POST;
      }

      else if (false !== stripos($contentType, 'application/json')) {
        $parsedBody = json_decode($body->getContents(), true);
      }

      else {
        $parsedBody = [];
        parse_str($body->getContents(), $parsedBody);
      }

      $instance = $serverRequest
        ->withCookieParams($_COOKIE)
        ->withQueryParams($_GET)
        ->withParsedBody($parsedBody)
        ->withUploadedFiles($_FILES);
    }

    return $instance;
  }


/**
   * Shortcut to fetch a GET-parameter with optional default value  
   *
   * @param string $key  
   *   the key to look for or NULL to acces the collection
   * @param mixed  $default   
   *   the value thar will be returned if the key is not set
   *
   * @return mixed
   *   the found or the default value
   */
  
  public function getQueryParam(string $key, $default = null) 
  {
    return $this->query && $this->query->has($key) ? $this->query->get($key) : $default;
  }

  public function getQueryParams() : array
  {
    return $this->query ? 
           $this->query->toArray() : 
           [];
  }


  public function withQueryParams(array $queryParams)
  {
    $new = clone $this;
    $new->query = new ParameterCollection($queryParams);

    return $new;
  }


  /**
   * Shortcut to fetch a POST-parameter with optional default value  
   *
   * @param string $key
   *   the key to look for
   * @param mixed $default
   *   the value thar will be returned if the key is not set
   *
   * @return mixed             
   *   the found or the default value
   */

  public function getBodyParam(string $key, $default = null) 
  {
    return $this->parsedBody && $this->parsedBody->has($key) ? 
           $this->parsedBody->get($key) : 
           $default;
  }


  /**
   * {@inheritDoc}
   */
  
  public function getParsedBody()
  {
    return $this->parsedBody ? 
           $this->parsedBody->toArray() : 
           [];
  }

  public function withParsedBody(array $parsedBody)
  {
    $new = clone $this;
    $new->parsedBody = new ParameterCollection($parsedBody);

    return $new;
  }


  /**
   * Shortcut to fetch a FILES-parameter with optional default value  
   *
   * @param  string $key      
   *   the key to look for
   * @param  mixed $default  
   *   the value thar will be returned if the key is not set
   *
   * @return mixed
   *   the found or the default value
   */

  public function getUploadedFile(string $key, $default = null)
  {
    return $this->uploadedFiles->has($key) ? $this->uploadedFiles->get($key) : $default;
  }

  public function getUploadedFiles() : array
  {
    return $this->uploadedFiles->toArray();
  }

  public function withUploadedFiles(array $uploadedFiles)
  {
    $new = clone $this;

    $new->uploadedFiles = new ParameterCollection(
      self::normalizeFiles($uploadedFiles)
    );

    return $new;
  }

  public function getCookieParams() : array
  {
    return $this->cookie ? $this->cookie->getArray() : [];
  }

  public function getCookieParam(string $name, $default = null)
  {
    if (!$this->cookie) {
      return $default;
    }

    return $this->cookie->get($name) ?: $default;
  }

  public function withCookieParams(array $cookieParams)
  {
    $new = clone $this;
    $new->cookie = new ParameterCollection($cookieParams);

    return $this;
  }


  public function getSeverParams() : array
  {
    return $this->server->toArray();
  }

  public function getServerParam(string $name, $default = null)
  {
    return $this->server->get($name) ?: $default;
  }

  /**
   * Shortcut to fetch the first set parameter of GET, POST or FILE with optional default value  
   *
   * @param string $key
   *   the key to look for
   * @param mixed $default
   *   the value thar will be returned if the key is not set
   *
   * @return mixed
   *   the found or the default value
   */

  public function getRequestParam(string $key, $default = null)
  {
    return $this->getQueryParam($key)   ?: 
           $this->getBodyParam($key)    ?: 
           $this->getUploadedFile($key) ?: $default;
  }

  /**
   * Retrieve attributes derived from the request.
   *
   * The request "attributes" may be used to allow injection of any
   * parameters derived from the request: e.g., the results of path
   * match operations; the results of decrypting cookies; the results of
   * deserializing non-form-encoded message bodies; etc. Attributes
   * will be application and request specific, and CAN be mutable.
   *
   * @return mixed[] Attributes derived from the request.
   */

  public function getAttributes() : array
  {
    return $this->attributes;
  }


  /**
   * Retrieve a single derived request attribute.
   *
   * Retrieves a single derived request attribute as described in
   * getAttributes(). If the attribute has not been previously set, returns
   * the default value as provided.
   *
   * This method obviates the need for a hasAttribute() method, as it allows
   * specifying a default value to return if the attribute is not found.
   *
   * @see getAttributes()
   * @param string $name The attribute name.
   * @param mixed $default Default value to return if the attribute does not exist.
   * @return mixed
   */

  public function getAttribute(string $name, $default = null)
  {
    return $this->attributes[$name] ?? $default;
  }


  /**
   * Return an instance with the specified derived request attribute.
   *
   * This method allows setting a single derived request attribute as
   * described in getAttributes().
   *
   * This method MUST be implemented in such a way as to retain the
   * immutability of the message, and MUST return an instance that has the
   * updated attribute.
   *
   * @see getAttributes()
   * @param string $name The attribute name.
   * @param mixed $value The value of the attribute.
   * @return static
   */

  public function withAttribute(string $name, $value)
  {
    $new = clone $this;
    $new->attributes[$name] = $value;

    return $new;
  }


  /**
   * Return an instance that removes the specified derived request attribute.
   *
   * This method allows removing a single derived request attribute as
   * described in getAttributes().
   *
   * This method MUST be implemented in such a way as to retain the
   * immutability of the message, and MUST return an instance that removes
   * the attribute.
   *
   * @see getAttributes()
   * @param string $name The attribute name.
   * @return static
   */
  
  public function withoutAttribute(string $name)
  {
    if (!array_key_exists($name, $this->attributes)) {
      return $this;
    }

    $new = clone $this;
    unset($new->attributes[$name]);

    return $new;
  }
}
