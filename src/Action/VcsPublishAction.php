<?php declare(strict_types=1);

/*
 * This file is part of the project RMT
 *
 * Copyright (c) 2013, Liip AG, http://www.liip.ch
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liip\RMT\Action;

use Liip\RMT\Information\InformationRequest;
use Liip\RMT\Context;

/**
 * Push current branch and tag to version control
 */
class VcsPublishAction extends BaseAction
{
    public const AUTO_PUBLISH_OPTION = 'auto-publish';

    public function __construct($options = [])
    {
        parent::__construct(array_merge([
            'ask-confirmation' => true,
            'remote-name' => null,
            'ask-remote-name' => false,
        ], $options));
    }

    public function execute()
    {
        if ($this->options['ask-confirmation']) {

            // Ask the question if there is no confirmation yet
            $ic = Context::get('information-collector');
            if (!$ic->hasValueFor(self::AUTO_PUBLISH_OPTION)) {
                $answer = Context::get('output')->askConfirmation('Do you want to publish your release (default: <green>y</green>): ', Context::get('input'));
                $ic->setValueFor(self::AUTO_PUBLISH_OPTION, $answer ? 'y' : 'n');
            }

            // Skip if the user didn't ask for publishing
            if ($ic->getValueFor(self::AUTO_PUBLISH_OPTION) !== 'y') {
                Context::get('output')->writeln('<error>requested to be ignored</error>');

                return;
            }
        }

        Context::get('vcs')->publishChanges($this->getRemote());
        Context::get('vcs')->publishTag(
            Context::get('version-persister')->getTagFromVersion(
                Context::getParam('new-version')
            ),
            $this->getRemote()
        );

        $this->confirmSuccess();
    }

    public function getInformationRequests()
    {
        $requests = [];
        if ($this->options['ask-confirmation']) {
            $requests[] = new InformationRequest(self::AUTO_PUBLISH_OPTION, [
                'description' => 'Changes will be published automatically',
                'type' => 'yes-no',
                'interactive' => false,
            ]);
        }
        if ($this->options['ask-remote-name']) {
            $requests[] = new InformationRequest('remote', [
                'description' => 'Remote to push changes',
                'type' => 'text',
                'default' => 'origin',
            ]);
        }

        return $requests;
    }

    /**
     * Return the remote name where to publish or null if not defined
     *
     * @return string|null
     */
    protected function getRemote()
    {
        if ($this->options['ask-remote-name']) {
            return Context::get('information-collector')->getValueFor('remote');
        }

        return $this->options[ 'remote-name' ] ?? null;
    }
}
