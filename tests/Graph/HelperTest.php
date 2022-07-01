<?php

declare(strict_types=1);

namespace Platine\Test\Workflow\Graph;

use Platine\Dev\PlatineTestCase;
use Platine\Workflow\Graph\Helper;

/**
 * Helper class tests
 *
 * @group core
 * @group workflow
 */
class HelperTest extends PlatineTestCase
{
    public function testEscape(): void
    {
        $this->assertEquals(Helper::escape('Foo <bar>'), '"Foo <bar>"');
        $this->assertEquals(Helper::escape(' '), '""');
        $this->assertEquals(Helper::escape('R&D'), '"R#amp;D"');
    }

    public function testGetId(): void
    {
        $this->assertEquals(Helper::getId('123'), md5('123'));
    }
}
