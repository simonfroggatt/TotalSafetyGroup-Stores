<?php
/**
 * Copyright 2014 Klarna AB
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * File containing the TestCase class.
 */

namespace Klarna\Rest\Tests\Unit;

use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use Klarna\Rest\Transport\Connector;
use PHPUnit_Framework_TestCase;

/**
 * Base unit test case class.
 */
class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var RequestInterface
     */
    protected $response;

    /**
     * @var ResponseInterface
     */
    protected $request;

    /**
     * @var Connector
     */
    protected $connector;

    /**
     * Sets up the test fixtures.
     */
    protected function setUp()
    {
        $this->request = $this->getMockBuilder('GuzzleHttp\Message\RequestInterface')
            ->getMock();

        $this->response = $this->getMockBuilder('GuzzleHttp\Message\ResponseInterface')
            ->getMock();

        $this->connector = $this->getMockBuilder('Klarna\Rest\Transport\Connector')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
