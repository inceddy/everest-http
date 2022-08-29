<?php

declare(strict_types=1);

use Everest\Http\Responses\RedirectResponse;
use Everest\Http\Uri;

class RedirectResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testRedirect()
    {
        $uri = Uri::from('http://google.com');
        $response = new RedirectResponse($uri);

        // Default
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertSame($uri, $response->getRedirectionTarget());
        $this->assertTrue($response->hasHeader('location'));

        $this->assertSame($response, $response->withRedirectionTarget(Uri::from('http://google.com')));

        $this->assertNotSame(
            $response,
            $response2 = $response->withRedirectionTarget(Uri::from('https://google.de'))
        );

        $this->assertNotSame($uri, $response2->getRedirectionTarget());

        $this->assertStringContainsString('https://google.de', (string) $response2);
    }
}
