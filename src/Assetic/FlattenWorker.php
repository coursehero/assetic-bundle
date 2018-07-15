<?php

namespace CourseHero\AsseticBundle\Assetic;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\AssetReference;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\Worker\WorkerInterface;

/**
 * Transforms an AssetCollection's assets into a CHAssetBag with applied filters based on provided rules
 * If no rules apply, this worker will not modify the AssetCollection
 */
class FlattenWorker implements WorkerInterface
{
    /** @var array [ext: string, class: string, args: array] */
    private $filterRules;

    public function __construct(array $filterRules)
    {
        $this->filterRules = $filterRules;
    }

    public function process(AssetInterface $assetCollection, AssetFactory $factory)
    {
        // only process the collection, not each individual asset
        if (!($assetCollection instanceof AssetCollectionInterface)) {
            return;
        }

        if (empty($assetCollection->all())) {
            return;
        }

        // for now, skip the "bundled" apps.
        // TODO: Look into removing this guard, and having all source maps flow through the AssetBag / SourceMapFilter
        $hasBundledAppFilter = false;
        foreach ($assetCollection->getFilters() as $filter) {
            if (get_class($filter) == BundledAppFilter::class) {
                $hasBundledAppFilter = true;
                break;
            }
        }
        if ($hasBundledAppFilter) {
            return;
        }

        $newAssets = [];
        $this->flatten($newAssets, $assetCollection);

        $applicableFilterRules = array_filter($this->filterRules, function ($filterRule) use ($newAssets) {
            foreach ($newAssets as $asset) {
                if (!preg_match($filterRule['match'], $asset->getSourcePath())) {
                    return false;
                }
            }

            return true;
        });

        if (empty($applicableFilterRules)) {
            return;
        }

        $assetBag = new CHAssetBag(
            $newAssets,
            $assetCollection->getTargetPath(),
            $assetCollection->getFilters(),
            $assetCollection->getSourceRoot(),
            null,
            $assetCollection->getVars()
        );

        foreach ($applicableFilterRules as $filterRule) {
            $reflection = new \ReflectionClass($filterRule['class']);
            $filter = $reflection->newInstanceArgs($filterRule['args']);
            $assetBag->ensureFilter($filter);
        }

        foreach ($assetCollection->all() as $asset) {
            $assetCollection->removeLeaf($asset);
        }
        $assetCollection->add($assetBag);
        return $assetCollection;
    }

    private function flatten(array & $newAssets, AssetCollectionInterface $assetCollection)
    {
        foreach ($assetCollection as $asset) {
            if ($asset instanceof AssetReference) {
                // call the private method "resolve"
                $getAsset = function () {
                    return $this->resolve();
                };
                $asset = $getAsset->call($asset);
            }

            if ($asset instanceof AssetCollectionInterface) {
                $this->flatten($newAssets, $asset);
            } elseif ($asset instanceof CHAssetBag) {
                foreach ($asset->getBag() as $baggedAsset) {
                    $newAssets[] = $baggedAsset;
                }
            } else {
                $newAssets[] = $asset;
            }
        }
    }
}
