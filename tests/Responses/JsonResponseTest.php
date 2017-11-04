<?php

use Everest\Http\Responses\JsonResponse;


class JsonResponseTest extends \PHPUnit_Framework_TestCase {

	public function testJson()
	{
		$response = new JsonResponse(['a' => 'b']);
		$this->assertEquals(['application/json'], $response->getHeader('Content-Type'));
		$this->assertContains('{"a":"b"}', (string)$response);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	
	public function testInvalidJson()
	{
		$response = new JsonResponse(fopen('php://temp', 'r+'));
	}
}
