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

class ParameterCollection implements
    ParameterCollectionInterface,
    Countable,
    IteratorAggregate,
    Stringable
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

        foreach ($this->parameters as $name => $parameter) {
            $string .= sprintf("%s = %s\r\n", $name, $parameter);
        }

        return $string;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }

    public function get(string $name)
    {
        return $this->has($name) ? $this->parameters[$name] : null;
    }

    public function set(string $name, $value, array $options = []): static
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    public function with(string $name, $value, array $options = []): static
    {
        if ($value === $this->get($name)) {
            return $this;
        }

        $new = clone $this;
        return $new->set($name, $value);
    }

    public function push(string $name, $value): static
    {
        // Not yet set
        if (! $this->has($name)) {
            return $this->set($name, [$value]);
        }

        // Already set with array value
        if (is_array($this->parameters[$name])) {
            $this->parameters[$name][] = $value;
            return $this;
        }

        // Set with single value
        $this->parameters[$name] = [$this->parameters[$name], $value];
        return $this;
    }

    public function withAdded(string $name, $value): static
    {
        $new = clone $this;
        return $new->push($name, $value);
    }

    public function delete(string $name): static
    {
        unset($this->parameters[$name]);
        return $this;
    }

    public function without(string $name): static
    {
        if (! $this->has($name)) {
            return $this;
        }

        $new = clone $this;
        return $new->delete($name);
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
