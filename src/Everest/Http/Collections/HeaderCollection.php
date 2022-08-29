<?php

declare(strict_types=1);

/*
 * This file is part of ieUtilities HTTP.
 *
 * (c) 2016 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\Http\Collections;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Stringable;

class HeaderCollection implements
    ParameterCollectionInterface,
    Countable,
    IteratorAggregate,
    Stringable
{
    /**
     * The parameter cache as key-value-storage.
     */
    private array $headers = [];

    /**
     * Constructor
     *
     * @param array $headers
     *    The initial headers in this collection
     */
    public function __construct(array $headers = [])
    {
        foreach ($headers as $name => $value) {
            $this->set($name, $value);
        }
    }

    /**
     * Returns string representation of this collection for debug proposes
     */
    public function __toString(): string
    {
        $string = '';

        foreach ($this->headers as $header) {
            [$name, $values] = $header;
            $string .= sprintf("%s: %s\r\n", $name, implode(', ', $values));
        }

        return $string;
    }

    public function has(string $name): bool
    {
        return isset($this->headers[self::normalizeHeaderName($name)]);
    }

    public function get(string $name)
    {
        return $this->headers[self::normalizeHeaderName($name)][1] ?? [];
    }

    public function set(string $name, $value, array $options = []): static
    {
        $this->headers[self::normalizeHeaderName($name)] = [$name, self::parseHeaderValue($value)];
        return $this;
    }

    public function with(string $name, $value, array $options = []): static
    {
        $parsed = self::parseHeaderValue($value);

        if ($this->get($name) === $parsed) {
            return $this;
        }

        $new = clone $this;
        $new->set($name, $value);

        return $new;
    }

    public function push(string $name, $value): static
    {
        $parsed = self::parseHeaderValue($value);

        if (empty($parsed)) {
            return $this;
        }

        $normalized = self::normalizeHeaderName($name);

        $this->headers[$normalized][1] = array_merge(
            $this->headers[$normalized][1] ?? [],
            $parsed
        );

        return $this;
    }

    public function withAdded(string $name, $value): static
    {
        $parsed = self::parseHeaderValue($value);

        if (empty($parsed)) {
            return $this;
        }

        $normalized = self::normalizeHeaderName($name);

        $new = clone $this;
        if (! isset($new->headers[$normalized])) {
            $new->headers[$normalized] = [$name, []];
        }

        $new->headers[$normalized][1] = array_merge(
            $new->headers[$normalized][1],
            $parsed
        );

        return $new;
    }


    public function delete(string $name): static
    {
        unset($this->headers[self::normalizeHeaderName($name)]);
        return $this;
    }

    public function without(string $name): static
    {
        if (! $this->has($name)) {
            return $this;
        }

        $new = clone $this;
        unset($new->headers[self::normalizeHeaderName($name)]);

        return $new;
    }

    public function toArray(): array
    {
        $headers = [];

        foreach ($this->headers as $header) {
            $headers[$header[0]] = $header[1];
        }

        return $headers;
    }

    /**
     * Gets the parameter count of this collection to satisfy the Countable interface.
     */
    public function count(): int
    {
        return count($this->headers);
    }

    /**
     * Creates a new ArrayIterator to satisfy the IteratorAggregate interface.
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->toArray());
    }

    private static function parseHeaderValue($value): array
    {
        switch (true) {
            case is_string($value):
                $value = explode(',', $value);
                // no break
            case is_array($value):
                return array_filter(array_map('trim', $value));
        }

        throw new \InvalidArgumentException(sprintf(
            'Header must be type of string or array but %s given.',
            get_debug_type($value)
        ));
    }

    /**
     * @see RFC 822 Chapter 3.2 - 1*<any CHAR, excluding CTLs, SPACE, and ":">
     *
     * @throws InvalidArgumentException
     *    If given name is not a valid header name
     *
     * @param  string $name
     *    The header name to normalize
     *
     *    The normalized header name
     */
    private static function normalizeHeaderName(string $name): string
    {
        if (preg_match('/^[^\x00-\x1F :]+$/', $name) === 0) {
            throw new \InvalidArgumentException(sprintf(
                '%s is not a valid header name.',
                $name
            ));
        }

        return strtolower($name);
    }
}
