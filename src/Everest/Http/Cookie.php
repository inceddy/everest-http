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

/**
 * Cookie represenation.
 * @author Philipp Steingrebe <philipp@steingrebe.de>
 */

class Cookie {

  /**
   * The name of this cookie
   * @var string
   */
  
  private $name;


  /**
   * The value of this cookie
   * @var string
   */
  
  private $value;


  /**
   * The options of this cookie
   * @var bool
   */
  
  private $httpOnly;

  /**
   * Whether this cookie is secure or not
   * @var bool
   */
  
  private $secure;


  /**
   * When this cookie is going to expire (0 means never)
   * @var int
   */
  
  private $expires;


  /**
   * Domain this cookie is valid for
   * @var string
   */
  
  private $domain;


  /**
   * Path this cookie is valid for
   * @var string
   */
  
  private $path;

  /**
   * The SameSite attribute of the Set-Cookie HTTP response header allows you to declare 
   * if your cookie should be restricted to a first-party or same-site context.
   * @var string|null
   */

  private $sameSite;


  /**
   * Validates the name and returns it
   *
   * @param  string $name The name to validate
   * @return string
   */
  
  private static function validateName(string $name) : string
  {
    if (empty($name) || preg_match("/[=,; \t\r\n\013\014]/", $name)) {
      throw new \InvalidArgumentException(sprintf('Invalid name \'%s\'.', $name));
    }

    return $name;
  }

  /**
   * Convert expiration time to a Unix timestamp
   *
   * @param  mixed $expire
   * @return int
   */
  
  private static function validateExpires($expires) : int
  {
    if (is_numeric($expires)) {
      return (int)$expires;
    }

    // Add intervals to current type
    if ($expires instanceof \DateInterval) {
      $now = new \DateTime('now');
      return (int)$now->add($expires)->format('U');
    }

    if ($expires instanceof \DateTimeInterface) {
      return (int)$expires->format('U');
    } 

    $expires = strtotime($expires);

    if (false === $expires || -1 === $expires) {
       throw new \InvalidArgumentException('The expiration time is not valid.');
    }

    return $expires;
  }


  /**
   * Constructor
   *
   * @param string $name
   *   The name
   * @param string $value
   *   The value
   * @param bool $httpOnly
   *   Whether or not this cookie is only available with http
   * @param bool $secure
   *   Whether or not this cookie is only available with TLS
   * @param mixed $expires
   *   When this cookie is going to expire
   * @param string|null $domain
   *   The domain(s) this cookie will be available on
   * @param string $path
   *   The path this cookie will be available on
   */
  
  public function __construct(
    string $name, 
    string $value, 
    bool   $httpOnly = false, 
    bool   $secure   = false, 
           $expires  = 0,
    string $domain   = null,
    string $path     = null,
    string $sameSite = null
  )
  {
    $this->name  = self::validateName($name);
    $this->value = $value;

    $this->httpOnly = $httpOnly;
    $this->secure   = $secure;

    $this->expires = self::validateExpires($expires);

    $this->domain = $domain;
    $this->path   = $path;

    $this->sameSite = $sameSite;
  }


  /**
   * Returns the cookie name
   * @return string
   */
  
  public function getName() : string
  {
    return $this->name;
  }


  /**
   * Returns the cookie value
   * @return string
   */
  
  public function getValue() : string
  {
    return $this->value;
  }

  /**
   * Return the cookie path if set
   * @return int
   */

  public function getExpires() : int
  {
    return $this->expires;
  }


  /**
   * Return the cookie domain if set
   * @return string|null
   */

  public function getDomain() :? string
  {
    return $this->domain;
  }


  /**
   * Return the cookie path if set
   * @return string|null
   */
 
  public function getPath() :? string
  {
    return $this->path;
  }

  
  /**
   * Gets the same site option if set.
   * @return     string|null  The same site value
   */
  
  public function getSameSite() : ?string
  {
    return $this->sameSite;
  }


  /**
   * Return whether or not this cookie is only available on TLS
   * @return boolean
   */
  
  public function isSecure() : bool
  {
    return $this->secure;
  }


  /**
   * Return whether or not this cookie is only available on http
   * @return boolean
   */
  
  public function isHttpOnly() : bool
  {
    return $this->httpOnly;
  }


  /**
   * Returns headerline from this cookie
   * @return string
   */
  
  public function toHeaderLine() : string
  {
    $cookie = sprintf('%s=%s', $this->name, urlencode($this->value));
    
    if ($this->expires !== 0) {
      $cookie .= sprintf('; expires=%s', gmdate('D, d-M-Y H:i:s T', $this->expires));
    }

    if (!empty($this->path)) {
      $cookie .= sprintf('; path=%s', $this->path);
    }

    if (!empty($this->domain)) {
      $cookie .= sprintf('; domain=%s', $this->domain);
    }

    if (!empty($this->secure)) {
      $cookie .= '; secure';
    }

    if ($this->secure && !empty($this->sameSite)) {
      $cookie .= sprintf('; samesite=%s', $this->sameSite);
    }

    if (!empty($this->httpOnly)) {
      $cookie .= '; httponly';
    }

    return $cookie;
  }


  /**
   * Returns cookie as header string
   * @return string
   */
  
  public function __toString()
  {
    return $this->toHeaderLine();
  }
}
