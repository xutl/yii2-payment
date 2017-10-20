<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace xutl\payment;
/**
 * Interface StateStorageInterface
 * @package xutl\payment
 */
interface StateStorageInterface
{
    /**
     * Adds a state variable.
     * If the specified name already exists, the old value will be overwritten.
     * @param string $key variable name
     * @param mixed $value variable value
     */
    public function set($key, $value);

    /**
     * Returns the state variable value with the variable name.
     * If the variable does not exist, the `$defaultValue` will be returned.
     * @param string $key the variable name
     * @return mixed the variable value, or `null` if the variable does not exist.
     */
    public function get($key);

    /**
     * Removes a state variable.
     * @param string $key the name of the variable to be removed
     * @return bool success.
     */
    public function remove($key);
}