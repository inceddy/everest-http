<?php

declare(strict_types=1);

use Everest\Http\Cookie;
use Everest\Http\Responses\Response;
use Everest\Http\Stream;

class ResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testHeaders()
    {
        // Testing withHeader & withHeaderAdded
        $response = (new Response())
            ->withHeader('single', 'bar')
            ->withHeader('multi-string', 'bar1, bar2')
            ->withHeader('multi-array', ['bar1', 'bar2'])
            ->withHeader('stack', 'bar1, bar2');

        $this->assertNotSame($response, $response2 = $response->withAddedHeader('stack', ['bar3', 'bar4']));

        $response = $response2;

        $this->assertSame($response, $response->withHeader('single', 'bar'));
        $this->assertSame($response, $response->withAddedHeader('single', ''));
        $this->assertSame($response, $response->withoutHeader('unknown'));

        $this->assertEquals('bar1, bar2', $response->getHeaderLine('multi-string'));

        // Testing hasHeader
        $this->assertFalse($response->hasHeader('unknown'));
        $this->assertTrue($response->hasHeader('single'));

        // Testing getHeader
        $this->assertEquals(['bar1', 'bar2'], $response->getHeader('multi-string'));

        // Testing withoutHeader & getHeaders
        $response2 = $response->withoutHeader('stack');
        $this->assertEquals([
            'single' => ['bar'],
            'multi-string' => ['bar1', 'bar2'],
            'multi-array' => ['bar1', 'bar2'],
        ], $response2->getHeaders());
    }

    public function testProtocolVersion()
    {
        $response = new Response();
        // Default is 1.1
        $this->assertEquals('1.1', $response->getProtocolVersion());
        $this->assertSame($response, $response->withProtocolVersion('1.1'));

        $this->assertNotSame($response, $response2 = $response->withProtocolVersion('2.0'));
        $this->assertEquals('2.0', $response2->getProtocolVersion());
    }

    public function testInvalidProtocolVersion()
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Response())->withProtocolVersion('3.6');
    }

    public function testBody()
    {
        $response = new Response('some-body');

        $this->assertInstanceOf(Stream::class, $response->getBody());
        $this->assertEquals('some-body', (string) $response->getBody());

        $this->assertNotSame($response, $response2 = $response->withBody(Stream::from('some-other-body')));
        $this->assertEquals('some-other-body', (string) $response2->getBody());
    }

    public function testStatusCode()
    {
        $response = new Response();
        // Default
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());

        // Remain unchanged
        $this->assertSame($response, $response->withStatus(200));

        // Clone on change
        $this->assertNotSame($response, $response2 = $response->withStatus(404, 'Foo Bar'));
        $this->assertSame(404, $response2->getStatusCode());
        $this->assertSame('Foo Bar', $response2->getReasonPhrase());
    }

    public function testInvalidStatusCode()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Response(null, 5000);
    }

    public function testCookie()
    {
        $response = (new Response())->withCookie(
            new Cookie('name', 'value')
        );

        $this->assertStringContainsString('Set-Cookie: name=value', (string) $response);
    }

    public function testMessage()
    {
        $this->assertEquals('HTTP/1.1 200 OK', (string) new Response());
    }
}
