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

class HeaderCollection implements
	ParameterCollectionInterface,
	Countable, 
	IteratorAggregate
{
	/**
	 * The parameter cache as key-value-storage.
	 * @var array
	 */
	
	private $headers = [];


  private static function parseHeaderValue($value) : array
  {
    switch (true) {
    	case is_string($value):
    		$value = explode(',', $value);
      case is_array($value):
        return array_filter(array_map('trim', $value));
    }

    throw new \InvalidArgumentException(sprintf(
      'Header must be type of string or array but %s given.', 
      is_object($value) ? get_class($value) : gettype($value)
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
   * @return string
   *    The normalized header name
   */
  
  private static function normalizeHeaderName(string $name) : string
  {
    if (0 === preg_match('/^[^\x00-\x1F :]+$/', $name)) {
      throw new \InvalidArgumentException(sprintf(
        '%s is not a valid header name.', 
        $name
      ));
    }

    return strtolower($name);
  }


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
	 * {@inheritDoc}
	 */
	
	public function has(string $name) : bool
	{
		return isset($this->headers[self::normalizeHeaderName($name)]);
	}


	/**
	 * {@inheritDoc}
	 */
	
	public function get(string $name) 
	{
		return $this->headers[self::normalizeHeaderName($name)][1] ?? [];
	}


	/**
	 * {@inheritDoc}
	 */

	public function set(string $name, $value, array $options = [])
	{
		$this->headers[self::normalizeHeaderName($name)] = [$name, self::parseHeaderValue($value)];
	}


	/**
	 * {@inheritDoc}
	 */

	public function with(string $name, $value, array $options = [])
	{
		$parsed     = self::parseHeaderValue($value);

		if ($this->get($name) === $parsed) {
			return $this;
		}

		$new = clone $this;
		$new->set($name, $value);

		return $new;
	}


	/**
	 * {@inheritDoc}
	 */

	public function push(string $name, $value)
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


	/**
	 * {@inheritDoc}
	 */

	public function withAdded(string $name, $value)
	{
		$parsed = self::parseHeaderValue($value);

		if (empty($parsed)) {
			return $this;
		}

		$normalized = self::normalizeHeaderName($name);

		$new = clone $this;
		if (!isset($new->headers[$normalized])) {
			$new->headers[$normalized] = [$name, []];
		}

		$new->headers[$normalized][1] = array_merge(
			$new->headers[$normalized][1],
			$parsed
		);

		return $new;
	}


	/**
	 * {@inheritDoc}
	 */

	public function delete(string $name)
	{
		unset($this->headers[self::normalizeHeaderName($name)]);
	}


	/**
	 * {@inheritDoc}
	 */

	public function without(string $name)
	{
		if (!$this->has($name)) {
			return $this;
		}

		$new = clone $this;
		unset($new->headers[self::normalizeHeaderName($name)]);

		return $new;
	}


	/**
	 * Returns string representation of this collection for debug proposes
	 * @return string
	 */
	
	public function __toString()
	{
		$string = '';

		foreach($this->headers as $header) {
			[$name, $values] = $header;
			$string .= sprintf("%s: %s\r\n", $name, implode(', ', $values));
		}

		return $string;
	}


	/**
	 * {@inheritDoc}
	 */
	
	public function toArray() : array
	{
  	$headers = [];

		foreach ($this->headers as $header) {
			$headers[$header[0]] = $header[1];
		}

		return $headers;
	}

	/**
	 * Gets the parameter count of this collection to satisfy the Countable interface.
	 * @return int
	 */
	
	public function count() : int
	{
		return count($this->headers);
	}


	/**
	 * Creates a new ArrayIterator to satisfy the IteratorAggregate interface.
	 * @return ArrayIterator
	 */
	
	public function getIterator() : ArrayIterator
	{
		return new ArrayIterator($this->toArray());
	}
}