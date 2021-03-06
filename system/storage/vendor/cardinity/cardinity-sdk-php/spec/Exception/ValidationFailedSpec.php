<?php

namespace spec\Cardinity\Exception;

use Cardinity\Method\ResultObject;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use RuntimeException;

class ValidationFailedSpec extends ObjectBehavior
{
    function let(RuntimeException $exception, ResultObject $error)
    {
        $this->beConstructedWith(
            $exception,
            $error
        );
    }

    function it_should_extend_request()
    {
        $this->shouldHaveType('Cardinity\Exception\Request');
    }

    function it_should_return_correct_code()
    {
        $this->getCode()->shouldReturn(400);
    }
}
