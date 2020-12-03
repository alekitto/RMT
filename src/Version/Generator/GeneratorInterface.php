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

interface GeneratorInterface
{
    public function __construct($options = []);

    /**
     * Return the next version number, according to the current one and optional options
     *
     * @param string $currentVersion
     *
     * @return string
     */
    public function generateNextVersion($currentVersion);

    /**
     * Function used to compare two versions. Must return:
     *  * -1 if $a is older than $b
     *  * 0 if $a and $b are the same
     *  * 1 if $a is more recent than $b
     *
     * @param string $a
     * @param string $b
     *
     * @return int
     */
    public function compareTwoVersions($a, $b);

    public function getInformationRequests();

    public function getValidationRegex();

    public function getInitialVersion();
}
