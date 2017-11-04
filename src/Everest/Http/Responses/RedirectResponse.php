<?php

/*
 * This file is part of Everest.
 *
 * (c) 2016 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\Http\Responses;
use Everest\Http\Uri;
use Everest\Http\Stream;
use InvalidArgumentException;

class RedirectResponse extends Response
{

  /**
   * The redirection target
   * @var Everest\Http\Uri
   */
  
  private $redirectionTarget;

  private static function getRedirectionBody(Uri $uri) : Stream
  {
    return Stream::from(sprintf('<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="refresh" content="0; url=%1$s" />
    </head>
    <body>
        <p>Redirecting to <a href="%1$s">%1$s</a>.
    </body>
</html>', htmlspecialchars((string)$uri, ENT_QUOTES, 'UTF-8')));
  }

  /**
   * Constructor
   * Invokes a new HTTP redirect response
   *
   * @param Everest\Http\Uri $uri
   *    The redirection target uri
   * @param integer $code
   *    The response code
   * @param array   $headers
   *    The header name-value-pairs to set
   *
   * @return self
   */
    
  public function __construct(
    Uri    $uri, 
    int    $code = self::HTTP_FOUND, 
    array  $headers = [],
    string $protocolVersion = self::HTTP_VERSION_1_1
  ){
    $this->redirectionTarget = $uri;

    parent::__construct(self::getRedirectionBody($uri), $code, $headers, $protocolVersion);
    $this->headers->set('Location', (string)$uri);
  }

  public function getRedirectionTarget() : Uri
  {
      return $this->redirectionTarget;
  }

  public function withRedirectionTarget(Uri $uri)
  {
    if ((string) $uri == (string) $this->redirectionTarget) {
      return $this;
    }

    $new = $this->withHeader('Location', (string)$uri);
    $new->redirectionTarget = $uri;
    $new->body = self::getRedirectionBody($uri);

    return $new;
  }
}