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

interface ParameterCollectionInterface
{
    public function __toString();

    /**
     * Returns whether or not the given name
     * is contained in this collection
     *
     * @param  string  $name
     *    The name to look for
     *
     * @return boolean
     */
    public function has(string $name): bool;

    /**
     * Returns the value behind the given name
     * or null if the name is not set
     *
     * @param  string $name
     *    The name of the value to retrieve
     *
     * @return mixed|null
     */
    public function get(string $name);

    /**
     * Sets a value on the given name
     *
     * @param string $name
     *    The name to set
     * @param mixed $value
     *    The value to set
     * @param array $options
     *    Optional options to be be uses for setting the value
     *    
     * @return static
     */
    public function set(string $name, $value, array $options = []): static;

    /**
     * Creates a new collection and sets the given name
     *
     * @param string $name
     *    The name to set
     * @param mixed $value
     *    The value to set
     * @param array $options
     *    Optional options to be be uses for setting the value
     *
     * @return static
     */
    public function with(string $name, $value, array $options = []): static;

    /**
     * Pushs a new value on the given name.
     *
     * If the name is not yet set it will be set with an
     *
     * @param string $name
     *    The name to set
     * @param mixed $value
     *    The value to set
     *
     * @return static
     */
    public function push(string $name, $value): static;

    /**
     * Creates a new collection and pushs a new value on the given name.
     *
     * @param string $name
     *    The name to set
     * @param mixed $value
     *    The value to set
     *
     * @return static
     */
    public function withAdded(string $name, $value): static;

    /**
     * Deletes the given name
     *
     * @param  string $name
     *    The name to delete
     *
     * @return static
     */
    public function delete(string $name): static;

    /**
     * Creates a new collection and deletes the given name
     *
     * @param  string $name
     *    The name to delete
     *
     * @return static
     */
    public function without(string $name): static;

    /**
     * Returns the collection in array representation
     */
    public function toArray(): array;
}
