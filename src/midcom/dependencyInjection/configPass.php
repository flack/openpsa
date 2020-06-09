<?php
namespace midcom\dependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use midcom_config;

abstract class configPass implements CompilerPassInterface
{
    /**
     * @var midcom_config
     */
    protected $config;

    public function __construct(midcom_config $config)
    {
        $this->config = $config;
    }
}