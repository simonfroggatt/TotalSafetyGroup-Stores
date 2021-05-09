<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Tests\Constraints;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class AllTest extends TestCase
{
    /**
     * @expectedException ConstraintDefinitionException
     */
    public function testRejectNonConstraints()
    {
        new All(array(
            'foo',
        ));
    }

    /**
     * @expectedException ConstraintDefinitionException
     */
    public function testRejectValidConstraint()
    {
        new All(array(
            new Valid(),
        ));
    }
}
