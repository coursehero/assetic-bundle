<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\Worker\WorkerInterface;
use CourseHero\UtilsBundle\Assetic\BundledAppFilter;

class BundledAppWorker implements WorkerInterface
{
    public function process(AssetInterface $asset, AssetFactory $factory)
    {
        // only process the collection, not each individual asset
        if (!($asset instanceof AssetCollectionInterface)) {
            return;
        }

        // if an asset collection is using the bundled filter, there should be only ONE asset
        $numAssets = $asset instanceof AssetCollectionInterface ? count($asset->all()) : 1;
        $hasBundledAppFilter = false;
        foreach ($asset->getFilters() as $filter) {
            if (get_class($filter) == BundledAppFilter::class) {
                $hasBundledAppFilter = true;
                break;
            }
        }

        $shouldThrow = $hasBundledAppFilter && $numAssets > 1;
        if ($shouldThrow) {
            $assetNamesFormatted = join(', ', array_map(function ($a) {
                return $a->getSourcePath();
            }, $asset->all()));
            throw new \Exception("The BundledAppFilter may only have one input, but multiple were given: $assetNamesFormatted");
        }
    }
}
