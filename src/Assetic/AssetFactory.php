<?php
namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Factory\LazyAssetManager;
use Symfony\Bundle\AsseticBundle\Factory\AssetFactory as BaseAssetFactory;
use Assetic\Factory\Worker\CacheBustingWorker;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class AssetFactory extends BaseAssetFactory
{
    public function __construct(KernelInterface $kernel, ContainerInterface $container, ParameterBagInterface $parameterBag, $baseDir, $debug = false)
    {
        parent::__construct($kernel, $container, $parameterBag, $baseDir, $debug);
        // Add CacheBustingWorker
        $this->addWorker(new CacheBustingWorker(new LazyAssetManager($this)));
    }
}