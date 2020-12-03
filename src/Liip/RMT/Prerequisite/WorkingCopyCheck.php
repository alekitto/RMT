<?php

/*
 * This file is part of the project RMT
 *
 * Copyright (c) 2013, Liip AG, http://www.liip.ch
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liip\RMT\Prerequisite;

use Exception;
use Liip\RMT\Context;
use Liip\RMT\Information\InformationRequest;
use Liip\RMT\Action\BaseAction;

/**
 * Ensure VCS working copy is clean
 */
class WorkingCopyCheck extends BaseAction
{
    /**
     * Exception code when working copy is not clean.
     *
     * @var int
     */
    public const EXCEPTION_CODE = 412;

    public $ignoreCheckOptionName = 'ignore-check';

    public function __construct($options = [])
    {
        parent::__construct(array_merge(['allow-ignore' => false], $options));
    }

    public function getTitle()
    {
        return 'Check that your working copy is clean';
    }

    public function execute()
    {
        // Allow to be skipped when explicitly activated from the config
        if (Context::get('information-collector')->getValueFor($this->ignoreCheckOptionName)) {
            if ($this->options['allow-ignore']) {
                Context::get('output')->writeln('<error>requested to be ignored</error>');
                return;
            }

            throw new Exception(
                'The option "' . $this->ignoreCheckOptionName . '" only works if the "allow-ignore" configuration ' .
                'key is set to true.'
            );
        }

        $modCount = count(Context::get('vcs')->getLocalModifications());
        if ($modCount > 0) {
            throw new Exception(
                'Your working directory contains ' . $modCount . ' local modification' . ($modCount > 1 ? 's' : '') .
                '. Use the --' . $this->ignoreCheckOptionName . ' option (along with the "allow-ignore" ' .
                'configuration key set to true) to bypass this check.' . "\n" . 'WARNING, if your release task ' .
                'include a commit action, the pending changes are going to be included in the release.',
                self::EXCEPTION_CODE
            );
        }

        $this->confirmSuccess();
    }

    public function getInformationRequests()
    {
        return [
            new InformationRequest($this->ignoreCheckOptionName, [
                'description' => 'Do not process the check for a clean VCS working copy (if "allow-ignore" ' .
                    'configuration key is set to true)',
                'type' => 'confirmation',
                'interactive' => false,
            ])
        ];
    }
}
