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

namespace Everest\Http\Responses;

use Everest\Http\Cookie;
use Everest\Http\MessageInterface;

/**
 * Representation of an outgoing, server-side response.
 *
 * Per the HTTP specification, this interface includes properties for
 * each of the following:
 *
 * - Protocol version
 * - Status code and reason phrase
 * - Headers
 * - Message body
 *
 * Responses are considered immutable; all methods that might change state MUST
 * be implemented such that they retain the internal state of the current
 * message and return an instance that contains the changed state.
 */

interface ResponseInterface extends MessageInterface
{
    /**
     * HTTP status codes
     * Taken from symfony http-everest
     */
    public const HTTP_CONTINUE = 100;

    public const HTTP_SWITCHING_PROTOCOLS = 101;

    public const HTTP_PROCESSING = 102;            // RFC2518

    public const HTTP_OK = 200;

    public const HTTP_CREATED = 201;

    public const HTTP_ACCEPTED = 202;

    public const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;

    public const HTTP_NO_CONTENT = 204;

    public const HTTP_RESET_CONTENT = 205;

    public const HTTP_PARTIAL_CONTENT = 206;

    public const HTTP_MULTI_STATUS = 207;          // RFC4918

    public const HTTP_ALREADY_REPORTED = 208;      // RFC5842

    public const HTTP_IM_USED = 226;               // RFC3229

    public const HTTP_MULTIPLE_CHOICES = 300;

    public const HTTP_MOVED_PERMANENTLY = 301;

    public const HTTP_FOUND = 302;

    public const HTTP_SEE_OTHER = 303;

    public const HTTP_NOT_MODIFIED = 304;

    public const HTTP_USE_PROXY = 305;

    public const HTTP_RESERVED = 306;

    public const HTTP_TEMPORARY_REDIRECT = 307;

    public const HTTP_PERMANENTLY_REDIRECT = 308;  // RFC7238

    public const HTTP_BAD_REQUEST = 400;

    public const HTTP_UNAUTHORIZED = 401;

    public const HTTP_PAYMENT_REQUIRED = 402;

    public const HTTP_FORBIDDEN = 403;

    public const HTTP_NOT_FOUND = 404;

    public const HTTP_METHOD_NOT_ALLOWED = 405;

    public const HTTP_NOT_ACCEPTABLE = 406;

    public const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;

    public const HTTP_REQUEST_TIMEOUT = 408;

    public const HTTP_CONFLICT = 409;

    public const HTTP_GONE = 410;

    public const HTTP_LENGTH_REQUIRED = 411;

    public const HTTP_PRECONDITION_FAILED = 412;

    public const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;

    public const HTTP_REQUEST_URI_TOO_LONG = 414;

    public const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;

    public const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;

    public const HTTP_EXPECTATION_FAILED = 417;

    public const HTTP_I_AM_A_TEAPOT = 418;                                               // RFC2324

    public const HTTP_UNPROCESSABLE_ENTITY = 422;                                        // RFC4918

    public const HTTP_LOCKED = 423;                                                      // RFC4918

    public const HTTP_FAILED_DEPENDENCY = 424;                                           // RFC4918

    public const HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL = 425;   // RFC2817

    public const HTTP_UPGRADE_REQUIRED = 426;                                            // RFC2817

    public const HTTP_PRECONDITION_REQUIRED = 428;                                       // RFC6585

    public const HTTP_TOO_MANY_REQUESTS = 429;                                           // RFC6585

    public const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;                             // RFC6585

    public const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;

    public const HTTP_INTERNAL_SERVER_ERROR = 500;

    public const HTTP_NOT_IMPLEMENTED = 501;

    public const HTTP_BAD_GATEWAY = 502;

    public const HTTP_SERVICE_UNAVAILABLE = 503;

    public const HTTP_GATEWAY_TIMEOUT = 504;

    public const HTTP_VERSION_NOT_SUPPORTED = 505;

    public const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;                        // RFC2295

    public const HTTP_INSUFFICIENT_STORAGE = 507;                                        // RFC4918

    public const HTTP_LOOP_DETECTED = 508;                                               // RFC5842

    public const HTTP_NOT_EXTENDED = 510;                                                // RFC2774

    public const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;                             // RFC6585

    /**
     * HTTP status codes to status text map.
     * @var array
     */
    public const STATUS_CODE_MAP = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',            // RFC2518
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',          // RFC4918
        208 => 'Already Reported',      // RFC5842
        226 => 'IM Used',               // RFC3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',    // RFC7238
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',                                               // RFC2324
        422 => 'Unprocessable Entity',                                        // RFC4918
        423 => 'Locked',                                                      // RFC4918
        424 => 'Failed Dependency',                                           // RFC4918
        425 => 'Reserved for WebDAV advanced collections expired proposal',   // RFC2817
        426 => 'Upgrade Required',                                            // RFC2817
        428 => 'Precondition Required',                                       // RFC6585
        429 => 'Too Many Requests',                                           // RFC6585
        431 => 'Request Header Fields Too Large',                             // RFC6585
        451 => 'Unavailable For Legal Reasons',                               // RFC7725
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates (Experimental)',                      // RFC2295
        507 => 'Insufficient Storage',                                        // RFC4918
        508 => 'Loop Detected',                                               // RFC5842
        510 => 'Not Extended',                                                // RFC2774
        511 => 'Network Authentication Required',                             // RFC6585
    ];

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int Status code.
     */
    public function getStatusCode(): int;

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     *
     * If no reason phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * status code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated status and reason phrase.
     *
     * @see http://tools.ietf.org/html/rfc7231#section-6
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param int $code The 3-digit integer result code to set.
     * @param string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return static
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus(int $code, string $reasonPhrase = '');

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * Because a reason phrase is not a required element in a response
     * status line, the reason phrase value MAY be empty. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * status code.
     *
     * @see http://tools.ietf.org/html/rfc7231#section-6
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string Reason phrase; must return an empty string if none present.
     */
    public function getReasonPhrase(): string;

    public function withCookies(array $cookies);

    public function withCookie(Cookie $cookie);

    public function send();
}
