<?php

declare(strict_types=1);

use Everest\Http\Route;
use Everest\Http\Uri;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class RouteTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->uri = $this->getMockBuilder(Uri::class)
            ->getMock();

        $this->uri->method('getPath')
            ->willReturn('test/path/user123');

        $this->uriWithSpecialChars = $this->getMockBuilder(Uri::class)
            ->getMock();

        $this->uriWithSpecialChars->method('getPath')
            ->willReturn('test/%C3%96%C3%9F%C3%A4');
    }

    public function testRouteIgnoresSlashes()
    {
        $route = new Route('/test/path/user123/');
        $this->assertTrue($route->parse($this->uri) !== null);
    }

    public function testRouteWildcard()
    {
        $route = new Route('test/path/*');
        $this->assertTrue($route->parse($this->uri) !== null);
    }

    public function testRouteVariables()
    {
        $route = new Route('test/{path}/{user}');
        $this->assertEquals([
            'path' => 'path',
            'user' => 'user123',
        ], $route->parse($this->uri));
    }

    public function testURLEncodedRouteVariables()
    {
        $route = new Route('test/{var}');
        $this->assertEquals([
            'var' => 'Ößä',
        ], $route->parse($this->uriWithSpecialChars));
    }

    public function testRouteVariableValidation()
    {
        $routeValid = (new Route('test/path/{user}'))
            ->validate('user', 'user\d+');
        $this->assertEquals([
            'user' => 'user123',
        ], $routeValid->parse($this->uri));

        // Invalid pattern
        $routeInvalid = (new Route('test/path/{user}'))
            ->validate('user', 'customer\d+');
        $this->assertEquals(null, $routeInvalid->parse($this->uri));
    }

    public function testRouteVariableMulti()
    {
        $uri = $this->getMockBuilder(Uri::class)
            ->getMock();
        $uri->method('getPath')
            ->willReturn('test/path/p200x300');

        $route = (new Route('test/path/p{width}x{height}'))
            ->validate('witdh', '\d+')
            ->validate('height', '\d+');

        $this->assertEquals([
            'width' => 200,
            'height' => 300,
        ], $route->parse($uri));
    }

    public function testTermination()
    {
        $uri = $this->getMockBuilder(Uri::class)->getMock();
        $uri->method('getPath')->willReturn('test/path/is/very/long');

        $route = new Route('test/path*');
        $this->assertEquals([], $route->parse($uri));
    }

    public function testShorthandValidation()
    {
        $uri = $this->getMockBuilder(Uri::class)->getMock();
        $uri->method('getPath')->willReturn('test/path/123');

        $route = new Route('test/path/{id|\d+}');
        $this->assertEquals([
            'id' => '123',
        ], $route->parse($uri));

        $route = new Route('test/path/{id|[a-z]+}');
        $this->assertEquals(null, $route->parse($uri));

        $route = new Route('test/path/{id|[a-z]+}');
        $this->assertEquals(null, $route->parse($uri));
    }
}
