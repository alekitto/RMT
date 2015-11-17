<?php

/*
 * This file is part of the project RMT
 *
 * Copyright (c) 2013, Liip AG, http://www.liip.ch
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liip\RMT\VCS;

use Liip\RMT\Exception\InvalidTagNameException;
use Liip\RMT\Exception\TagAlreadyExistsException;

class Hg extends BaseVCS
{
    protected $dryRun = false;

    public function getAllModificationsSince($tag, $color = true, $noMergeCommits = false)
    {
        $noMergeCommits = $noMergeCommits ? '--no-merges' : '';
        $modifications = $this->executeHgCommand("log --template '{node|short} {desc}\n' -r tip:$tag $noMergeCommits");
        array_pop($modifications); // remove the last commit since it is the one described by the tag

        return $modifications;
    }

    public function getModifiedFilesSince($tag)
    {
        $data = $this->executeHgCommand("status --rev $tag:tip");
        $files = array();
        foreach ($data as $d) {
            $parts = explode(' ', $d);
            $files[$parts[1]] = $parts[0];
        }

        return $files;
    }

    public function getLocalModifications()
    {
        return $this->executeHgCommand('status');
    }

    public function getTags()
    {
        $tags = $this->executeHgCommand('tags');
        $tags = array_map(function ($t) {
            $parts = explode(' ', $t);

            return $parts[0];
        }, $tags);

        return $tags;
    }

    public function validateTag($tagName)
    {
        if(preg_match("/[:\r\n]/", $tagName) > 0 || preg_match("/^[0-9]*$/", $tagName) > 0) {
            throw new InvalidTagNameException("'$tagName' is an invalid tag name for mercurial.");
        }

        if(in_array($tagName, $this->getTags())) {
            throw new TagAlreadyExistsException("'$tagName' already exists.");
        }
    }

    public function createTag($tagName)
    {
        return $this->executeHgCommand("tag $tagName");
    }

    public function publishTag($tagName, $remote = null)
    {
        // nothing to do, tags are published with other changes
    }

    public function publishChanges($remote = null)
    {
        $remote = $remote === null ? 'default' : $remote;
        $this->executeHgCommand("push $remote");
    }

    public function saveWorkingCopy($commitMsg = '')
    {
        $this->executeHgCommand('addremove');
        $this->executeHgCommand("commit -m \"$commitMsg\"");
    }

    public function getCurrentBranch()
    {
        $data = $this->executeHgCommand('branch');

        return $data[0];
    }

    protected function executeHgCommand($cmd)
    {
        if ($this->dryRun) {
            $binary = 'hg --dry-run ';
        } else {
            $binary = 'hg ';
        }

        // Execute
        $cmd = $binary.$cmd;
        exec($cmd, $result, $exitCode);

        if ($exitCode !== 0) {
            throw new \Liip\RMT\Exception('Error while executing hg command: '.$cmd);
        }

        return $result;
    }
}
