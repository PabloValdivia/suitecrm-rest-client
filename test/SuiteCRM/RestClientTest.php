<?php
namespace SuiteCRM;
class RestClientTest extends \PHPUnit_Framework_TestCase {

    public static $rest_client;

    public static function setUpBeforeClass() {
        RestClientTest::$rest_client = new RestClient('testuser', 'testpassword', 'http://localhost/sdk/instances/suitecrm');
    }

    public function testPhpUnit() {
        $this->assertEquals(false, false);
    }


}
