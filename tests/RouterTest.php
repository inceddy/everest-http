<?php

declare(strict_types=1);

use Everest\Http\HttpException;
use Everest\Http\Requests\ServerRequest;
use Everest\Http\Responses\Response;
use Everest\Http\Responses\ResponseInterface;
use Everest\Http\Router;
use Everest\Http\Uri;

// Load middleware class
require __DIR__ . '/fixtures/Middleware.php';

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */

class RouterTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->request = new ServerRequest(
            ServerRequest::HTTP_ALL,
            Uri::from('http://steingrebe.de/prefix/test?some=value#hash')
        );
    }

    public function getRequest(string $uri, int $methods = null)
    {
        return new ServerRequest(
            $methods ?? ServerRequest::HTTP_ALL,
            Uri::from($uri)
        );
    }

    public function testEmptyResultLeedsToException()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);

        $test = $this;
        $router = new Router();
        $router->get('prefix/test', function () use ($test) {
        })->handle($this->request);
    }

    public function testUndefinedRouteLeedsToException()
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(404);

        $router = (new Router())->handle($this->request);
    }

    public function testRoutingHost()
    {
        $router = new Router();
        $router
            ->context('', function ($router) {
                $router
                    ->host('foo.de')
                    ->get('/prefix/test', function () {
                  $this->assertTrue(false);
                  return '';
              });
            })
            ->context('', function ($router) {
                $router
                    ->host('steingrebe.de')
                    ->get('/prefix/test', function () {
                  $this->assertTrue(true);
                  return '';
              });
            })
            ->handle($this->request);
    }

    public function testRoutingGet()
    {
        $test = $this;
        $router = new Router();
        $router->get('prefix/test', function () use ($test) {
            $test->assertTrue(true);
            return 'Response!';
        })->handle($this->request);
    }

    public function testRoutingPost()
    {
        $test = $this;
        $router = new Router();
        $router->post('prefix/test', function () use ($test) {
            $test->assertTrue(true);
            return 'Response!';
        })->handle($this->request);
    }

    public function testRoutingRequest()
    {
        $test = $this;
        $router = new Router();
        $router->request('prefix/test', ServerRequest::HTTP_ALL, function () use ($test) {
            $test->assertTrue(true);
            return 'Response!';
        })->handle($this->request);
    }

    public function testRoutingPatch()
    {
        $test = $this;
        $router = new Router();
        $router->patch('prefix/test', function () use ($test) {
            $test->assertTrue(true);
            return 'Response!';
        })->handle($this->request);
    }

    public function testContext()
    {
        $test = $this;
        $router = new Router([
            'debug' => true,
        ]);
        $router->context('prefix', function (Router $router) use ($test) {
            $router->get('test', function () use ($test) {
                $test->assertTrue(true);
                return 'Response!';
            });
        })->handle($this->request);
    }

    public function testUnprefixedCOntext()
    {
        $router = new Router([
            'debug' => true,
        ]);
        $router->context(function (Router $router) {
            $router->get('/prefix/test', function () {
                $this->assertTrue(true);
                return '';
            });
        })->handle($this->request);
    }

    public function testNestedContext()
    {
        $test = $this;
        $router = new Router([
            'debug' => true,
        ]);
        $router->context('prefix', function (Router $router) use ($test) {
            $router->context('test', function (Router $router) use ($test) {
                $router->get('/', function () use ($test) {
                    $test->assertTrue(true);
                    return 'Response!';
                });
            });
        })->handle($this->request);
    }

    public function testContextWithVariables()
    {
        $test = $this;
        $router = new Router([
            'debug' => true,
        ]);
        $router
            ->before(function (Closure $next, ServerRequest $request) {
                return $next($request->withUri(
                    $request->getUri()->withPathPrepend('blaa')
                ));
            })
            ->get('/blaa/blub', function () use ($test) {
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
        $router = new Router([
            'debug' => true,
        ]);
        $result =
    $router
      // Closure middleware
        ->before(function (Closure $next, ServerRequest $request) {
            return $next($request->withAttribute('attr1', 1));
        })
      // Object middleware
        ->before(new Middleware('attr2', 2))
        ->after(function (Closure $next, $response) {
            return $response->withHeader('test', 'value');
        })
        ->get('prefix/test', function (ServerRequest $request) {
            $this->assertSame(1, $request->getAttribute('attr1'));
            $this->assertSame(2, $request->getAttribute('attr2'));
            $this->assertEquals([], $request->getAttribute('parameter'));

            return 'Response';
        })
        ->handle($this->request);

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame('value', $result->getHeaderLine('test'));
    }

    public function testNestedMiddleware()
    {
        $router = new Router();
        $router
      // Root context middleware
            ->before(new Middleware('attr1', 1))
            ->context('prefix', function (Router $router) {
                $router
          // Sub context middleware
                    ->before(new Middleware('attr2', 2))
                    ->get('{var}', function (ServerRequest $request) {
                  $this->assertSame('test', $request->getAttribute('var'));
                  $this->assertSame(1, $request->getAttribute('attr1'));
                  $this->assertSame(2, $request->getAttribute('attr2'));

                  return 'Middleware Response!';
              });
            })
            ->handle($this->request);
    }

    public function testModifingNestedMiddleware()
    {
        $router = new Router();
        $router
      // Root context middleware
            ->before(function ($next, $request) {
                $uri = $request->getUri()->withPath('prefix/test-mod');
                return $next($request->withUri($uri));
            })
            ->context('prefix', function (Router $router) {
                $router
                    ->get('{var}', function (ServerRequest $request) {
                  $this->assertSame('test-mod', $request->getAttribute('var'));
                  return 'Middleware Response!';
              });
            })
            ->handle($this->request);
    }

    public function testDefaultHandlerOnEmptyResult()
    {
        $calledHandler = false;
        $calledDefault = false;
        $response = (new Router())
            ->otherwise(function () use (&$calledDefault) {
                $calledDefault = true;
                return 'not-empty-result';
            })
            ->get('prefix/test', function () use (&$calledHandler) {
                $calledHandler = true;
            })
            ->handle($this->request);

        $this->assertTrue($calledHandler && $calledDefault);
    }

    public function testDefaultHandlerOnMissingMatch()
    {
        $calledHandler = false;
        $calledDefault = false;
        $response = (new Router())
            ->otherwise(function () use (&$calledDefault) {
                $calledDefault = true;
                return 'not-empty-result';
            })
            ->get('this/does/not/match', function () use (&$calledHandler) {
                $calledHandler = true;
            })
            ->handle($this->request);

        $this->assertTrue($calledDefault);
        $this->assertFalse($calledHandler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame('not-empty-result', (string) $response->getBody());
    }

    public function testBeforeMiddlewareMustReturnResponseOrRequest()
    {
        $this->expectException(\RuntimeException::class);
        $router = (new Router())
            ->before(function () {
                return 'string';
            })
            ->handle($this->request);
    }

    public function testBeforeMiddlewareReturningResponse()
    {
        $result = $router = (new Router())
            ->before(function () {
                return new Response('content');
            })
            ->handle($this->request);

        $this->assertSame('content', $result->getBody()->getContents());
    }

    public function testErrorHandling()
    {
        $message = '';

        (new Router())
            ->get('prefix/test', function () {
                throw new \Exception('error');
            })
            ->error(function ($error) use (&$message) {
                $message = $error->getMessage();
                return ''; // Prevent invalid result exception
            })
            ->handle($this->request);

        $this->assertSame('error', $message);
    }

    public function testErrorHandlingInNestedContext()
    {
        $message = '';

        (new Router())
            ->context('prefix', function (Router $router) {
                $router
                    ->error(function ($error) {
                  throw new \Exception($error->getMessage() . '-prefix');
              })
                    ->context('test', function (Router $router) {
                  $router
                      ->error(function ($error) {
                      throw new \Exception($error->getMessage() . '-test');
                  })
                      ->get('/', function () {
                      throw new \Exception('initial');
                  });
              });
            })
            ->error(function ($error) use (&$message) {
                $message = $error->getMessage() . '-root';
                return ''; // Prevent invalid result exception
            })
            ->handle($this->request);

        $this->assertSame('initial-test-prefix-root', $message);
    }
}
