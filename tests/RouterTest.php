<?php

use Everest\Http\Router;
use Everest\Http\Route;
use Everest\Http\Requests\ServerRequest;
use Everest\Http\Requests\Request;
use Everest\Http\Responses\Response;
use Everest\Http\Responses\ResponseInterface;
use Everest\Http\Uri;

// Load middleware class
require __DIR__ . '/fixtures/Middleware.php';

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class RouterTest extends \PHPUnit\Framework\TestCase {

  public function setUp()
  {
    $this->request = new ServerRequest(
      ServerRequest::HTTP_ALL, 
      Uri::from('http://steingrebe.de/prefix/test?some=value#hash')
    );
  }

  /**
   * @expectedException        \Everest\Http\HttpException
   * @expectedExceptionCode 404
   */

  public function testEmptyResultLeedsToException()
  {
    $test = $this;
    $router = new Router;
    $router->get('prefix/test', function() use ($test) {
    })->handle($this->request);
  }


  public function testRoutingGet()
  {
    $test = $this;
    $router = new Router;
    $router->get('prefix/test', function() use ($test) {
      $test->assertTrue(true);
      return 'Response!';
    })->handle($this->request);
  }

  public function testRoutingPost()
  {
    $test = $this;
    $router = new Router;
    $router->post('prefix/test', function() use ($test) {
      $test->assertTrue(true);
      return 'Response!';
    })->handle($this->request);
  }

  public function testRoutingRequest()
  {
    $test = $this;
    $router = new Router;
    $router->request('prefix/test', ServerRequest::HTTP_ALL, function() use ($test) {
      $test->assertTrue(true);
      return 'Response!';
    })->handle($this->request);
  }

  public function testContext()
  {
    $test = $this;
    $router = new Router(['debug' => true]);
    $router->context('prefix', function(Router $router) use ($test) {
      $router->get('test', function() use ($test) {
        $test->assertTrue(true);
        return 'Response!';
      });
    })->handle($this->request);
  }

  public function testNestedContext()
  {
    $test = $this;
    $router = new Router(['debug' => true]);
    $router->context('prefix', function(Router $router) use ($test) {
      $router->context('test', function(Router $router) use ($test) {
        $router->get('/', function() use ($test) {
          $test->assertTrue(true);
          return 'Response!';
        });
      });
    })->handle($this->request);
  }

  public function testContextWithVariables()
  {
    $test = $this;
    $router = new Router(['debug' => true]);
    $router
    ->before(function(Closure $next, ServerRequest $request) {
      return $next($request->withUri(
        $request->getUri()->withPathPrepend('blaa')
      ));
    })
    ->get('/blaa/blub',function() use ($test) {
      $test->assertTrue(true);
      return 'Response!';
    })
    ->handle(new ServerRequest(
      ServerRequest::HTTP_ALL, 
      Uri::from('http://steingrebe.de/blub')
    ));
  }

  public function testMiddleware()
  {
    $test = $this;
    $router = new Router(['debug' => true]);
    $result = 
    $router
      // Closure middleware
      ->before(function(Closure $next, ServerRequest $request) {
        return $next($request, 'A');
      })
      // Object middleware
      ->before(new Middleware('B'))
      ->after(function(Closure $next, $response){
        return $next($response . 'After');
      })
      ->get('prefix/test', function(ServerRequest $request, $add1, $add2) {
        $this->assertEquals('A', $add1);
        $this->assertEquals('B', $add2);
        $this->assertEquals([], $request->getAttribute('parameter'));

        return 'Response';
      })
      ->handle($this->request);

      $this->assertInstanceOf(ResponseInterface::CLASS, $result);
      $this->assertSame('ResponseAfter', (string)$result->getBody());
  }

  public function testNestedMiddleware()
  {
    $router = new Router;
    $router
      // Root context middleware
      ->before(new Middleware('arg1'))
      ->context('prefix', function(Router $router) {
        $router
          // Sub context middleware
          ->before(new Middleware('arg2'))
          ->get('test', function(ServerRequest $request, $arg2, $arg1) {
              $this->assertEquals('arg1', $arg1);
              $this->assertEquals('arg2', $arg2);

              return 'Middleware Response!';
          });
      })
      ->handle($this->request);
  }

  public function testDefaultHandlerOnEmptyResult()
  {
    $calledHandler = false;
    $calledDefault = false;
    $response = (new Router)
      ->otherwise(function() use (&$calledDefault) {
        $calledDefault = true;
        return 'not-empty-result';
      })
      ->get('prefix/test', function() use (&$calledHandler) {
        $calledHandler = true;
      })
      ->handle($this->request);

    $this->assertTrue($calledHandler && $calledDefault);
  }

  public function testDefaultHandlerOnMissingMatch()
  {
    $calledHandler = false;
    $calledDefault = false;
    $response = (new Router)
      ->otherwise(function() use (&$calledDefault) {
        $calledDefault = true;
        return 'not-empty-result';
      })
      ->get('this/does/not/match', function() use (&$calledHandler) {
        $calledHandler = true;
      })
      ->handle($this->request);

    $this->assertTrue($calledDefault);
    $this->assertFalse($calledHandler);

    $this->assertInstanceOf(ResponseInterface::CLASS, $response);
    $this->assertSame('not-empty-result', (string)$response->getBody());
  }
}
