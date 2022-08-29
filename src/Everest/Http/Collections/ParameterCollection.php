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

class ParameterCollection implements
    ParameterCollectionInterface,
    Countable,
    IteratorAggregate,
    \Stringable
{
    /**
     * The parameter cache as key-value-storage.
     */
    private array $parameters;

    /**
     * Constructor
     *
     * @param array $parameter
     *    The initial parameters in this collection
     */
    public function __construct(array $parameter = [])
    {
        $this->parameters = $parameter;
    }

    /**
     * Returns string representation of this collection for debug proposes
     */
    public function __toString(): string
    {
        $string = '';

        foreach ($this->parameters as $key => $parameter) {
            $string .= sprintf("%s = %s\r\n", $key, $parameter);
        }

        return $string;
    }


    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }


    public function get(string $key)
    {
        return $this->has($key) ? $this->parameters[$key] : null;
    }


    public function set(string $key, $value, array $options = [])
    {
        $this->parameters[$key] = $value;
        return $this;
    }


    public function with(string $key, $value, array $options = [])
    {
        if ($value === $this->get($key)) {
            return $this;
        }

        $new = clone $this;
        return $new->set($key, $value);
    }


    public function push(string $key, $value)
    {
        // Not yet set
        if (! $this->has($key)) {
            return $this->set($key, [$value]);
        }

        // Already set with array value
        if (is_array($this->parameters[$key])) {
            $this->parameters[$key][] = $value;
            return $this;
        }

        // Set with single value
        $this->parameters[$key] = [$this->parameters[$key], $value];
        return $this;
    }


    public function withAdded(string $key, $value)
    {
        $new = clone $this;
        return $new->push($key, $value);
    }


    public function delete(string $key)
    {
        unset($this->parameters[$key]);
        return $this;
    }


    public function without(string $key)
    {
        if (! $this->has($key)) {
            return $this;
        }

        $new = clone $this;
        return $new->delete($key);
    }


    public function toArray(): array
    {
        return $this->parameters;
    }

    /**
     * Gets the parameter count of this collection to satisfy the Countable interface.
     */
    public function count(): int
    {
        return count($this->parameters);
    }

    /**
     * Creates a new ArrayIterator to satisfy the IteratorAggregate interface.
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->parameters);
    }
}
