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

namespace Everest\Http\Requests;

use ArrayAccess;
use Closure;
use Everest\Http\Collections\ParameterCollection;
use Everest\Http\Stream;
use Everest\Http\UploadedFile;
use Everest\Http\Uri;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use SimpleXMLElement;

class ServerRequest extends Request implements RequestInterface
{
    /**
     * The ParameterCollection of the super global $_GET
     * @var Everest\Http\ParameterCollectionInterface
     */
    public $query;

    /**
     * The ParameterCollection of the super global $_POST
     * @var Everest\Http\ParameterCollectionInterface
     */
    public $parsedBody = false;

    /**
     * The ParameterCollection of the super global $_FILES
     * @var Everest\Http\ParameterCollectionInterface
     */
    public $files;

    /**
     * The ParameterCollection of the super global $_SERVER
     * @var Everest\Http\ParameterCollectionInterface
     */
    public $server;

    /**
     * The CookieCollection of the super global $_COOKIE
     * @var Everest\Http\CookieCollectionInterface
     */
    public $cookie;

    /**
     * Array of parsers for different body content types
     * @var array<Closure>
     */
    protected static $bodyParsers = [];

    private $attributes;

    /**
     * Creates a new request with the default super global parameters of PHP.
     *
     * @return Everest\Http\Request
     */
    public function __construct(
        $method,
        $uri,
        array $headers = [],
        $body = null,
        string $protocolVersion = self::HTTP_VERSION_1_1,
        array $server = null
    ) {
        parent::__construct($method, $uri, $headers, $body, $protocolVersion);

        $this->server = new ParameterCollection($server ?: $_SERVER);
        $this->attributes = [];
    }

    public static function addBodyParser(string $mediaType, callable $parser): void
    {
        self::$bodyParsers[strtolower($mediaType)] = $parser;
    }

    public static function getUriFromGlobals(): Uri
    {
        $uri = Uri::from(sprintf(
            '%s://%s:%s',
            self::getHttpScheme(),
            self::getHost(),
            self::getPort()
        ));

        if (isset($_SERVER['REQUEST_URI'])) {
            $requestUriParts = explode('?', (string) $_SERVER['REQUEST_URI']);
            $uri = $uri->withPath($requestUriParts[0]);
            if (isset($requestUriParts[1])) {
                return $uri = $uri->withQuery($requestUriParts[1]);
            }
        }

        if (isset($_SERVER['QUERY_STRING'])) {
            $uri = $uri->withQuery($_SERVER['QUERY_STRING']);
        }

        return $uri;
    }

    /**
     * Tries to detect if the server is running behind an SSL.
     */
    public static function isBehindSsl(): bool
    {
        // Check for proxy first
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            return self::protocolWithActiveSsl($_SERVER['HTTP_X_FORWARDED_PROTO']);
        }

        if (isset($_SERVER['HTTPS'])) {
            return self::protocolWithActiveSsl($_SERVER['HTTPS']);
        }

