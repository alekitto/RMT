<?php declare(strict_types=1);

/*
 * This file is part of the project RMT
 *
 * Copyright (c) 2013, Liip AG, http://www.liip.ch
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liip\RMT\Tests\Unit\Version;

use Liip\RMT\Version\Generator\SimpleGenerator;
use PHPUnit\Framework\TestCase;

class SimpleGeneratorTest extends TestCase
{
    public function testIncrement(): void
    {
        $generator = new SimpleGenerator();
        self::assertEquals(4, $generator->generateNextVersion(3));
    }
}
