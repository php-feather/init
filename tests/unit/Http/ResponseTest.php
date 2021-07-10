<?php

use Feather\Init\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Description of ResponseTest
 *
 * @author fcarbah
 */
class ResponseTest extends TestCase
{

    /** @var \Feather\Init\Http\Response * */
    protected $response;

    public function setUp()
    {
        $this->response = Response::getInstance();
    }

    public function tearDown()
    {
        $this->response = null;
    }

    /**
     * @test
     */
    public function willSetHeaders()
    {

        $headers = ['Content-Type' => 'text/plain', 'Accept' => '*'];
        $this->response->setHeaders($headers);

        $headers = $this->response->getHeaders();

        $this->assertTrue(count($headers) === 2);
        $this->assertEquals('*', $headers->get('Accept'));
    }

}
