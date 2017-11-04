<?php

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
	/**
	 * Returns whether or not the given key
	 * is contained in this collection
	 *
	 * @param  string  $key
	 *    The key to look for
	 *
	 * @return boolean
	 */
	
	public function has(string $key) : bool;


	/**
	 * Returns the value behind the given key
	 * or null if the key is not set
	 *
	 * @param  string $key
	 *    The key of the value to retrieve
	 *
	 * @return mixed|null
	 */
	
	public function get(string $key);


	/**
	 * Sets a value on the given key
	 *
	 * @param string $key
	 *    The key to set
	 * @param mixed $value
	 *    The value to set
	 * @param array $options
	 *    Optional options to be be uses for setting the value
	 *
	 * @return self
	 */
	
	public function set(string $key, $value, array $options = []);


	/**
	 * Creates a new collection and sets the given key
	 *
	 * @param string $key
	 *    The key to set
	 * @param mixed $value
	 *    The value to set
	 * @param array $options
	 *    Optional options to be be uses for setting the value
	 *
	 * @return static
	 */
	
	public function with(string $key, $value, array $options = []);


	/**
	 * Pushs a new value on the given key.
	 *
	 * If the key is not yet set it will be set with an
	 *
	 * @param string $key
	 *    The key to set
	 * @param mixed $value
	 *    The value to set
	 *
	 * @return self
	 */
	public function push(string $key, $value);


	/**
	 * Creates a new collection and pushs a new value on the given key.
	 *
	 * @param string $key
	 *    The key to set
	 * @param mixed $value
	 *    The value to set
	 * @param array $options
	 *    Optional options to be be uses for setting the value
	 *
	 * @return static
	 */
	
	public function withAdded(string $key, $value);


	/**
	 * Deletes the given key
	 *
	 * @param  string $key
	 *    The key to delete
	 *
	 * @return self
	 */
	
	public function delete(string $key);


	/**
	 * Creates a new collection and deletes the given key
	 *
	 * @param  string $key
	 *    The key to delete
	 *
	 * @return static
	 */

	public function without(string $key);


	/**
	 * Returns the collection in array representation
	 * @return array
	 */
	
	public function toArray() : array;

	public function __toString();
}