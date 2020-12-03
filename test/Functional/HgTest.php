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

class HgTest extends RMTFunctionalTestBase
{
    public static function cleanTags($tags)
    {
        return array_map(static function ($t) {
            $parts = explode(' ', $t);

            return $parts[0];
        }, $tags);
    }

    public function testInitialVersion(): void
    {
        $this->initHg();
        $this->createConfig('simple', ['name' => 'vcs-tag', 'tag-prefix' => 'v'], ['vcs' => 'hg']);
        exec('./RMT release -n --confirm-first');
        exec('hg tags', $tags);
        self::assertEquals(['tip', 'v1'], static::cleanTags($tags));
    }

    public function testInitialVersionSemantic(): void
    {
        $this->initHg();
        $this->createConfig('semantic', 'vcs-tag', ['vcs' => 'hg']);
        exec('./RMT release -n  --type=patch --confirm-first');
        exec('hg tags', $tags);
        self::assertEquals(['tip', '0.0.1'], static::cleanTags($tags));
    }

    public function testSimple(): void
    {
        $this->initHg();
        exec('hg tag v1');
        exec('hg tag v3');
        exec('hg tag toto');
        $this->createConfig('simple', ['name' => 'vcs-tag', 'tag-prefix' => 'v'], ['vcs' => 'hg']);
        exec('./RMT release -n');
        exec('hg tags', $tags);
        self::assertEquals(['tip', 'v4', 'toto', 'v3', 'v1'], static::cleanTags($tags));
    }

    public function testSemantic(): void
    {
        $this->initHg();
        exec('hg tag 2.1.19');
        $this->createConfig('semantic', 'vcs-tag', ['vcs' => 'hg']);
        exec('./RMT release -n --type=minor');
        exec('hg tags', $tags);
        self::assertEquals(['tip', '2.2.0', '2.1.19'], static::cleanTags($tags));
    }

    public function testTagPrefix(): void
    {
        $this->initHg();
        exec('hg tag v2');
        exec('hg tag v_1');
        $this->createConfig('simple', ['name' => 'vcs-tag', 'tag-prefix' => 'v_'], ['vcs' => 'hg']);
        exec('./RMT release -n');
        exec('hg tags', $tags);
        self::assertEquals(['tip', 'v_2', 'v_1', 'v2'], static::cleanTags($tags));
    }

    public function testTagPrefixWithBranchNamePlaceHolder(): void
    {
        $this->initHg();
        $this->createConfig('simple', ['name' => 'vcs-tag', 'tag-prefix' => '_{branch-name}_'], ['vcs' => 'hg']);
        exec('./RMT release -n --confirm-first');
        exec('hg tags', $tags);
        self::assertEquals(['tip', '_default_1'], static::cleanTags($tags));
    }

    protected function initHg(): void
    {
        exec('hg init');
        exec('echo "[ui]" > .hg/hgrc');
        exec('echo "username = John Doe <test@test.com>" >> .hg/hgrc');
        exec('hg add *');
        exec('hg commit -m "First commit"');
    }
}
