<?php

use PHPUnit\Framework\TestCase;
use Feather\Init\Http\Request;

/**
 * Description of RequestTest
 *
 * @author fcarbah
 */
class RequestTest extends TestCase
{

    /** @var \Feather\Init\Http\Request * */
    protected static $request;

    public static function setUpBeforeClass()
    {
        $get = ['user_id' => '1'];

        $post = [
            'firstname' => 'Steve',
            'lastname' => 'Francis',
            'address' => '1024 Bain Ave',
            'city' => 'Boston',
            'state' => 'RI',
            'save' => '1',
            'rating' => '4.96'
        ];

        $server = [
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'POST',
            'HTTP_COOKIE' => 'fasession=1Qwtry7890;uid=02356',
            'SERVER_ADDR' => '127.0.0.1',
            'REMOTE_ADDR' => '::1'
        ];

        static::$request = Request::create($get, $post, $server, [], []);
    }

    public static function tearDownAfterClass()
    {
        static::$request = null;
        Request::tearDown();
        Feather\Init\Http\Input::tearDown();
    }

    /**
     * @test
     */
    public function hasRequestPostParams()
    {
        $postBag = static::$request->post();
        $this->assertTrue($postBag->count() > 0);
    }

    /**
     * @test
     */
    public function hasRequestGetParams()
    {
        $getBag = static::$request->get();

        $this->assertTrue($getBag->count() > 0);
    }

    /**
     * @test
     */
    public function willReturnValueOfValidPostParam()
    {
        $firstName = static::$request->post('firstname');
        $this->assertTrue($firstName == 'Steve');
    }

    /**
     * @test
     */
    public function willReturnNullForMIssingPostParam()
    {
        $id = static::$request->post('user_id');
        $this->assertNull($id);
    }

    /**
     * @test
     */
    public function willReturnDefaultValueForMissingPostParam()
    {
        $id = static::$request->post('user_id', 100);
        $this->assertEquals(100, $id);
    }

    /**
     * @test
     */
    public function willReturnValueOfValidGetParam()
    {
        $id = static::$request->get('user_id');
        $this->assertTrue('1' == $id);
    }

    /**
     * @test
     */
    public function willReturnNullForMissingGetParam()
    {
        $lastname = static::$request->get('lastname');
        $this->assertNull($lastname);
    }

    /**
     * @test
     */
    public function willReturnDefaultValueForMissingGetParam()
    {
        $name = static::$request->get('firstname', 'John');
        $this->assertTrue($name == 'John');
    }

    /**
     * @test
     */
    public function canGetRequestURI()
    {
        $uri = static::$request->getUri();
        $this->assertTrue($uri == '/');
    }

    /**
     * @test
     */
    public function canGetHost()
    {
        $host = static::$request->getHost();
        $this->assertTrue($host == 'localhost');
    }

    /**
     * @test
     */
    public function canGetClientIPAddress()
    {
        $clientIp = static::$request->getClientIp();
        $this->assertTrue($clientIp == '::1');
    }

}
