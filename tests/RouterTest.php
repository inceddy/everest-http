<?php

use Everest\Http\Router;
use Everest\Http\Route;
use Everest\Http\Requests\ServerRequest;
use Everest\Http\Requests\Request;
use Everest\Http\Responses\Response;
use Everest\Http\Uri;

// Load middleware class
require __DIR__ . '/fixtures/Middleware.php';

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class RouterTest extends \PHPUnit_Framework_TestCase {

	public function setUp()
	{
		// Mock request
		$this->request = $this->getMockBuilder(ServerRequest::CLASS)
			->disableOriginalConstructor()
			->getMock();
		$this->request->method('getUri')->willReturn(Uri::from('http://steingrebe.de/prefix/test?some=value#hash'));
		$this->request->method('isMethod')->willReturn(true);
	}

  /**
   * @expectedException        Exception
   * @expectedExceptionMessage No matching route found
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

	public function testMiddleware()
	{
		$test = $this;
		$router = new Router(['debug' => true]);
		$router
			// Closure middleware
			->middleware(function(Closure $next, ...$args) {
				return $next('test', ...$args);
			})
			// Object middleware
			->middleware(new Middleware('test'))
			->get('prefix/test', function($arg1, $arg2, $request, $params) use ($test) {
				$this->assertEquals('test', $arg1);
				$this->assertEquals('test', $arg2);
				$this->assertInstanceOf(Request::CLASS, $request);
				$this->assertEquals([], $params);

				return 'Middleware Response!';
			})
			->handle($this->request);
	}

	public function testNestedMiddleware()
	{
		$router = new Router;
		$router
			// Root context middleware
			->middleware(new Middleware('arg1'))
			->context('prefix', function(Router $router) {
				$router
					// Sub context middleware
					->middleware(new Middleware('arg2'))
					->get('test', function($arg1, $arg2, $request, $params) {
							$this->assertEquals('test', $arg1);
							$this->assertEquals('test', $arg2);
							$this->assertInstanceOf(Request::CLASS, $request);
							$this->assertEquals([], $params);
							return 'Middleware Response!';
					});
			});
	}

	public function testDefaultHandlerOnEmptyResult()
	{
		$test = $this;
		(new Router)
			->otherwise(function() use ($test) {
				// Default handler is called
				$test->assertTrue(true);
				return 'not-empty-result';
			})
			->get('prefix/test', function() use ($test) {
				// Handler gets called but has no result
				$test->assertTrue(true);
			})
			->handle($this->request);
	}

	public function testDefaultHandlerOnMissingMatch()
	{
		$test = $this;
		$response = 
		(new Router)
			->otherwise(function() use ($test) {
				// Default handler is called
				$test->assertTrue(true);
				return 'not-empty-result';
			})
			->get('this/does/not/match', function() use ($test) {
				// Handler gets called never called!
				$test->assertTrue(false);
			})
			->handle($this->request);

		$this->assertInstanceOf(Response::CLASS, $response);
	}

	public function testDefaultHandlerOnException()
	{
		$test = $this;
		(new Router)
			->otherwise(function($request, $exception) use ($test) {
				$test->assertInstanceOf(Exception::CLASS, $exception);
				$test->assertEquals('Route error', $exception->getMessage());
				return 'not-empty-result';
			})
			->get('prefix/test', function() use ($test) {
				throw new Exception('Route error');
			})
			->handle($this->request);
	}
}