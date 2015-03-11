<?php

/*
 * This file is part of the puli/repository-manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\RepositoryManager\Api\Package;

use Puli\RepositoryManager\Api\Config\ConfigFileManager;
use Puli\RepositoryManager\Api\Environment\ProjectEnvironment;
use Puli\RepositoryManager\Api\InvalidConfigException;
use Puli\RepositoryManager\Api\IOException;

/**
 * Manages changes to the root package file.
 *
 * Use this class to make persistent changes to the puli.json of a project.
 * Whenever you call methods in this class, the changes will be written to disk.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface RootPackageFileManager extends ConfigFileManager
{
    /**
     * Returns the project environment.
     *
     * @return ProjectEnvironment The project environment.
     */
    public function getEnvironment();

    /**
     * Returns the managed package file.
     *
     * @return RootPackageFile The managed package file.
     */
    public function getPackageFile();

    /**
     * Returns the package name configured in the package file.
     *
     * @return null|string The configured package name.
     */
    public function getPackageName();

    /**
     * Sets the package name configured in the package file.
     *
     * @param string $packageName The package name.
     */
    public function setPackageName($packageName);

    /**
     * Installs a plugin class.
     *
     * The plugin class must be passed as fully-qualified name of a class that
     * implements {@link PuliPlugin}. Plugin constructors must not have
     * mandatory parameters.
     *
     * @param string $pluginClass The fully qualified plugin class name.
     *
     * @throws InvalidConfigException If a class is not found, is not a class,
     *                                does not implement {@link PuliPlugin}
     *                                or has required constructor parameters.
     */
    public function installPluginClass($pluginClass);

    /**
     * Returns whether a plugin class is installed.
     *
     * @param string $pluginClass   The fully qualified plugin class name.
     * @param bool   $includeGlobal If set to `true`, both plugins installed in
     *                              the configuration of the root package and
     *                              plugins installed in the global configuration
     *                              are considered. If set to `false`, only the
     *                              plugins defined in the root package are
     *                              considered.
     *
     * @return bool Whether the plugin class is installed.
     *
     * @see installPluginClass()
     */
    public function isPluginClassInstalled($pluginClass, $includeGlobal = true);

    /**
     * Returns all installed plugin classes.
     *
     * @param bool $includeGlobal If set to `true`, both plugins installed in
     *                            the configuration of the root package and
     *                            plugins installed in the global configuration
     *                            are returned. If set to `false`, only the
     *                            plugins defined in the root package are
     *                            returned.
     *
     * @return string[] The fully qualified plugin class names.
     *
     * @see installPluginClass()
     */
    public function getPluginClasses($includeGlobal = true);

    /**
     * Sets an extra key in the file.
     *
     * The file is saved directly after setting the key.
     *
     * @param string $key   The key name.
     * @param mixed  $value The stored value.
     *
     * @throws IOException If the file cannot be written.
     */
    public function setExtraKey($key, $value);

    /**
     * Sets the extra keys in the file.
     *
     * The file is saved directly after setting the keys.
     *
     * @param string[] $values A list of values indexed by their key names.
     *
     * @throws IOException If the file cannot be written.
     */
    public function setExtraKeys(array $values);

    /**
     * Removes an extra key from the file.
     *
     * The file is saved directly after removing the key.
     *
     * @param string $key The name of the removed extra key.
     *
     * @throws IOException If the file cannot be written.
     */
    public function removeExtraKey($key);

    /**
     * Removes multiple extra keys from the file.
     *
     * The file is saved directly after removing the keys.
     *
     * @param string[] $keys The names of the removed extra keys.
     *
     * @throws IOException If the file cannot be written.
     */
    public function removeExtraKeys(array $keys);

    /**
     * Removes all extra keys from the file.
     *
     * The file is saved directly after removing the keys.
     *
     * @throws IOException If the file cannot be written.
     */
    public function clearExtraKeys();

    /**
     * Returns whether an extra key exists.
     *
     * @param string $key The extra key to search.
     *
     * @return bool Returns `true` if the file contains the key and `false`
     *              otherwise.
     */
    public function hasExtraKey($key);

    /**
     * Returns whether the file contains any extra keys.
     *
     * @return bool Returns `true` if the file contains extra keys and `false`
     *              otherwise.
     */
    public function hasExtraKeys();

    /**
     * Returns the value of a configuration key.
     *
     * @param string $key     The name of the extra key.
     * @param mixed  $default The value to return if the key was not set.
     *
     * @return mixed The value of the key or the default value, if none is set.
     */
    public function getExtraKey($key, $default = null);

    /**
     * Returns the values of all extra keys set in the file.
     *
     * @return array A mapping of configuration keys to values.
     */
    public function getExtraKeys();
}
