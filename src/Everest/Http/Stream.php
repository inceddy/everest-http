<?php

/*
 * This file is part of Everest.
 *
 * (c) 2017 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\Http;
use Psr\Http\Message\StreamInterface;

class Stream
{
  private $resrouce;
  private $size;
  private $seekable;
  private $readable;
  private $writable;
  private $uri;
  private $customMetadata;

  private const READ_MODES =  '/^(?:r\+?|[awxc]\+)[tb]?$/';
  private const WRITE_MODES = '/^(?:r\+|[awxc]\+?)[tb]?$/';

  public static function from($content, array $options = [])
  {
    switch (true) {
      // Trivial
      case $content instanceof Stream:
        return $content;

      // File
      case is_string($content) && @file_exists($content) && @is_readable($content):
        $resource = fopen($content, 'r+');
        return new self($resource, $options);

      // Scalar 
      case is_scalar($content):
      case is_null($content):
        $resource = fopen('php://temp', 'r+');
        if ($content !== '') {
            fwrite($resource, $content);
            fseek($resource, 0);
        }
        return new self($resource, $options);

      // Resource
      case is_resource($content):
        return new self($content, $options);
    }
    
    throw new \InvalidArgumentException(sprintf(
      'Can\'t create stream from %s.',
      is_object($content) ? get_class($content) : gettype($content)
    ));
  }

  public function __construct($stream, array $options = [])
  {
    if (!is_resource($stream)) {
      throw new \InvalidArgumentException('Stream must be a resource');
    }

    if (isset($options['size'])) {
      $this->size = $options['size'];
    }
      
    $this->customMetadata = $options['metadata'] ?? [];
    $this->resource = $stream;

    $meta = stream_get_meta_data($this->resource);
    
    $this->seekable = $meta['seekable'];
    $this->readable = 1 === preg_match(self::READ_MODES, $meta['mode']);
    $this->writable = 1 === preg_match(self::WRITE_MODES, $meta['mode']);

    $this->uri = $this->getMetadata('uri');
  }
  /**
   * Closes the stream when the destructed
   */
  public function __destruct()
  {
      $this->close();
  }
  public function __toString()
  {
      try {
          $this->seek(0);
          return (string) stream_get_contents($this->resource);
      } catch (\Exception $e) {
          return '';
      }
  }
  public function getContents()
  {
      if (!isset($this->resource)) {
          throw new \RuntimeException('Stream is detached');
      }
      $contents = stream_get_contents($this->resource);
      if ($contents === false) {
          throw new \RuntimeException('Unable to read stream contents');
      }
      return $contents;
  }

  public function close()
  {
    if (isset($this->resource)) {
      fclose($this->resource);
    }
    $this->detach();
  }

  public function detach()
  {
    if (!isset($this->resrouce)) {
        return null;
    }

    $resource = $this->resrouce;
    unset($this->resrouce);

    $this->size = 
    $this->uri = null;

    $this->readable = 
    $this->writable = 
    $this->seekable = false;

    return $resource;
  }

  public function getSize() :? int
  {
    if ($this->size !== null) {
        return $this->size;
    }

    if (!isset($this->resource)) {
        return null;
    }

    if ($this->uri) {
        clearstatcache(true, $this->uri);
    }

    $stats = fstat($this->resource);

    if (isset($stats['size'])) {
        $this->size = $stats['size'];
        return $this->size;
    }
    return null;
  }

  public function isReadable() : bool
  {
    return $this->readable;
  }

  public function isWritable() : bool
  {
    return $this->writable;
  }

  public function isSeekable() : bool
  {
    return $this->seekable;
  }

  public function eof() : bool
  {
    if (!isset($this->resource)) {
      throw new \RuntimeException('Stream is detached');
    }
    return feof($this->resource);
  }

  public function tell()
  {
    if (!isset($this->resource)) {
      throw new \RuntimeException('Stream is detached');
    }

    $result = ftell($this->resource);
    
    if ($result === false) {
      throw new \RuntimeException('Unable to determine stream position');
    }

    return $result;
  }

  public function rewind()
  {
    $this->seek(0);
  }

  public function seek(int $offset, $whence = SEEK_SET)
  {
    if (!isset($this->resource)) {
      throw new \RuntimeException('Stream is detached');
    }

    if (!$this->seekable) {
      throw new \RuntimeException('Stream is not seekable');
    }

    if (-1 === fseek($this->resource, $offset, $whence)) {
      throw new \RuntimeException(sprintf(
        'Unable to seek to stream position %s with whence %s',
        $offset,
        var_export($whence, true)
      ));
    }
  }

  public function read(int $length)
  {
    if (!isset($this->resource)) {
      throw new \RuntimeException('Stream is detached');
    }

    if (!$this->readable) {
      throw new \RuntimeException('Cannot read from non-readable stream');
    }

    if ($length < 0) {
      throw new \RuntimeException('Length parameter cannot be negative');
    }

    if (0 === $length) {
      return '';
    }

    $string = fread($this->resource, $length);

    if (false === $string) {
      throw new \RuntimeException('Unable to read from stream');
    }

    return $string;
  }

  public function write($string)
  {
    if (!isset($this->resource)) {
      throw new \RuntimeException('Stream is detached');
    }

    if (!$this->writable) {
      throw new \RuntimeException('Cannot write to a non-writable stream');
    }

    // We can't know the size after writing anything
    $this->size = null;
    $result = fwrite($this->resource, $string);

    if ($result === false) {
      throw new \RuntimeException('Unable to write to stream');
    }

    return $result;
  }

  public function getMetadata(string $key = null)
  {
    if (!isset($this->resource)) {
      return $key ? null : [];
    } elseif (!$key) {
      return $this->customMetadata + stream_get_meta_data($this->resource);
    } elseif (isset($this->customMetadata[$key])) {
      return $this->customMetadata[$key];
    }

    $meta = stream_get_meta_data($this->resource);
    
    return $meta[$key] ?? null;
  }
}
