<?php

declare(strict_types=1);

use Everest\Http\Requests\ServerRequest;
use Everest\Http\Stream;
use Everest\Http\Tests\WebTestCase;
use Everest\Http\UploadedFile;
use Everest\Http\Uri;

/**
 * @author  Philipp Steingrebe <philipp@steingrebe.de>
 */
class ServerRequestTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->setupWebRequest();
    }

    public function getServerRequest(int $method, Uri $uri)
    {
        return new ServerRequest($method, $uri);
    }

    public function testSeverRequestFromGlobals()
    {
        $request = ServerRequest::fromGlobals();
        $this->assertInstanceOf(ServerRequest::class, $request);
    }

    public function testInitialQueryParams()
    {
        $request = $this->getServerRequest(
            ServerRequest::HTTP_GET,
            Uri::from('localhost')
        );

        // Collection MUST be initially unset
        $this->assertNull($request->query);

        // Initially MUST return empty array
        $queryParams = $request->getQueryParams();
        $this->assertTrue(is_array($queryParams) && empty($queryParams));

        // Unset names MUST return null if no default is set
        $this->assertNull($request->getQueryParam('unsetkey'));

        // Unset names MUST return default if set
        $this->assertTrue($request->getQueryParam('unsetkey', true));
    }

    public function testQueryParams()
    {
        $initialRequest = $this->getServerRequest(
            ServerRequest::HTTP_GET,
            Uri::from('localhost')
        );

        $queryParams = [
            'name' => 'value',
        ];

        $request = $initialRequest->withQueryParams($queryParams);
        $this->assertNotEquals($request, $initialRequest);

        $this->assertEquals($queryParams, $request->getQueryParams());
        $this->assertEquals('value', $request->getQueryParam('name'));
    }

    public function testInitialBodyParams()
    {
        $request = $this->getServerRequest(
            ServerRequest::HTTP_GET,
            Uri::from('localhost')
        );

        // Collection MUST be initially false
        $this->assertFalse($request->parsedBody);

        // Initially MUST return null if theres nothing to parse
        $bodyParams = $request->getParsedBody();
        $this->assertNull($bodyParams);

        // Unset names MUST return null if no default is set
        $this->assertNull($request->getBodyParam('unsetkey'));

        // Unset names MUST return default if set
        $this->assertTrue($request->getBodyParam('unsetkey', true));
    }

    public function testBodyParams()
    {
        $initialRequest = $this->getServerRequest(
            ServerRequest::HTTP_GET,
            Uri::from('/some/path')
        );

        $bodyParams = [
            'name' => 'value',
        ];

        $request = $initialRequest->withParsedBody($bodyParams);
        $this->assertNotEquals($request, $initialRequest);

        $this->assertEquals($bodyParams, $request->getParsedBody());
        $this->assertEquals('value', $request->getBodyParam('name'));
    }

    public function testUploadedFiles()
    {
        $initialRequest = $this->getServerRequest(
            ServerRequest::HTTP_GET,
            Uri::from('/some/path')
        );

        $request = $initialRequest->withUploadedFiles([
            'single_file' => new UploadedFile('temp name', 1000, UPLOAD_ERR_OK, 'name', 'type'),
            'multi_file' => [
                'name' => [
                    '35mm-filmstreifen.jpg',
                    'Blockhaus_innen_Saal.jpg',
                    'lupe.gif',
                    'Satelliten-Foto (Google-Maps).bmp',
                ],
                'type' => [
                    'image/jpeg',
                    'image/jpeg',
                    'image/gif',
                    '',
                ],
                'tmp_name' => [
                    'M:\User\Tom\www\testserver.lan\tmp\php12F.tmp',
                    'M:\User\Tom\www\testserver.lan\tmp\php130.tmp',
                    'M:\User\Tom\www\testserver.lan\tmp\php131.tmp',
                    '',
                ],
                'error' => [
                    UPLOAD_ERR_OK,
                    UPLOAD_ERR_OK,
                    UPLOAD_ERR_OK,
                    UPLOAD_ERR_NO_FILE,
                ],
                'size' => [
                    12645,
                    112716,
                    1373,
                    0,
                ],
            ],
        ]);

        // Stack access
        $uploadedFiles = $request->getUploadedFiles();
        $this->assertTrue(is_array($uploadedFiles));
        $this->assertInstanceOf(UploadedFile::class, $uploadedFiles['single_file']);
        $this->assertContainsOnly(UploadedFile::class, $uploadedFiles['multi_file']);

        // Single access
        $this->assertInstanceOf(UploadedFile::class, $request->getUploadedFile('single_file'));
    }

    public function testRequestTarget()
    {
        $request = $this->getServerRequest(
            ServerRequest::HTTP_GET,
            Uri::from('https://steingrebe.de/some/target')
        );

        $target = $request->getRequestTarget();
        $this->assertEquals('/some/target', $target);
    }

    public function testMethod()
    {
        $request = $this->getServerRequest(
            ServerRequest::HTTP_GET,
            Uri::from('https://steingrebe.de/some/target')
        );

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals(ServerRequest::HTTP_GET, $request->getMethodFlag());

        $this->assertTrue($request->isMethod('GET'));
        $this->assertTrue($request->isMethod(ServerRequest::HTTP_GET));
        $this->assertTrue($request->isMethod(ServerRequest::HTTP_ALL));

        $this->assertFalse($request->isMethod('POST'));
        $this->assertFalse($request->isMethod(ServerRequest::HTTP_POST));
    }

    public function testJsonBodyParser()
    {
        $request = ServerRequest::fromGlobals()
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Stream::from(json_encode([
                'a' => true,
                'b' => [],
            ])));

        $this->assertSame([
            'a' => true,
            'b' => [],
        ], $request->getParsedBody());
    }

    public function testXmlBodyParser()
    {
        $request = ServerRequest::fromGlobals()
            ->withHeader('Content-Type', 'application/xml')
            ->withBody(Stream::from('<?xml version="1.0" encoding="UTF-8"?>
<test>
  <a>1</a>
  <b>2</b>
</test>'));

        $this->assertInstanceOf(\SimpleXMLElement::class, $request->getParsedBody());
    }

    public function testXml2BodyParser()
    {
        $request = ServerRequest::fromGlobals()
            ->withHeader('Content-Type', 'text/xml')
            ->withBody(Stream::from('<?xml version="1.0" encoding="UTF-8"?>
<test>
  <a>1</a>
  <b>2</b>
</test>'));

        $this->assertInstanceOf(\SimpleXMLElement::class, $request->getParsedBody());
    }

    public function testUrlEncodedBodyParser()
    {
        $request = ServerRequest::fromGlobals()
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody(Stream::from('first=value&arr[]=foo+bar&arr[]=baz'));

        $this->assertSame([
            'first' => 'value',
            'arr' => ['foo bar', 'baz'],
        ], $request->getParsedBody());
    }
}
