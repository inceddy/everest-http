<?php

declare(strict_types=1);

use Everest\Http\Cookie;

class CookieTest extends \PHPUnit\Framework\TestCase
{
    public function testExpireVariants()
    {
        $now = new DateTime('now');
        $delta = new DateInterval('P2D');

        // As timestamp
        $cookie = new Cookie('name', 'value', false, false, $now->format('U'));
        $this->assertEquals($now->format('U'), $cookie->getExpires());

        // As DateTime
        $cookie = new Cookie('name', 'value', false, false, $now);
        $this->assertEquals((int) $now->format('U'), $cookie->getExpires());

        // As DateInterval
        $cookie = new Cookie('name', 'value', false, false, $delta);
        $this->assertEquals((int) $now->add($delta)->format('U'), $cookie->getExpires());

        // As string
        $cookie = new Cookie('name', 'value', false, false, '10-10-10');
        $this->assertEquals(strtotime('10-10-10'), $cookie->getExpires());
    }

    public function testInvalidExpireValue()
    {
        $this->expectException(\Exception::class);
        $cookie = new Cookie('name', 'value', false, false, 'foo bar');
    }

    public function testInvalidName()
    {
        $this->expectException(InvalidArgumentException::class);
        $cookie = new Cookie("\n", 'value');
    }

    public function testCookieToHeaderLine()
    {
        $cookie = new Cookie('name', 'some value', true, true, new DateTime('tomorrow'), '*.vds.de', '/path/some');
        $headerLine = (string) $cookie;
        $this->assertNotEmpty($headerLine);
    }

    public function testGetters()
    {
        // Basic
        $cookie = new Cookie('name', 'some value');
        $this->assertEquals('name', $cookie->getName());
        $this->assertEquals('some value', $cookie->getValue());
        $this->assertNull($cookie->getDomain());
        $this->assertNull($cookie->getPath());
        $this->assertEquals(0, $cookie->getExpires());
        $this->assertFalse($cookie->isSecure());
        $this->assertFalse($cookie->isHttpOnly());

        // Full
        $cookie = new Cookie('name', 'some value', true, true, new DateTime('tomorrow'), '*.vds.de', '/path/some');
        $this->assertEquals('*.vds.de', $cookie->getDomain());
        $this->assertEquals('/path/some', $cookie->getPath());
        $this->assertTrue(is_int($cookie->getExpires()));
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
    }
}
