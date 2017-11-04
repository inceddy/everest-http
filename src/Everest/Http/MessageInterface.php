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

interface MessageInterface {

	/**
	 * HTTP protocol versions
	 */
	
	public const HTTP_VERSION_1_0 = '1.0';
  public const HTTP_VERSION_1_1 = '1.1';
  public const HTTP_VERSION_2_0 = '2.0';


  /**
   * Http protocol version map
   */
  
  public const PROTOCOL_VERSION_MAP = [
    '1.0' => 'HTTP/1.0',
    '1.1' => 'HTTP/1.1',
    '2.0' => 'HTTP/2.0' 
  ];

  /**
   * The http request methods bit-encoded
   */
  
  public const HTTP_ALL     = 0xFF;

  public const HTTP_GET     = 0x01;
  public const HTTP_POST    = 0x02;
  public const HTTP_HEAD    = 0x04;
  public const HTTP_PUT     = 0x08;
  public const HTTP_DELETE  = 0x10;
  public const HTTP_TRACE   = 0x20;
  public const HTTP_OPTIONS = 0x40;
  public const HTTP_CONNECT = 0x80;


  /**
   * All allowed methods
   * @var string[]
   */
  
  public const HTTP_METHOD_MAP = [
    self::HTTP_GET     => 'GET',
    self::HTTP_POST    => 'POST',
    self::HTTP_HEAD    => 'HEAD',
    self::HTTP_PUT     => 'PUT',
    self::HTTP_DELETE  => 'DELETE',
    self::HTTP_TRACE   => 'TRACE',
    self::HTTP_OPTIONS => 'OPTIONS',
    self::HTTP_CONNECT => 'CONNECT',
    self::HTTP_ALL     => 'ALL'
  ];
}