        return $_SERVER['SERVER_PORT'] === 443;
    }

    /**
     * Return the currently active URI scheme.
     */
    public static function getHttpScheme(): string
    {
        return self::isBehindSsl() ? 'https' : 'http';
    }

    /**
     * Tries to detect the host name of the server.
     *
     * Some elements adapted from
     * @see https://github.com/symfony/HttpEverest/blob/master/Request.php
     */
    public static function getHost(): string
    {
        // Check for proxy first
        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $forwaredHostNames = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_HOST']);
            $host = end($forwaredHostNames);
        } else {
            $host = 
              $_SERVER['HTTP_HOST'] ??
              $_SERVER['HTTP_SERVER_NAME'] ??
              $_SERVER['HTTP_SERVER_ADDR'];
        }

        // trim and remove port number from host
        // host is lowercase as per RFC 952/2181
        return strtolower(preg_replace('/:\d+$/', '', trim((string) $host)));
    }

    /**
     *  Returns the port of this request as string.
     */
    public static function getPort(): int|null
    {
        // Check for proxy first
        if (isset($_SERVER['HTTP_X_FORWARDED_PORT'])) {
            return (int) $_SERVER['HTTP_X_FORWARDED_PORT'];
        }
        if ('https' === ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) {
            return 443;
        }

        return isset($_SERVER['SERVER_PORT']) ?
        (int) $_SERVER['SERVER_PORT'] : null;
    }

    /**
     * Return an UploadedFile instance array.
     *
     * @param array $files A array which respect $_FILES structure
     * @throws InvalidArgumentException for unrecognized values
     * @return array
     */
    public static function normalizeFiles(array $files)
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFile) {
                $normalized[$key] = $value;
            } elseif (is_array($value)) {
                $normalized[$key] = isset($value['tmp_name']) ?
          self::createUploadedFileFromSpec($value) :
          self::normalizeFiles($value);
            } else {
                throw new \InvalidArgumentException('Invalid value in files specification');
            }
        }
        return $normalized;
    }

    /**
     * Creates a new request with the default super global parameters of PHP.
     *
     * @return Everest\Http\Request
     */
    public static function fromGlobals()
    {
        static $instance;

        if (! isset($instance)) {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            $uri = self::getUriFromGlobals();
            $body = Stream::from(fopen('php://input', 'r+'));
            $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', (string) $_SERVER['SERVER_PROTOCOL']) : '1.1';

            $instance = (new self($method, $uri, $headers, $body, $protocol, $_SERVER))
                ->withCookieParams($_COOKIE)
                ->withQueryParams($_GET)
                ->withUploadedFiles($_FILES);

            $contentType = $instance->getContentType();
            if ($method === 'POST' && (
                stripos((string) $contentType, 'application/x-www-form-urlencoded') !== false ||
                stripos((string) $contentType, 'multipart/form-data') !== false
            )) {
                $instance = $instance->withParsedBody($_POST);
            }
        }

        return $instance;
    }

    public function getContentType(): ? string
    {
        return $this->getHeader('Content-Type')[0] ?? null;
    }

    public function getMediaType(): ? string
    {
        if (! $contentType = $this->getContentType()) {
            return null;
        }

        return strtolower(preg_split('/\s*[;,]\s*/', $contentType)[0]);
    }

    public function getMediaTypeParams(): array
    {
        $result = [];

        if (! $contentType = $this->getContentType()) {
            return $result;
        }

        foreach (preg_split('/\s*[;,]\s*/', $contentType) as $part) {
            [$name, $value] = explode('=', (string) $part);
            $result[strtolower($name)] = $value;
        }

        return $result;
    }

    public function getContentCharset(): ? string
    {
        foreach ($this->getMediaTypeParams() as $name => $value) {
            if ($name === 'charset') {
                return $value;
            }
        }

        return null;
    }

    /**
     * Shortcut to fetch a GET-parameter with optional default value
     *
     * @param string $key
     *   the key to look for or NULL to acces the collection
     * @param mixed  $default
     *   the value thar will be returned if the key is not set
     *
     * @return mixed
     *   the found or the default value
     */
    public function getQueryParam(string $key, mixed $default = null)
    {
        return $this->query && $this->query->has($key) ? $this->query->get($key) : $default;
    }

    public function getQueryParams(): array
    {
        return $this->query ?
           $this->query->toArray() :
           [];
    }

    public function withQueryParams(array $queryParams): static
    {
        $new = clone $this;
        $new->query = new ParameterCollection($queryParams);

        return $new;
    }

    /**
     * Shortcut to fetch a POST-parameter with optional default value
     *
     * @param string $key
     *   the key to look for
     * @param mixed $default
     *   the value thar will be returned if the key is not set
     *
     * @return mixed
     *   the found or the default value
     */
    public function getBodyParam(string $key, mixed $default = null)
    {
        if (is_array($this->parsedBody) || $this->parsedBody instanceof ArrayAccess) {
            return $this->parsedBody[$key] ?? $default;
        }

        return $default;
    }

    public function getParsedBody(): array|object|null
    {
        $mediaType = null;
        $parts = [];
        if ($this->parsedBody !== false) {
            return $this->parsedBody;
        }

        if (! $mediaType = $this->getMediaType()) {
            return $this->parsedBody = null;
        }

        $parts = explode('+', $mediaType);
        if (count($parts) >= 2) {
            $mediaType = 'application/' . $parts[count($parts) - 1];
        }

        if (isset(self::$bodyParsers[$mediaType]) === true) {
            $body = $this->body->getContents();
            $parsed = call_user_func(self::$bodyParsers[$mediaType], $body);

            if ($parsed !== null && ! is_object($parsed) && ! is_array($parsed)) {
                throw new RuntimeException(
                    'Request body parser return value must be an array, an object, or null'
                );
            }

            return $this->parsedBody = $parsed;
        }

        return null;
    }

    public function withParsedBody($parsedBody): static
    {
        if ($parsedBody !== null && ! is_object($parsedBody) && ! is_array($parsedBody)) {
            throw new \InvalidArgumentException('Parsed body must be an array, an object, or null');
        }

        $new = clone $this;
        $new->parsedBody = $parsedBody;

        return $new;
    }

    /**
     * Shortcut to fetch a FILES-parameter with optional default value
     *
     * @param  string $key
     *   the key to look for
     * @param  mixed $default
     *   the value thar will be returned if the key is not set
     *
     * @return mixed
     *   the found or the default value
     */
    public function getUploadedFile(string $key, mixed $default = null)
    {
        return $this->uploadedFiles->has($key) ? $this->uploadedFiles->get($key) : $default;
    }

    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles->toArray();
    }

    public function withUploadedFiles(array $uploadedFiles): static
    {
        $new = clone $this;

        $new->uploadedFiles = new ParameterCollection(
            self::normalizeFiles($uploadedFiles)
        );

        return $new;
    }

    public function getCookieParams(): array
    {
        return $this->cookie ? $this->cookie->getArray() : [];
    }

    public function getCookieParam(string $name, $default = null)
    {
        if (! $this->cookie) {
            return $default;
        }

        return $this->cookie->get($name) ?: $default;
    }

    public function withCookieParams(array $cookieParams): static
    {
        $new = clone $this;
        $new->cookie = new ParameterCollection($cookieParams);

        return $new;
    }

    public function getSeverParams(): array
    {
        return $this->server->toArray();
    }

    public function getServerParam(string $name, $default = null)
    {
        return $this->server->get($name) ?: $default;
    }

    /**
     * Shortcut to fetch the first set parameter of GET, POST or FILE with optional default value
     *
     * @param string $key
     *   the key to look for
     * @param mixed $default
     *   the value thar will be returned if the key is not set
     *
     * @return mixed
     *   the found or the default value
     */
    public function getRequestParam(string $key, mixed $default = null)
    {
        return $this->getQueryParam($key) ?:
           $this->getBodyParam($key) ?:
           $this->getUploadedFile($key) ?: $default;
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return mixed[] Attributes derived from the request.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute(string $name, mixed $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return static
     */
    public function withAttribute(string $name, mixed $value)
    {
        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $name The attribute name.
     * @return static
     */
    public function withoutAttribute(string $name)
    {
        if (! array_key_exists($name, $this->attributes)) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }

    /**
     * Detects an active SSL protocol value.
     *
     * @param string $protocol
     */
    private static function protocolWithActiveSsl($protocol): bool
    {
        return in_array(strtolower((string) $protocol), ['on', '1', 'https', 'ssl'], true);
    }

    /**
     * Create and return an UploadedFile instance from a $_FILES specification.
     *
     * If the specification represents an array of values, this method will
     * delegate to normalizeNestedFileSpec() and return that return value.
     *
     * @param array $value $_FILES struct
     */
    private static function createUploadedFileFromSpec(array $value): array|UploadedFileInterface
    {
        if (is_array($value['tmp_name'])) {
            return self::normalizeNestedFileSpec($value);
        }
        return new UploadedFile(
            $value['tmp_name'],
            (int) $value['size'],
            (int) $value['error'],
            $value['name'],
            $value['type']
        );
    }

    /**
     *  Normalize an array of file specifications.
     *
     *  Loops through all nested files and returns a normalized array of
     *  UploadedFileInterface instances.
     *
     * @return (UploadedFileInterface|array)[]
     *
     * @psalm-return array<UploadedFileInterface|array>
     */
    private static function normalizeNestedFileSpec(array $files = []): array
    {
        $normalizedFiles = [];

        foreach (array_keys($files['tmp_name']) as $key) {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size' => $files['size'][$key],
                'error' => $files['error'][$key],
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
            ];
            $normalizedFiles[$key] = self::createUploadedFileFromSpec($spec);
        }

        return $normalizedFiles;
    }
}

// Define default body parsers

$xmlParser = function ($body): SimpleXMLElement|null {
    $backup_errors = libxml_use_internal_errors(true);

    $xml = simplexml_load_string((string) $body);
    libxml_clear_errors();
    libxml_use_internal_errors($backup_errors);

    if ($xml === false) {
        return null;
    }
    return $xml;
};

ServerRequest::addBodyParser('application/xml', $xmlParser);

ServerRequest::addBodyParser('text/xml', $xmlParser);

ServerRequest::addBodyParser('application/json', function ($body) {
    $json = json_decode((string) $body, true, 512, JSON_THROW_ON_ERROR);
    if (! is_array($json)) {
        return null;
    }
    return $json;
});

ServerRequest::addBodyParser('application/x-www-form-urlencoded', function ($body) {
    $data = [];
    parse_str((string) $body, $data);
    return $data;
});
