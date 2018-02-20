<?php

/*
 * This file is part of Everest.
 *
 * (c) 2016 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\Http\Tests;
use Everest\Http\Uri;

if (!class_exists('\PHPUnit\Framework\TestCase')) {
  die ('PHPUnit is missing');
}


class WebTestCase extends \PHPUnit\Framework\TestCase {

  public function setupWebRequest(Uri $uri = null)
  {
    $uri = $uri ?: Uri::from('http://example.com/some/path');

    $_SERVER = array_merge($_SERVER, array (
      'REMOTE_ADDR' => '80.154.19.222',
      'HTTP_HOST' => $uri->getHost(),
      'TZ' => 'MET',
      'PHP_FCGI_MAX_REQUESTS' => '100',
      'PHP_FCGI_CHILDREN' => '12',
      'PHP_FCGI_STARTUP_REQUESTS' => '2',
      'PHP_FCGI_CACHE' => '1',
      'DOCUMENT_ROOT' => '/home/strato/http/premium/rid/48/01/51894801/htdocs',
      'SCRIPT_FILENAME' => '/home/strato/http/premium/rid/48/01/51894801/htdocs/stg2016/app/request.php',
      'PHPRC' => '/home/strato/http/premium/rid/48/01/51894801/htdocs/stg2016/app/',
      'PHP_FCGI_IDLE' => '1',
      'SCRIPT_NAME' => '/request.php',
      'REQUEST_URI' => '/request.php',
      'QUERY_STRING' => '',
      'REQUEST_METHOD' => 'GET',
      'SERVER_PROTOCOL' => 'HTTP/1.1',
      'GATEWAY_INTERFACE' => 'CGI/1.1',
      'REMOTE_PORT' => '43300',
      'SERVER_ADMIN' => 'service@example.com',
      'SERVER_PORT' => $uri->getPort(),
      'SERVER_NAME' => $uri->getHost(),
      'SERVER_SOFTWARE' => 'Apache/2.2.31 (Unix)',
      'PATH' => '/usr/bin:/bin',
      'HTTP_CONNECTION' => 'close',
      'HTTP_UPGRADE_INSECURE_REQUESTS' => '1',
      'HTTP_COOKIE' => '__utma=212823254.556707476.1488799907.1494313935.1496390504.6; __utmz=212823254.1488799907.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); PHPSESSID=fo3e1nqqn30kl49c049ltvu0k3',
      'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, br',
      'HTTP_ACCEPT_LANGUAGE' => 'de,en-US;q=0.7,en;q=0.3',
      'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
      'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0',
      'RZ_php' => '56',
      'HTTPS' => 'on',
      'SCRIPT_URI' => $uri->toString(),
      'SCRIPT_URL' => $uri->getPath(),
      'RZ_path' => 'webr/d3/01/51894801',
      'RZ_a' => ':fcgi=1:php=56:spam=0:forcessl=301:crt=880530:tpl=strato-premium:Rproxy:Cpremium:quota=358400MB:',
      'RZ_n' => '51894801',
      'UNIQUE_ID' => 'WdTBjcCoLMwAADI3ZvMAAAE9',
      'FCGI_ROLE' => 'RESPONDER',
      'PHP_SELF' => '/request.php',
      'REQUEST_TIME_FLOAT' => microtime(true),
      'REQUEST_TIME' => time(),
      'argv' => 
      array (
      ),
      'argc' => 0,
    ));
  }

} 