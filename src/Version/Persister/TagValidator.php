<?php declare(strict_types=1);

/*
 * This file is part of the project RMT
 *
 * Copyright (c) 2013, Liip AG, http://www.liip.ch
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liip\RMT\Version\Persister;

class TagValidator
{
    private $regex;
    private $tagPrefix;

    public function __construct($regex, $tagPrefix = '')
    {
        $this->regex = $regex;
        $this->tagPrefix = $tagPrefix;
    }

    /**
     * Check if a tag is valid
     *
     * @param string $tag
     *
     * @return bool
     */
    public function isValid($tag)
    {
        if ($this->tagPrefix !== '' && strpos($tag, $this->tagPrefix) !== 0) {
            return false;
        }

        return preg_match('/^' . $this->regex . '$/', substr($tag, strlen($this->tagPrefix))) === 1;
    }

    /**
     * Remove all invalid tags from a list
     *
     * @param array $tags
     *
     * @return array
     */
    public function filtrateList($tags)
    {
        $validTags = [];
        foreach ($tags as $tag) {
            if ($this->isValid($tag)) {
                $validTags[] = $tag;
            }
        }

        return $validTags;
    }
}
