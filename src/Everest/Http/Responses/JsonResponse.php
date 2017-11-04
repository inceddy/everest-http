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
use InvalidArgumentException;

class JsonResponse extends Response 
{

  /**
   * Constructor
   * Invokes a new HTTP JSON response
   *
   * @param mixed   $body
   *    The content to encode as JSON
   * @param integer $code
   *    The response code
   * @param array   $headers
   *    The header name-value-pairs to set
   *
   * @return self
   */
    
  public function __construct(
           $body = null, 
    int    $code = self::HTTP_OK, 
    array  $headers = [],
    string $protocolVersion = self::HTTP_VERSION_1_1
  ){
    if (false === $body = json_encode($body)) {
      throw new InvalidArgumentException(
        json_last_error_msg(),
        json_last_error()
      );
    }

    $headers = array_merge($headers, [
      'Content-Type' => 'application/json'
    ]);

    parent::__construct($body, $code, $headers, $protocolVersion);
  }
}