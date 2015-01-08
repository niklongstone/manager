<?php

/*
 * This file is part of the puli/repository-manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\RepositoryManager\Generator;

use InvalidArgumentException;
use Puli\RepositoryManager\Assert\Assert;
use Puli\RepositoryManager\Config\Config;
use Webmozart\PathUtil\Path;

/**
 * Generates the source code of the Puli factory.
 *
 * The Puli factory can later be used to easily instantiate the resource
 * repository and the resource discovery in both the user's web application and
 * the Puli CLI.
 *
 * The factory is generated by pasting "build recipes" for the repository and
 * the discovery into the "res/template/PuliFactory.tpl.php" template. The build
 * recipes contain the source code needed to construct the repository/discovery.
 *
 * The type of the generated repository/discovery is stored in the user's
 * configuration as string. This string is passed to the
 * {@link ProviderFactory}, which creates the matching
 * {@link BuildRecipeProvider} for the type name. The provider then returns the
 * build recipe for that service.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @see    BuildRecipe, BuildRecipeProvider
 */
class PuliFactoryGenerator
{
    /**
     * The name of the resource repository variable.
     */
    const REPO_VAR_NAME = '$repo';

    /**
     * The name of the resource discovery variable.
     */
    const DISCOVERY_VAR_NAME = '$discovery';

    /**
     * @var ProviderFactory
     */
    private $providerFactory;

    /**
     * Creates a new factory generator.
     *
     * @param ProviderFactory $providerFactory The factory used to create the
     *                                         recipe providers for the
     *                                         individual repository and
     *                                         discovery types.
     */
    public function __construct(ProviderFactory $providerFactory = null)
    {
        $this->providerFactory = $providerFactory ?: new ProviderFactory();
    }

    /**
     * Generates the factory class at its configured path.
     *
     * @param string $path      The path where to generate the factory class.
     * @param string $className The fully-qualified name of the generated class.
     * @param string $rootDir   The root directory of the project.
     * @param Config $config    The configuration storing the generator settings.
     *
     * @throws InvalidArgumentException If any of the arguments is invalid.
     */
    public function generateFactory($path, $className, $rootDir, Config $config)
    {
        Assert::string($path, 'The path to the generated factory file must be a string. Got: %2$s');
        Assert::notEmpty($path, 'The path to the generated factory file must not be empty.');
        Assert::true(Path::isAbsolute($path), sprintf('The path "%s" is not absolute.', $path));
        Assert::string($className, 'The class name of the generated factory must be a string. Got: %2$s');
        Assert::notEmpty($className, 'The class name of the generated factory must not be empty.');
        Assert::string($rootDir, 'The root directory must be a string. Got: %2$s');
        Assert::notEmpty($rootDir, 'The root directory must not be empty.');
        Assert::directory($rootDir, 'The root directory "%s" was expected to be a directory.');

        $outputDir = Path::getDirectory($path);

        $variables = $this->generateVariables(
            $className,
            $this->getRepositoryRecipe($outputDir, $rootDir, $config),
            $this->getDiscoveryRecipe($outputDir, $rootDir, $config)
        );

        $source = $this->generateSource($variables);

        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        file_put_contents($path, $source);
    }

    /**
     * Indents a block of source code for the given number of spaces.
     *
     * @param string $source     Some source code.
     * @param int    $nbOfSpaces The number of spaces to indent.
     *
     * @return string The indented source code.
     */
    protected function indent($source, $nbOfSpaces)
    {
        $prefix = str_repeat(' ', $nbOfSpaces);

        return $prefix.implode("\n".$prefix, explode("\n", $source));
    }

    /**
     * Generates the PHP source code using the given variables.
     *
     * @param array $variables The variables used in the generator template.
     *
     * @return string The PHP source code of the registry class.
     */
    private function generateSource(array $variables)
    {
        extract($variables);

        ob_start();

        require __DIR__.'/../../res/template/PuliFactory.tpl.php';

        return "<?php\n".ob_get_clean();
    }

    /**
     * Generates the variables used in the generator template.
     *
     * @param string      $className       The fully-qualified class name.
     * @param BuildRecipe $repoRecipe      The recipe for the repository.
     * @param BuildRecipe $discoveryRecipe The recipe for the discovery.
     *
     * @return array A mapping of variable names to values.
     */
    private function generateVariables($className, BuildRecipe $repoRecipe, BuildRecipe $discoveryRecipe)
    {
        $className = trim($className, '\\');
        $pos = strrpos($className, '\\');

        $variables = array();
        $variables['namespace'] = false !== $pos ? substr($className, 0, $pos) : '';
        $variables['shortClassName'] = false !== $pos ? substr($className, $pos + 1) : $className;

        $variables['imports'] = array_unique(array_merge(
            $repoRecipe->getImports(),
            $discoveryRecipe->getImports(),
            array(
                'Puli\Repository\Api\ResourceRepository',
                'Puli\Discovery\Api\ResourceDiscovery',
                'Puli\Factory\PuliFactory',
            )
        ));

        sort($variables['imports']);

        $variables['repoDeclarations'] = $repoRecipe->getVarDeclarations();
        $variables['repoVarName'] = self::REPO_VAR_NAME;

        $variables['discoveryDeclarations'] = $discoveryRecipe->getVarDeclarations();
        $variables['discoveryVarName'] = self::DISCOVERY_VAR_NAME;

        return $variables;
    }

    /**
     * Returns the recipe for the resource repository.
     *
     * @param string $outputDir The directory where the generated file is placed.
     * @param string $rootDir   The root directory of the project.
     * @param Config $config    The configuration.
     *
     * @return BuildRecipe The recipe.
     */
    private function getRepositoryRecipe($outputDir, $rootDir, Config $config)
    {
        $provider = $this->providerFactory->createRepositoryRecipeProvider($config->get(Config::REPOSITORY_TYPE));

        return $provider->getRecipe(
            self::REPO_VAR_NAME,
            $outputDir,
            $rootDir,
            $this->camelizeKeys($config->get(Config::REPOSITORY)),
            $this->providerFactory
        );
    }

    /**
     * Returns the recipe for the resource discovery.
     *
     * @param string $outputDir The directory where the generated file is placed.
     * @param string $rootDir   The root directory of the project.
     * @param Config $config    The configuration.
     *
     * @return BuildRecipe The recipe.
     */
    private function getDiscoveryRecipe($outputDir, $rootDir, Config $config)
    {
        $provider = $this->providerFactory->createDiscoveryRecipeProvider($config->get(Config::DISCOVERY_TYPE));

        return $provider->getRecipe(
            self::DISCOVERY_VAR_NAME,
            $outputDir,
            $rootDir,
            $this->camelizeKeys($config->get(Config::DISCOVERY)),
            $this->providerFactory
        );
    }

    /**
     * Recursively camelizes the keys of an array.
     *
     * @param array $array The array to process.
     *
     * @return array The input array with camelized keys.
     */
    private function camelizeKeys(array $array)
    {
        $camelized = array();

        foreach ($array as $key => $value) {
            $camelized[$this->camelize($key)] = is_array($value)
                ? $this->camelizeKeys($value)
                : $value;
        }

        return $camelized;
    }

    /**
     * Camelizes a string.
     *
     * @param string $string A string.
     *
     * @return string The camelized string.
     */
    private function camelize($string)
    {
        return preg_replace_callback('/\W+([a-z])/', function ($matches) {
            return strtoupper($matches[1]);
        }, $string);
    }
}
