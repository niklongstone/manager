<?php

/*
 * This file is part of the puli/manager package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Manager\Tests\Factory\Generator;

use PHPUnit_Framework_TestCase;
use Puli\Manager\Api\Factory\Generator\GeneratorRegistry;
use Puli\Manager\Api\Php\Clazz;
use Puli\Manager\Api\Php\Method;
use Puli\Manager\Factory\Generator\BuildRecipe;
use Puli\Manager\Factory\Generator\DefaultGeneratorRegistry;
use Puli\Manager\Factory\Generator\ProviderFactory;
use Puli\Manager\Php\ClassWriter;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class AbstractGeneratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Clazz
     */
    protected $class;

    /**
     * @var Method
     */
    protected $method;

    /**
     * @var string
     */
    protected $tempDir;

    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var string
     */
    protected $outputDir;

    /**
     * @var string
     */
    protected $outputPath;

    /**
     * @var GeneratorRegistry
     */
    protected $registry;

    /**
     * @var ClassWriter
     */
    protected $writer;

    protected function setUp()
    {
        while (false === @mkdir($this->tempDir = sys_get_temp_dir().'/puli-repo-manager/AbstractGeneratorTest'.rand(10000, 99999), 0777, true)) {}

        $this->rootDir = $this->tempDir.'/root';
        $this->outputDir = $this->rootDir.'/out';
        $this->outputPath = $this->outputDir.'/generated.php';
        $this->registry = new DefaultGeneratorRegistry();
        $this->writer = new ClassWriter();

        $this->class = new Clazz('Puli\Test\GeneratedFactory');
        $this->class->setFilePath($this->outputPath);

        $this->method = new Method('createService');
        $this->class->addMethod($this->method);

        mkdir($this->rootDir);
        mkdir($this->outputDir);
    }

    protected function tearDown()
    {
        $filesystem = new Filesystem();
        $filesystem->remove($this->tempDir);
    }

    protected function putCode($path, Method $method)
    {
        $imports = 'use '.implode(";\nuse ", $method->getClass()->getImports()).";\n";

        file_put_contents($path, "<?php\nnamespace Puli\\Test;\n$imports\n".$method->getBody());

//        echo file_get_contents($path);
    }
}
