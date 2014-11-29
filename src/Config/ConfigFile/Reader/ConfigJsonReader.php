<?php

/*
 * This file is part of the Puli Repository Manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\RepositoryManager\Config\ConfigFile\Reader;

use Puli\RepositoryManager\Config\ConfigFile\ConfigFile;
use Puli\RepositoryManager\FileNotFoundException;
use Puli\RepositoryManager\InvalidConfigException;
use Webmozart\Json\DecodingFailedException;
use Webmozart\Json\JsonDecoder;
use Webmozart\Json\ValidationFailedException;

/**
 * Reads JSON configuration files.
 *
 * The data in the JSON file is validated against the schema
 * `res/schema/config-schema.json`.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ConfigJsonReader implements ConfigFileReaderInterface
{
    /**
     * Reads a JSON configuration file.
     *
     * The data in the JSON file is validated against the schema
     * `res/schema/config-schema.json`.
     *
     * @param string $path The path to the JSON file.
     *
     * @return ConfigFile The configuration file.
     *
     * @throws FileNotFoundException If the JSON file was not found.
     * @throws InvalidConfigException If the JSON file is invalid.
     */
    public function readConfigFile($path)
    {
        $configFile = new ConfigFile($path);
        $config = $configFile->getConfig();

        $jsonData = $this->decodeFile($path);

        foreach ($jsonData as $key => $value) {
            $config->set($key, $value);
        }

        return $configFile;
    }

    private function decodeFile($path)
    {
        $decoder = new JsonDecoder();
        $schema = $decoder->decodeFile(realpath(__DIR__.'/../../../../res/schema/package-schema.json'));
        $configSchema = $schema->properties->config;

        if (!file_exists($path)) {
            throw new FileNotFoundException(sprintf(
                'The file %s does not exist.',
                $path
            ));
        }

        try {
            return $decoder->decodeFile($path, $configSchema);
        } catch (ValidationFailedException $e) {
            throw new InvalidConfigException(sprintf(
                "The configuration in %s is invalid:\n%s",
                $path,
                $e->getErrorsAsString()
            ), 0, $e);
        } catch (DecodingFailedException $e) {
            throw new InvalidConfigException(sprintf(
                "The configuration in %s could not be decoded:\n%s",
                $path,
                $e->getMessage()
            ), $e->getCode(), $e);
        }
    }
}