<?php

/*
 * This file is part of the project RMT
 *
 * Copyright (c) 2013, Liip AG, http://www.liip.ch
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Liip\RMT;

use InvalidArgumentException;

class Context
{
    protected $services = [];
    protected $params = [];
    protected $lists = [];

    private static $instance;

    protected function __construct()
    {
    }

    /**
     * @return Context
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function setService($id, $classOrObject, $options = null)
    {
        if (is_object($classOrObject)) {
            $this->services[$id] = $classOrObject;
        } elseif (is_string($classOrObject)) {
            $this->validateClass($classOrObject);
            $this->services[$id] = [$classOrObject, $options];
        } else {
            throw new InvalidArgumentException('setService() only accept an object or a valid class name');
        }
    }

    public function getService($id)
    {
        if (!isset($this->services[$id])) {
            throw new InvalidArgumentException("There is no service defined with id [$id]");
        }
        if (is_array($this->services[$id])) {
            $this->services[$id] = $this->instanciateObject($this->services[$id]);
        }

        return $this->services[$id];
    }

    public function setParameter($id, $value)
    {
        $this->params[$id] = $value;
    }

    public function getParameter($id)
    {
        if (!isset($this->params[$id])) {
            throw new InvalidArgumentException("There is no param defined with id [$id]");
        }

        return $this->params[$id];
    }

    public function createEmptyList($id)
    {
        $this->lists[$id] = [];
    }

    public function addToList($id, $class, $options = null)
    {
        $this->validateClass($class);
        if (!isset($this->lists[$id])) {
            $this->createEmptyList($id);
        }
        $this->lists[$id][] = [$class, $options];
    }

    public function getList($id)
    {
        if (!isset($this->lists[$id])) {
            throw new InvalidArgumentException("There is no list defined with id [$id]");
        }
        foreach ($this->lists[$id] as $pos => $object) {
            if (is_array($object)) {
                $this->lists[$id][$pos] = $this->instanciateObject($object);
            }
        }

        return $this->lists[$id];
    }

    protected function instanciateObject($objectDefinition)
    {
        [$className, $options] = $objectDefinition;

        return new $className($options);
    }

    protected function validateClass($className)
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException("The class [$className] does not exist");
        }
    }

    /**
     * Shortcut to retried a service
     *
     * @param string $serviceName
     *
     * @return mixed
     */
    public static function get($serviceName)
    {
        return self::getInstance()->getService($serviceName);
    }

    /**
     * Shortcut to retried a parameter
     *
     * @param string $name
     *
     * @return mixed
     */
    public static function getParam($name)
    {
        return self::getInstance()->getParameter($name);
    }
}
