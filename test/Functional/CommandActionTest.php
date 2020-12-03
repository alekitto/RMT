<?php declare(strict_types=1);

/*
 * This file is part of the project RMT
 *
 * Copyright (c) 2013, Liip AG, http://www.liip.ch
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liip\RMT\Tests\Functional;

class CommandActionTest extends RMTFunctionalTestBase
{
    public function testCommand(): void
    {
        $this->createChangelog('simple');
        $this->createConfig('simple', 'changelog', [
            'pre-release-actions' => [
                'command' => ['cmd' => 'echo "hello world"'],
            ],
        ]);
        exec('./RMT release -n --no-ansi --comment="test"', $output);
        $output = implode("\n", $output);
//        $this->manualDebug();
        self::assertStringContainsString('Command Action : echo "hello world"', $output);
    }
}
