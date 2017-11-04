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

interface RequestInterface {
  public function getRequestTarget() : string;

  public function withRequestTarget(string $target);

  public function getMethod() : string;

  public function getMethodFlag() : int;

  public function withMethod($method);

  public function isMethod($method) : bool;

  /**
   * Tests if request method is POST.
   * Alias for Request::isMethod(Request::HTTP_POST).
   *
   * @return boolean
   */
  
  public function isMethodPost() : bool;


  /**
   * Tests if request method is GET.
   * Alias for Request::isMethod(Request::HTTP_GET).
   *
   * @return boolean
   */

  public function isMethodGet() : bool;

  public function getUri() : Uri;

  public function withUri(Uri $uri, bool $preserveHost = false);
}