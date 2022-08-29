<?php

declare(strict_types=1);


/*
 * This file is part of Everest.
 *
 * (c) 2017 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\Http;

use Everest\Http\Collections\HeaderCollection;
use Everest\Http\Responses\Response;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

trait MessageTrait
{
    /**
     * The HTTP protocol version
     * @var string
     */
    protected $protocolVersion;

    /**
     * Headers
     * @var HeaderCollection
     */
    protected $headers;

    /**
     * The body of the message
     * @var Psr\Http\StreamInterface
     */
    protected $body;

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): Response
    {
        $version = self::validateProtocolVersion($version);

        if ($version === $this->protocolVersion) {
            return $this;
        }

        $new = clone $this;
        $new->protocolVersion = $version;

        return $new;
    }

    public function getHeaders(): array
    {
        return $this->headers->toArray();
    }

    public function hasHeader(string $name): bool
    {
        return $this->headers->has($name);
    }

    public function getHeader(string $name): array
    {
        return $this->headers->get($name);
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws InvalidArgumentException for invalid header names or values.
     */
    public function withHeader(string $name, string|array $value)
    {
        $newHeaders = $this->headers->with($name, $value);

        if ($this->headers === $newHeaders) {
            return $this;
        }

        $new = clone $this;
        $new->headers = $newHeaders;

        return $new;
    }

    public function withAddedHeader(string $name, $value): Response
    {
        $newHeaders = $this->headers->withAdded($name, $value);

        if ($newHeaders === $this->headers) {
            return $this;
        }

        $new = clone $this;
        $new->headers = $newHeaders;

        return $new;
    }

    /**
     * Return an instance without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the named header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return static
     */
    public function withoutHeader(string $name)
    {
        $newHeaders = $this->headers->without($name);

        if ($newHeaders === $this->headers) {
            return $this;
        }

        $new = clone $this;
        $new->headers = $newHeaders;

        return $new;
    }

    /**
     *  Gets the body of the message.
     *
     * @return Psr\Http\StreamInterface Returns the body as a stream.
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     *  Return an instance with the specified message body.
     *
     *  The body MUST be a StreamInterface object.
     *
     *  This method MUST be implemented in such a way as to retain the
     *  immutability of the message, and MUST return a new instance that has the
     *  new body stream.
     *
     * @return static
     *
     * @throws InvalidArgumentException When the body is not valid.
     */
    public function withBody(Stream $body)
    {
        $new = clone $this;
        $new->body = $body;

        return $new;
    }

    private static function validateProtocolVersion(string $protocolVersion): string
    {
        if (! isset(self::PROTOCOL_VERSION_MAP[$protocolVersion])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid protocol version %s given. Use 1.0, 1.1 or 2.0 instead.',
                $protocolVersion
            ));
        }

        return $protocolVersion;
    }
}
