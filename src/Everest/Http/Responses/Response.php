<?php

declare(strict_types=1);

/*
 * This file is part of Everest.
 *
 * (c) 2016 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\Http\Responses;

use Everest\Http\Collections\HeaderCollection;
use Everest\Http\Cookie;
use Everest\Http\MessageTrait;
use Everest\Http\Stream;

class Response implements ResponseInterface, \Stringable
{
    use MessageTrait;

    /**
     * The reponse status code
     * @var integer
     */
    protected $statusCode;

    /**
     * The reason phrase
     * @var string
     */
    protected $reasonPhrase;

    /**
     * Constructor
     * Invokes a new HTTP response
     *
     * @param mixed   $body
     *    The response body
     * @param integer $code
     *    The response code
     * @param array   $headers
     *    The header name-value-pairs to set
     * @param string  $protocolVersion
     *    The protocol version
     *
     * @return self
     */
    public function __construct(
        $body = null,
        int $code = self::HTTP_OK,
        array $headers = [],
        string $protocolVersion = self::HTTP_VERSION_1_1
    ) {
        $this->body = Stream::from($body);
        $this->statusCode = self::validateStatusCode($code);
        $this->headers = new HeaderCollection($headers);
        $this->protocolVersion = self::validateProtocolVersion($protocolVersion);
    }

    /**
     * Transform response to string for debug proposes
     */
    public function __toString(): string
    {
        // HTTP header & body
        return trim(sprintf(
            "HTTP/%s %s %s\r\n",
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        ) . sprintf(
            "%s\r\n%s",
            (string) $this->headers,
            (string) $this->body
        ));
    }

    /**
     * Sets the HTTP status code
     *
     * @param integer $code  The status code
     *
     * @return self
     */
    public function withStatus(int $code, string $reasonPhrase = null)
    {
        $code = self::validateStatusCode($code);

        if ($code === $this->statusCode && $reasonPhrase === $this->reasonPhrase) {
            return $this;
        }

        $new = clone $this;

        $new->statusCode = $code;
        $new->reasonPhrase = $reasonPhrase;

        return $new;
    }

    /**
     * Returns the current status code.
     * @return integer
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Returns the current status message
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase ?: self::STATUS_CODE_MAP[$this->getStatusCode()];
    }


    public function withCookies(array $cookies)
    {
        return $this->withAddedHeader('Set-Cookie', array_map(fn (Cookie $cookie) => $cookie->toHeaderLine(), $cookies));
    }


    public function withCookie(Cookie $cookie)
    {
        return $this->withCookies([$cookie]);
    }

    /**
     * Sends the response headers to the client.
     *
     * @return self
     */
    public function sendHeaders()
    {
        if (headers_sent()) {
            throw new \RuntimeException('Headers already sent.');
        }

        foreach ($this->headers as $name => $values) {
            header(
                sprintf('%s: %s', $name, implode(', ', $values)),
                true,
                $this->statusCode
            );
        }

        header(sprintf(
            '%s %s %s',
            self::PROTOCOL_VERSION_MAP[$this->protocolVersion],
            $this->statusCode,
            $this->getReasonPhrase()
        ));

        return $this;
    }

    /**
     * Sends the response content to the client
     * @return self
     */
    public function sendContent()
    {
        echo $this->body;
        return $this;
    }


    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();

        return $this;
    }

    /**
     * Returns the given status code if its valid
     *
     * @param  int    $code
     *    The status code to validate
     */
    protected static function validateStatusCode(int $code): int
    {
        if (! array_key_exists($code, self::STATUS_CODE_MAP)) {
            throw new \InvalidArgumentException(sprintf('The HTTP status code %s is unknown.', $code));
        }

        return $code;
    }
}
