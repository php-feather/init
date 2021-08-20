<?php

use Feather\Init\Http\Input;
use PHPUnit\Framework\TestCase;

/**
 * Description of InputTest
 *
 * @author fcarbah
 */
class InputTest extends TestCase
{

    /** @var \Feather\Init\Http\Input * */
    protected static $input;

    public static function setUpBeforeClass()
    {
        $get = ['uid' => 1, 'action' => 'edit'];
        $post = [
            'name' => 'Steve Francis',
            'age' => 30,
            'username' => 'sfrancis',
            'id' => 1
        ];
        static::$input = Input::fill($get, $post);
    }

    public static function tearDownAfterClass()
    {
        static::$input = null;
        Input::tearDown();
    }

    /**
     * @test
     */
    public function willReturnValueForValidGetParameter()
    {
        $action = static::$input->get('action');
        $this->assertEquals('edit', $action);
    }

    /**
     * @test
     */
    public function willReturnNullForInvalidGetParameter()
    {
        $name = static::$input->get('name');
        $this->assertNull($name);
    }

    /**
     * @test
     */
    public function willReturnValueForValidPostParameter()
    {
        $age = static::$input->post('age');
        $this->assertEquals(30, $age);
    }

    /**
     * @test
     */
    public function willReturnNullForInvalidPostParameter()
    {
        $uid = static::$input->post('uid');
        $this->assertNull($uid);
    }

    /**
     * @test
     */
    public function willReturnValueForValidGetOrPostParameter()
    {
        $username = static::$input->all('username');
        $uid = static::$input->all('uid');
        $this->assertTrue('sfrancis' == $username);
        $this->assertEquals(1, $uid);
    }

    /**
     * @test
     */
    public function willReturnNullForInvalidGetOrPostParameter()
    {
        $height = static::$input->all('height');
        $this->assertNull($height);
    }

    /**
     * @test
     */
    public function willReturnDefaultValueIfParameterDoesNotExist()
    {
        $id = static::$input->get('id', 100);
        $this->assertEquals(100, $id);
    }

    /**
     * @test
     */
    public function willReturnParameterBagIfNoKeySpecified()
    {
        $postBag = static::$input->post();
        $getBag = static::$input->get();

        $this->assertTrue($postBag instanceof \Feather\Init\Http\Parameters\ParameterBag);
        $this->assertTrue($getBag instanceof \Feather\Init\Http\Parameters\ParameterBag);
    }

}
