<?php

namespace CourseHero\AsseticBundle\Assetic;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\AssetReference;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\Worker\WorkerInterface;
use CourseHero\AsseticBundle\Assetic\BundledAppFilter;

class BundledAppWorker implements WorkerInterface
{
    public function process(AssetInterface $assetCollection, AssetFactory $factory)
    {
        // only process the collection, not each individual asset
        if (!($assetCollection instanceof AssetCollectionInterface)) {
            return;
        }
        
        // this worker only applies when BundleAppFilter is applied
        $hasBundledAppFilter = false;
        foreach ($assetCollection->getFilters() as $filter) {
            if (get_class($filter) == BundledAppFilter::class) {
                $hasBundledAppFilter = true;
                break;
            }
        }
        
        if (!$hasBundledAppFilter) {
            return;
        }
        
        // if an asset collection is using the bundled filter, there should be only ONE asset
        $numAssets = count($assetCollection->all());
        
        if ($numAssets > 1) {
            $assetNamesFormatted = join(', ', array_map(function ($asset) {
                return $asset->getSourcePath();
            }, $assetCollection->all()));
            throw new \Exception("The BundledAppFilter may only have one input, but multiple were given: $assetNamesFormatted");
        }

        if ($numAssets === 0) {
            throw new \Exception("The BundledAppFilter must have one input, but none given");
        }
    }
}
