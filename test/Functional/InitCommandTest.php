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

use Symfony\Component\Yaml\Yaml;

class InitCommandTest extends RMTFunctionalTestBase
{
    public function testInitConfig(): void
    {
        $configFile = '.rmt.yml';
        @unlink($configFile);
        self::assertFileDoesNotExist($configFile);
        exec('./RMT init --configonly=n --vcs=git --generator=semantic-versioning --persister=vcs-tag -n');

//        $this->manualDebug();

        self::assertFileExists($configFile);
        $config = Yaml::parse(file_get_contents($configFile), true);

        $defaultConfig = $config['_default'];
        $masterConfig = $config['master'];

        self::assertEquals('git', $defaultConfig['vcs']);

        self::assertEquals('simple', $defaultConfig['version-generator']);
        self::assertEquals('semantic', $masterConfig['version-generator']);

        self::assertEquals(array('vcs-tag' => array('tag-prefix' => '{branch-name}_')), $defaultConfig['version-persister']);
        self::assertEquals(array('vcs-tag' => array('tag-prefix' => '')), $masterConfig['version-persister']);
    }
}
