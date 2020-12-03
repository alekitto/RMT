<?php

/*
 * This file is part of the project RMT
 *
 * Copyright (c) 2013, Liip AG, http://www.liip.ch
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liip\RMT\Tests\Functional;

use Phar;

class BuildPharPackageActionTest extends RMTFunctionalTestBase
{
    public const START_VERSION = '1.0.0';

    public const GENERATED_PACKAGE_PATH = '/tmp/configured/my-new-package-1.0.1.phar';

    public const STUB_FILE = 'stub-file.php';

    public const STUB_FILE_WEB = 'stub-file-web.php';

    protected function setUp(): void
    {
        parent::setUp();

        $destination = '/tmp/configured';

        $this->createConfig('semantic', 'vcs-tag', array(
            'vcs' => 'git',
            'pre-release-actions' => array(
                'build-phar-package' => array(
                    'package-name' => 'my-new-package',
                    'destination' => $destination,
                    'excluded-paths' => '/^(?!.*excluded-file|.*\.git).*$/im',
                    'metadata' => array('owner' => 'Paddington'),
                    'default-stub-cli' => self::STUB_FILE,
                    'default-stub-web' => self::STUB_FILE_WEB,
                ),
            ),
        ));

        $this->initGit();

        exec('rm -rf ' . $destination . ' && mkdir ' . $destination);

        exec('git tag ' . self::START_VERSION);
    }

    public function testPackageNameContainsPackageNameOptionAndVersion(): void
    {
        exec('./RMT release -n', $consoleOutput, $exitCode);

        self::assertStringContainsString('my-new-package-1.0.1.phar', implode("\n", $consoleOutput));
    }

    public function testPackageIsCreatedInTheConfiguredDirectory(): void
    {
        exec('./RMT release -n', $consoleOutput, $exitCode);

        self::assertStringContainsString(self::GENERATED_PACKAGE_PATH, implode("\n", $consoleOutput));
    }

    public function testPackageDoesNotContainsExcludedPaths(): void
    {
        exec('touch excluded-file');

        exec('./RMT release -n', $consoleOutput, $exitCode);

        $extractTo = '/tmp/configured/extracted';

        $this->extractPackage(self::GENERATED_PACKAGE_PATH, $extractTo);

        exec('ls -la ' . $extractTo, $consoleOutput);

        $output = implode("\n", $consoleOutput);

        self::assertStringNotContainsString('excluded-file', $output);
        self::assertStringNotContainsString('.git', $output);
    }

    public function testPackageHasConfiguredMetadata(): void
    {
        exec('./RMT release -n');

        $phar = new Phar(self::GENERATED_PACKAGE_PATH);
        self::assertEquals(['version' => '1.0.1', 'owner' => 'Paddington'], $phar->getMetadata());
    }

    public function testPackageHasConfiguredStub(): void
    {
        exec('touch ' . self::STUB_FILE);
        exec('touch ' . self::STUB_FILE_WEB);

        exec('./RMT release -n');

        $phar = new Phar(self::GENERATED_PACKAGE_PATH);

        self::assertStringContainsString(self::STUB_FILE, $phar->getStub());
        self::assertStringContainsString(self::STUB_FILE_WEB, $phar->getStub());
    }

    protected function extractPackage($source, $target): void
    {
        $phar = new Phar($source);
        $phar->extractTo($target);
    }
}
