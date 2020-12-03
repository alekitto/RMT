<?php

/*
 * This file is part of the project RMT
 *
 * Copyright (c) 2013, Liip AG, http://www.liip.ch
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liip\RMT\Information;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Collect user info
 */
class InformationCollector
{
    protected static $standardRequests = [
        'comment' => [
            'description' => 'Comment associated with the release',
            'type' => 'text',
        ],
        'type' => [
            'description' => 'Release type, can be major, minor or patch',
            'type' => 'choice',
            'choices' => ['major', 'minor', 'patch'],
            'choices_shortcuts' => ['m' => 'major', 'i' => 'minor', 'p' => 'patch'],
            'default' => 'patch',
        ],
        'label' => [
            'description' => 'Release label, can be rc, beta, alpha or none',
            'type' => 'choice',
            'choices' => ['rc', 'beta', 'alpha', 'none'],
            'choices_shortcuts' => ['rc' => 'rc', 'b' => 'beta', 'a' => 'alpha', 'n' => 'none'],
            'default' => 'none',
        ],
    ];

    protected $requests = [];
    protected $values = [];

    public function registerRequest($request)
    {
        $name = $request->getName();
        if (in_array($name, static::$standardRequests, true)) {
            throw new Exception("Request [$name] is reserved as a standard request name, choose an other name please");
        }

        if ($this->hasRequest($name)) {
            throw new Exception("Request [$name] already registered");
        }

        $this->requests[$name] = $request;
    }

    public function registerRequests($list)
    {
        foreach ($list as $request) {
            if (is_string($request)) {
                $this->registerStandardRequest($request);
            } elseif ($request instanceof InformationRequest) {
                $this->registerRequest($request);
            } else {
                throw new Exception('Invalid request, must a Request class or a string for standard requests');
            }
        }
    }

    public function registerStandardRequest($name)
    {
        if (! array_key_exists($name, static::$standardRequests)) {
            throw new Exception("There is no standard request named [$name]");
        }
        if (!isset($this->requests[$name])) {
            $this->requests[$name] = new InformationRequest($name, static::$standardRequests[$name]);
        }
    }

    /**
     * @param string $name
     *
     * @return InformationRequest
     */
    public function getRequest($name)
    {
        if (!$this->hasRequest($name)) {
            throw new InvalidArgumentException("There is no information request named [$name]");
        }

        return $this->requests[$name];
    }

    public function hasRequest($name)
    {
        return array_key_exists($name, $this->requests);
    }

    /**
     * Return a set of command request, converted from the Base Request
     *
     * @return InputOption[]
     */
    public function getCommandOptions()
    {
        $consoleOptions = [];
        foreach ($this->requests as $name => $request) {
            if ($request->isAvailableAsCommandOption()) {
                $consoleOptions[$name] = $request->convertToCommandOption();
            }
        }

        return $consoleOptions;
    }

    public function hasMissingInformation()
    {
        foreach ($this->requests as $request) {
            if (!$request->hasValue()) {
                return true;
            }
        }

        return false;
    }

    public function getInteractiveQuestions()
    {
        $questions = [];
        foreach ($this->requests as $name => $request) {
            if ($request->isAvailableForInteractive() && !$request->hasValue()) {
                $questions[$name] = $request->convertToInteractiveQuestion();
            }
        }

        return $questions;
    }

    public function handleCommandInput(InputInterface $input)
    {
        foreach ($input->getOptions() as $name => $value) {
            if ($this->hasRequest($name) && ($value !== null && $value !== false)) {
                $this->getRequest($name)->setValue($value);
            }
        }
    }

    public function setValueFor($requestName, $value)
    {
        $this->getRequest($requestName)->setValue($value);
    }

    public function hasValueFor($requestName)
    {
        return $this->getRequest($requestName)->hasValue();
    }

    public function getValueFor($requestName, $default = null)
    {
        if ($this->hasRequest($requestName)) {
            return $this->getRequest($requestName)->getValue();
        }

        if (func_num_args() === 2) {
            return $default;
        }

        throw new Exception("No request named $requestName");
    }
}
