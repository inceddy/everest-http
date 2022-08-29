<?php

declare(strict_types=1);

use Everest\Http\Uri;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class UriTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor()
    {
        $uri = new Uri([
            'scheme' => 'stg',
            'host' => 'some-hostname.de',
        ]);
        $this->assertSame('stg', $uri->getScheme());
        $this->assertSame('some-hostname.de', $uri->getHost());
    }

    public function testFrom()
    {
        // From array
        $uri = Uri::from([
            'scheme' => 'stg',
            'host' => 'some-hostname.de',
        ]);
        $this->assertSame('stg://some-hostname.de', (string) $uri);

        // From string
        $uri = Uri::from('stg://some-hostname.de');
        $this->assertSame('stg://some-hostname.de', (string) $uri);
    }

    public function testInvalidFrom()
    {
        $this->expectException(\InvalidArgumentException::class);

        Uri::from(null);
    }

    public function uriStringProvider()
    {
        return [
            ['https://username:password@example.com:8080/some/path/somefile.php?id=some_id&cache=false#anchor'],
            ['https://example.com:8080/some/path/somefile.php?id=some_id&cache=false#anchor'],
            ['https://example.com/some/path/somefile.php?id=some_id&cache=false#anchor'],
            ['https://example.com/some/path/somefile.php?id=some_id&cache=false'],
            ['https://example.com/some/path/somefile.php'],
            ['https://example.com/some/path'],
        ];
    }

    /**
     * @dataProvider uriStringProvider
     */
    public function testFromString($uriString)
    {
        $uriObject = Uri::from($uriString);
        $this->assertSame($uriString, (string) $uriObject);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidFromString()
    {
        $this->expectException(\InvalidArgumentException::class);

        Uri::fromString('http://user@:80');
    }

    public function testDefaultSchemeAndHost()
    {
        $uriString = 'this-is.nonesense';
        $uriObject = Uri::from($uriString);

        $this->assertSame('http://localhost/this-is.nonesense', (string) $uriObject);
    }

    public function testScheme()
    {
        $uri = Uri::from('https://example.com');
        $this->assertSame('https', $uri->getScheme());

        // Remain unchanged
        $this->assertSame($uri, $uri->withScheme('hTtpS'));

        // Clone on change
        $this->assertNotSame($uri, $uri2 = $uri->withScheme('ftp'));
        $this->assertSame('ftp', $uri2->getScheme());
    }

    public function testUserInfo()
    {
        $uri = Uri::from('https://username:password@example.com');
        $this->assertSame('username:password', $uri->getUserInfo());

        // Remain unchanged
        $this->assertSame($uri, $uri->withUserInfo('username', 'password'));

        // Clone on change
        $this->assertNotSame($uri, $uri2 = $uri->withUserInfo('username'));
        $this->assertSame('username', $uri2->getUserInfo());

        $this->assertSame('', $uri->withUserInfo('', null)->getUserInfo());
    }

    public function testHostImmutable()
    {
        $uri = Uri::from('https://some-server.de');

        // Remain unchanged
        $this->assertSame($uri, $uri->withHost('some-server.de'));
        // Clone on change
        $this->assertNotSame($uri, $uri->withHost('some-other-server.de'));
    }

    public function hostProvider()
    {
        return [
            ['localhost',    'localhost'],
            ['ic3.local:8080', 'ic3.local'],
        ];
    }

    /**
     * @dataProvider hostProvider
     */
    public function testHost($host, $hostShould)
    {
        $uri = Uri::from($host);
        $this->assertSame($uri->getHost(), $hostShould);
    }

    public function testInvalidHost()
    {
        $this->expectException(\InvalidArgumentException::class);

        $uri = Uri::from('https://some-server.de');
        $uri->withHost('_-');
    }

    public function testPort()
    {
        $uri = Uri::from('https://some-server.de:8000');
        $this->assertSame(8000, $uri->getPort());

        // Remain unchanged
        $this->assertSame($uri, $uri->withPort(8000));

        // Clone on change
        $this->assertNotSame($uri, $uri2 = $uri->withPort(null));
        $this->assertSame(null, $uri2->getPort());

        // Remove known ports if matching with scheme
        $this->assertSame('https://some-server.de', (string) $uri->withPort('443'));

        // Derive prot from scheme if known
        $this->assertSame(21, $uri->withScheme('ftp')->getPort());
    }

    public function testInvalidPort()
    {
        $this->expectException(\InvalidArgumentException::class);

        $uri = Uri::from('https://some-server.de:8080');
        $uri->withPort(100000);
    }

    public function testPath()
    {
        // Access url path
        $uri = Uri::from('https://example.com/some/path');
        $this->assertSame('some/path', $uri->getPath());
        $this->assertSame(['some', 'path'], $uri->getPathArray());

        /// Remain unchanged
        $this->assertSame($uri, $uri->withPath('some/path'));

        // Clone on change
        $this->assertNotSame($uri, $uri2 = $uri->withPath('some/other/path'));
        $this->assertSame('some/other/path', $uri2->getPath());

        $this->assertNotSame($uri, $uri2 = $uri->withPathPrepend('prefix'));
        $this->assertSame('prefix/some/path', $uri2->getPath());

        $this->assertNotSame($uri, $uri2 = $uri->withPathAppend('suffix'));
        $this->assertSame('some/path/suffix', $uri2->getPath());
    }

    public function testQuery()
    {
        $uri = Uri::from('https://some-server.de?some=value');

        // Remain unchanged
        $this->assertSame($uri, $uri->withQuery('some=value'));
        $this->assertSame($uri, $uri->withQuery([
            'some' => 'value',
        ]));
        // Clone on change
        $this->assertNotSame($uri, $uri2 = $uri->withQuery('foo=bar'));
        $this->assertSame('foo=bar', $uri2->getQuery());
        $this->assertNotSame($uri, $uri2 = $uri->withQuery([
            'foo' => 'bar',
        ]));
        $this->assertSame('foo=bar', $uri2->getQuery());

        $this->assertEmpty($uri->withQuery([])->getQuery());
        $this->assertEmpty($uri->withQuery('')->getQuery());

        $this->assertSame([
            'foo' => 'bar',
        ], $uri2->getQueryArray());
    }

    public function testInvalidQuery()
    {
        $this->expectException(\InvalidArgumentException::class);

        $uri = Uri::from('https://some-server.de');
        $uri->withQuery(100000);
    }

    public function testMergedQuery()
    {
        $uri = Uri::from('https://some-server.de?some=value');

        // Remain unchanged
        $this->assertSame($uri, $uri->withMergedQuery('some=value'));
        $this->assertSame($uri, $uri->withMergedQuery([
            'some' => 'value',
        ]));
        // Clone on change
        $this->assertNotSame($uri, $uri2 = $uri->withMergedQuery('foo=bar'));
        $this->assertSame('some=value&foo=bar', $uri2->getQuery());
        $this->assertNotSame($uri, $uri2 = $uri->withMergedQuery([
            'foo' => 'bar',
        ]));
        $this->assertSame('some=value&foo=bar', $uri2->getQuery());
    }

    public function testInvalidMergedQuery()
    {
        $this->expectException(\InvalidArgumentException::class);

        $uri = Uri::from('https://some-server.de');
        $uri->withMergedQuery(100000);
    }

    public function testFragment()
    {
        $uri = Uri::from('https://example.com/#anchor');
        $this->assertSame('anchor', $uri->getFragment());

        // Remain unchanged
        $this->assertSame($uri, $uri->withFragment('anchor'));
        // Clone on change
        $this->assertNotSame($uri, $uri2 = $uri->withFragment('anchor2'));
        $this->assertSame('anchor2', $uri2->getFragment());
    }

    public function testEquals()
    {
        $this->assertTrue(
            Uri::from('http://test.de')->equals(Uri::from('http://test.de'))
        );
    }

    public function testArrayAccess()
    {
        $uri = Uri::from('http://test.de/a/b/c');

        $this->assertSame('a', $uri[0]);
        $this->assertNull($uri[3]);

        $this->assertTrue(isset($uri[0]));
        $this->assertFalse(isset($uri[3]));
    }

    public function testArrayAccessOffsetSet()
    {
        $this->expectException(\BadMethodCallException::class);

        $uri = Uri::from('http://test.de/a/b/c');
        $uri[0] = 'foo';
    }

    public function testArrayAccessOffsetUnset()
    {
        $this->expectException(\BadMethodCallException::class);

        $uri = Uri::from('http://test.de/a/b/c');
        unset($uri[0]);
    }
}
