<?php

declare(strict_types=1);

namespace Goat\Mapper\Cache\GeneratorStrategy;

use Goat\Mapper\Cache\Writer\FileWriter;

/**
 * Generator strategy that produces the code and evaluates it at runtime
 */
class EvaluatingGeneratorStrategy implements GeneratorStrategy
{
    private bool $canEval = true;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->canEval = !\ini_get('suhosin.executor.disable_eval');
    }

    /**
     * Evaluates the generated code before returning it
     *
     * {@inheritDoc}
     */
    public function generate(string $generatedClassName, string $generatedCode): void
    {
        // @codeCoverageIgnoreStart
        if (!$this->canEval) {
            $filename = __DIR__ . '/EvaluatingGeneratorStrategy.php.tmp';
            FileWriter::writeFile($filename, $generatedCode);

            require $filename;
            \unlink($filename);

            return;
        }
        // @codeCoverageIgnoreEnd

        eval($generatedCode);
    }
}
