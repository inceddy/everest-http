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

/**
 * Uploaded file represenation.
 * @author Philipp Steingrebe <philipp@steingrebe.de>
 */

class UploadedFile {

  public function __construct(
    string $tmpName, 
    int $size = null, 
    int $error = UPLOAD_ERR_OK, 
    string $name = null, 
    string $type = null)
  {
    $this->tmpName = $tmpName;
    $this->size = $size;
    $this->error = $error;
    $this->name = $name;
    $this->type = $type;
  }


  /**
   * Retrieve a stream representing the uploaded file.
   *
   * This method MUST return a StreamInterface instance, representing the
   * uploaded file. The purpose of this method is to allow utilizing native PHP
   * stream functionality to manipulate the file upload, such as
   * stream_copy_to_stream() (though the result will need to be decorated in a
   * native PHP stream wrapper to work with such functions).
   *
   * If the moveTo() method has been called previously, this method MUST raise
   * an exception.
   *
   * @return StreamInterface Stream representation of the uploaded file.
   * @throws \RuntimeException in cases when no stream is available.
   * @throws \RuntimeException in cases when no stream can be created.
   */

  public function getStream()
  {
    return Stream::from($this->tmpName);
  }


  /**
   * Move the uploaded file to a new location.
   *
   * Use this method as an alternative to move_uploaded_file(). This method is
   * guaranteed to work in both SAPI and non-SAPI environments.
   * Implementations must determine which environment they are in, and use the
   * appropriate method (move_uploaded_file(), rename(), or a stream
   * operation) to perform the operation.
   *
   * $targetPath may be an absolute path, or a relative path. If it is a
   * relative path, resolution should be the same as used by PHP's rename()
   * function.
   *
   * The original file or stream MUST be removed on completion.
   *
   * If this method is called more than once, any subsequent calls MUST raise
   * an exception.
   *
   * When used in an SAPI environment where $_FILES is populated, when writing
   * files via moveTo(), is_uploaded_file() and move_uploaded_file() SHOULD be
   * used to ensure permissions and upload status are verified correctly.
   *
   * If you wish to move to a stream, use getStream(), as SAPI operations
   * cannot guarantee writing to stream destinations.
   *
   * @see http://php.net/is_uploaded_file
   * @see http://php.net/move_uploaded_file
   * @param string $targetPath Path to which to move the uploaded file.
   * @throws \InvalidArgumentException if the $targetPath specified is invalid.
   * @throws \RuntimeException on any error during the move operation.
   * @throws \RuntimeException on the second or subsequent call to the method.
   */

  public function moveTo(string $targetPath)
  {
    static $moved = false;

    if ($moved) {
      throw new \RuntimeException('Subsequent calls of moveTo are not allowed.');
    }

    if (!is_writable($targetPath)) {
      throw new \InvalidArgumentException('The target path specified is invalid.');
    }

    if ($this->error !== UPLOAD_ERR_OK || !$moved = move_uploaded_file($this->tmpName, $targetPath)) {
      throw new \RuntimeException('An error occured during file upload.', $this->error);
    }
  }


  /**
   * Retrieve the file size.
   *
   * Implementations SHOULD return the value stored in the "size" key of
   * the file in the $_FILES array if available, as PHP calculates this based
   * on the actual size transmitted.
   *
   * @return int|null The file size in bytes or null if unknown.
   */

  public function getSize() :? int
  {
    return $this->size;
  }


  /**
   * Retrieve the error associated with the uploaded file.
   *
   * The return value MUST be one of PHP's UPLOAD_ERR_XXX constants.
   *
   * If the file was uploaded successfully, this method MUST return
   * UPLOAD_ERR_OK.
   *
   * Implementations SHOULD return the value stored in the "error" key of
   * the file in the $_FILES array.
   *
   * @see http://php.net/manual/en/features.file-upload.errors.php
   * @return int One of PHP's UPLOAD_ERR_XXX constants.
   */
  
  public function getError() : int
  {
    return $this->error;
  }

  /**
   * Retrieve the filename sent by the client.
   *
   * Do not trust the value returned by this method. A client could send
   * a malicious filename with the intention to corrupt or hack your
   * application.
   *
   * Implementations SHOULD return the value stored in the "name" key of
   * the file in the $_FILES array.
   *
   * @return string|null The filename sent by the client or null if none
   *     was provided.
   */
    
  public function getClientFilename() :? string
  {
    return $this->name;
  }


  /**
   * Retrieve the media type sent by the client.
   *
   * Do not trust the value returned by this method. A client could send
   * a malicious media type with the intention to corrupt or hack your
   * application.
   *
   * Implementations SHOULD return the value stored in the "type" key of
   * the file in the $_FILES array.
   *
   * @return string|null The media type sent by the client or null if none
   *     was provided.
   */
 
  public function getClientMediaType() :? string
  {
    return $this->type;
  }
}
