<?php declare(strict_types=1);

/*
 * This file is part of the project RMT
 *
 * Copyright (c) 2013, Liip AG, http://www.liip.ch
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liip\RMT\Changelog;

use Liip\RMT\Exception;
use Liip\RMT\Exception\NoReleaseFoundException;

/**
 * Class to read/write the changelog file
 */
class ChangelogManager
{
    protected $filePath;
    protected $formatter;

    public function __construct($filePath, $format)
    {
        // File name validation
        if (!file_exists($filePath)) {
            touch($filePath);
        }
        if (!is_file($filePath) || !is_writable($filePath)) {
            throw new Exception("Unable to write file [$filePath]");
        }
        $this->filePath = $filePath;

        // Store the formatter
        $formatterClass = 'Liip\\RMT\\Changelog\\Formatter\\'.ucfirst($format).'ChangelogFormatter';
        if (!class_exists($formatterClass)) {
            throw new \Exception("There is no formatter for [$format]");
        }
        $this->formatter = new $formatterClass();
    }

    public function update($version, $comment, $options = [])
    {
        $lines = file($this->filePath, FILE_IGNORE_NEW_LINES);
        $lines = $this->formatter->updateExistingLines($lines, $version, $comment, $options);
        file_put_contents($this->filePath, implode("\n", $lines));
    }

    public function getCurrentVersion()
    {
        $changelog = file_get_contents($this->filePath);
        $result = preg_match($this->formatter->getLastVersionRegex(), $changelog, $match);
        if ($result === 1) {
            return $match[1];
        }
        throw new NoReleaseFoundException(
            'There is a format error in the CHANGELOG file, impossible to read the last version number'
        );
    }
}
