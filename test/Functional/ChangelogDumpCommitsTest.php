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

class ChangelogDumpCommitsTest extends RMTFunctionalTestBase
{
    public function testDump(): void
    {
        $this->createConfig('semantic', 'vcs-tag', [
            'vcs' => 'git',
            'pre-release-actions' => [
                'changelog-update' => [
                    'format' => 'semantic',
                    'dump-commits' => true,
                ],
                'vcs-commit' => null,
            ],
        ]);
        $this->initGit();

        // First release must contain as message explaining why there is no commit dump
        exec('./RMT release -n --confirm-first --comment="First release"', $output);
        $output = implode("\n", $output);
        self::assertStringContainsString('No commits dumped as this is the first release', $output);

        // Next release must update the CHANGELOG
        exec('echo "text" > new-file && git add -A && git commit -m "Second commit"');
        exec('echo "text2" >> new-file && git commit -am "Third commit"');
        exec('./RMT release -n --comment="Second release"', $output);
        $changelog = file_get_contents($this->tempDir . '/CHANGELOG');
        self::assertStringContainsString('Second commit', $changelog);
        self::assertStringContainsString('Third commit', $changelog);
    }
}
