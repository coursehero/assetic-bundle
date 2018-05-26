<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\AssetReference;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\Worker\WorkerInterface;

class FlattenWorker implements WorkerInterface
{
    public function process(AssetInterface $assetCollection, AssetFactory $factory)
    {
        // only process the collection, not each individual asset
        if (!($assetCollection instanceof AssetCollectionInterface)) {
            return;
        }

        // be sure to only process JS assets
        foreach ($assetCollection as $asset) {
            // asset collections don't have any source paths
            if ($asset->getSourcePath() && !preg_match('/.js$/', $asset->getSourcePath())) {
                return;
            }
        }

        // flatten!
        $allAssets = $assetCollection->all();
        foreach ($allAssets as $asset) {
            $assetCollection->removeLeaf($asset);
        }
        foreach ($allAssets as $asset) {
            if ($asset instanceof AssetReference) {
                $getAsset = function () {
                    return $this->resolve();
                };
                $asset = $getAsset->call($asset);
            }

            if ($asset instanceof AssetCollectionInterface) {
                $this->flatten($assetCollection, $asset);
            } else {
                $assetCollection->add($asset);
            }
        }
    }

    private function flatten($rootAssetCollection, $assetCollection)
    {
        foreach ($assetCollection as $asset) {
            if ($asset instanceof AssetCollection) {
                $this->flatten($rootAssetCollection, $asset);
            } else {
                $rootAssetCollection->add($asset);
            }
        }
    }
}
