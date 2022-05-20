<?php

use Everest\Http\Responses\JsonResponse;


class JsonResponseTest extends \PHPUnit\Framework\TestCase {

	public function testJson()
	{
		$response = new JsonResponse(['a' => 'b']);
		$this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
		$this->assertStringContainsString('{"a":"b"}', (string)$response);
	}
	
	public function testInvalidJson()
	{
		$this->expectException(\InvalidArgumentException::class);

		$response = new JsonResponse(fopen('php://temp', 'r+'));
	}
}
