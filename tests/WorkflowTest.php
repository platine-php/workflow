<?php

declare(strict_types=1);

namespace Platine\Test\Workflow;

use Platine\Dev\PlatineTestCase;
use Platine\Workflow\Workflow;

/**
 * Workflow class tests
 *
 * @group core
 * @group workflow
 */
class WorkflowTest extends PlatineTestCase
{
    public function testConstructor(): void
    {
        $w = new Workflow();
        $this->assertInstanceOf(Workflow::class, $w);
    }
}
