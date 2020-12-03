<?php declare(strict_types=1);

/*
 * This file is part of the project RMT
 *
 * Copyright (c) 2013, Liip AG, http://www.liip.ch
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liip\RMT\Version\Generator;

class SimpleGenerator implements GeneratorInterface
{
    public function __construct($options = [])
    {
    }

    public function generateNextVersion($currentVersion)
    {
        return ++$currentVersion;
    }

    public function getInformationRequests()
    {
        return [];
    }

    public function getValidationRegex()
    {
        return '\d+';
    }

    public function getInitialVersion()
    {
        return '0';
    }

    public function compareTwoVersions($a, $b)
    {
        return $a <=> $b;
    }
}
