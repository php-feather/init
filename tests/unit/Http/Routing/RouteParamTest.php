<?php

use Feather\Init\Http\Routing\RouteParam;
use PHPUnit\Framework\TestCase;

/**
 * Description of RouteParamTest
 *
 * @author fcarbah
 */
class RouteParamTest extends TestCase
{

    /** @var \Feather\Init\Http\Routing\RouteParam * */
    protected $routeParam;

    public function setUp()
    {
        $this->routeParam = new RouteParam();
        $this->routeParam->setUri('user/{id}/{:action}')
                ->setOriginalUri('user/{id}/{:action}');
    }

    public function tearDown()
    {
        $this->routeParam = null;
    }

    /**
     * @test
     */
    public function hasUriParameters()
    {
        $params = $this->routeParam->getParams();
        $this->assertEquals(2, count($params));
    }

    /**
     * @test
     */
    public function hasOneRequiredUriParameter()
    {
        $params = $this->routeParam->getParams();
        $requiredParams = array_filter($params, function($param) {
            return $param['required'];
        });

        $this->assertEquals(1, count($requiredParams));
    }

    /**
     * @test
     */
    public function hasOneOptionalUriParameter()
    {
        $params = $this->routeParam->getParams();
        $optionalParams = array_filter($params, function($param) {
            return !$param['required'];
        });

        $this->assertEquals(1, count($optionalParams));
    }

    /**
     * @test
     */
    public function hasNoUriParameter()
    {
        $this->routeParam->setUri('/user/1/edit')
                ->setOriginalUri('user/1/edit');

        $params = $this->routeParam->getParams();

        $this->assertEmpty($params);
    }

}
