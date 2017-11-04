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
use Countable;
use IteratorAggregate;
use ArrayIterator;

class ParameterCollection implements
	ParameterCollectionInterface,
	Countable, 
	IteratorAggregate
{
	/**
	 * The parameter cache as key-value-storage.
	 * @var array
	 */
	
	private $parameters;


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
	 * {@inheritDoc}
	 */
	
	public function has(string $key) : bool
	{
		return array_key_exists($key, $this->parameters);
	}


	/**
	 * {@inheritDoc}
	 */
	
	public function get(string $key) 
	{
		return $this->has($key) ? $this->parameters[$key] : null;
	}


	/**
	 * {@inheritDoc}
	 */

	public function set(string $key, $value, array $options = [])
	{
		$this->parameters[$key] = $value;
		return $this;
	}


	/**
	 * {@inheritDoc}
	 */

	public function with(string $key, $value, array $options = [])
	{
		if ($value === $this->get($key)) {
			return $this;
		}

		$new = clone $this;
		return $new->set($key, $value);
	}


	/**
	 * {@inheritDoc}
	 */

	public function push(string $key, $value)
	{
		// Not yet set
		if (!$this->has($key)) {
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


	/**
	 * {@inheritDoc}
	 */
	
	public function withAdded(string $key, $value)
	{
		$new = clone $this;
		return $new->push($key, $value);
	}


	/**
	 * {@inheritDoc}
	 */

	public function delete(string $key)
	{
		unset($this->parameters[$key]);
		return $this;
	}

	
	/**
	 * {@inheritDoc}
	 */
	
	public function without(string $key)
	{
		if (!$this->has($key)) {
			return $this;
		}

		$new = clone $this;
		return $new->delete($key);
	}


	
	/**
	 * {@inheritDoc}
	 */
	
	public function toArray() : array
	{
		return $this->parameters;
	}


	/**
	 * Returns string representation of this collection for debug proposes
	 * @return string
	 */
	
	public function __toString()
	{
		$string = '';

		foreach($this->parameters as $key => $parameter) {
			$string .= sprintf("%s = %s\r\n", $key, $parameter);
		}

		return $string;
	}


	/**
	 * Gets the parameter count of this collection to satisfy the Countable interface.
	 * @return int
	 */
	
	public function count()
	{
		return count($this->parameters);
	}


	/**
	 * Creates a new ArrayIterator to satisfy the IteratorAggregate interface.
	 * @return ArrayIterator
	 */
	
	public function getIterator()
	{
		return new ArrayIterator($this->parameters);
	}
}