<?php declare(strict_types=1);

/*
 * This file is part of the project RMT
 *
 * Copyright (c) 2014, Liip AG, http://www.liip.ch
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liip\RMT\Prerequisite;

use Exception;
use Liip\RMT\Action\BaseAction;
use Liip\RMT\Context;
use Liip\RMT\Information\InformationRequest;

/**
 * Test if only allowed dependencies use unstable versions.
 */
class ComposerDependencyStabilityCheck extends BaseAction
{
    public const SKIP_OPTION = 'skip-composer-dependency-stability-check';
    public const DEPENDENCY_LISTS = ['require', 'require-dev'];

    private $whitelist;
    private $dependencyListWhitelists;

    public function __construct($options)
    {
        parent::__construct($options);

        $this->whitelist = [];
        $this->dependencyListWhitelists = [];

        if (isset($this->options['whitelist'])) {
            $this->createWhitelists($this->options['whitelist']);
        }
    }

    private function createWhitelists($whitelistConfig)
    {
        foreach ($whitelistConfig as $listing) {
            if (isset($listing[1])) {
                if (!in_array($listing[1], self::DEPENDENCY_LISTS, true)) {
                    throw new Exception("configuration error: " . $listing[1] . " is no valid composer dependency section");
                }
                if (!isset($this->dependencyListWhitelists[$listing[1]])) {
                    $this->dependencyListWhitelists[$listing[1]] = [];
                }
                $this->dependencyListWhitelists[$listing[1]][] = $listing[0];
            } else {
                $this->whitelist[] = $listing[0];
            }
        }
    }

    public function execute()
    {
        if (Context::get('information-collector')->getValueFor(self::SKIP_OPTION)) {
            Context::get('output')->writeln('<error>composer dependency-stability check skipped</error>');
            return;
        }

        if (!file_exists('composer.json')) {
            Context::get('output')->writeln('<error>composer.json does not exist, skipping check</error>');
            return;
        }

        if (!is_readable('composer.json')) {
            throw new Exception(
                'composer.json can not be read (permissions?), (you can force a release with option --'
                . self::SKIP_OPTION.')'
            );
        }

        $contents = json_decode(file_get_contents('composer.json'), true);

        foreach (self::DEPENDENCY_LISTS as $dependencyList) {
            if (!$this->isListIgnored($dependencyList) && $this->listExists($contents, $dependencyList)) {
                $specificWhitelist = $this->generateListSpecificWhitelist($dependencyList);
                $this->checkDependencies($contents[$dependencyList], $specificWhitelist);
            }
        }

        $this->confirmSuccess();
    }

    /**
     * @param $dependencyList
     * @return mixed
     */
    private function isListIgnored($dependencyList)
    {
        $index = 'ignore-' . $dependencyList;

        return isset($this->options[$index]) && $this->options[$index] === true;
    }

    /**
     * @param $contents
     * @param $dependencyList
     * @return bool
     */
    private function listExists($contents, $dependencyList)
    {
        return isset($contents[$dependencyList]);
    }

    /**
     * @param $dependencyList
     * @return array
     */
    private function generateListSpecificWhitelist($dependencyList)
    {
        if (isset($this->dependencyListWhitelists[$dependencyList])) {
            return array_merge($this->whitelist, $this->dependencyListWhitelists[$dependencyList]);
        }

        return $this->whitelist;
    }

    /**
     * check every element inside this array for composer version strings and throw an exception if the dependency is
     * not stable
     *
     * @param $dependencyList array
     * @param $whitelist array
     * @throws Exception
     */
    private function checkDependencies($dependencyList, $whitelist = []) {
        foreach ($dependencyList as $dependency => $version) {
            if (($this->startsWith($version, 'dev-') || $this->endsWith($version, '@dev'))
                && !in_array($dependency, $whitelist, true)) {
                throw new Exception(
                    $dependency
                    . ' uses dev-version but is not listed on whitelist '
                    . ' (you can force a release with option --'.self::SKIP_OPTION.')'
                );
            }
        }
    }

    /**
     * @param $haystack string
     * @param $needle string
     * @return bool
     */
    private function startsWith($haystack, $needle)
    {
        return $haystack[0] === $needle[0]
            ? strncmp($haystack, $needle, strlen($needle)) === 0
            : false;
    }

    /**
     * @param $haystack string
     * @param $needle string
     * @return bool
     */
    private function endsWith($haystack, $needle) {
        return $needle === '' || substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }

    public function getInformationRequests()
    {
        return [
            new InformationRequest(
                self::SKIP_OPTION,
                [
                    'description' => 'Do not check composer.json for minimum-stability before the release',
                    'type' => 'confirmation',
                    'interactive' => false,
                ]
            ),
        ];
    }
}
