<?php
namespace GuzzleHttp\Tests\Stream\Exception;

use GuzzleHttp\Stream\Exception\SeekException;
use GuzzleHttp\Stream\Stream;
use PHPUnit_Framework_TestCase;

class SeekExceptionTest extends PHPUnit_Framework_TestCase
{
    public function testHasStream()
    {
        $s = Stream::factory('foo');
        $e = new SeekException($s, 10);
        $this->assertSame($s, $e->getStream());
        $this->assertContains('10', $e->getMessage());
    }
}
