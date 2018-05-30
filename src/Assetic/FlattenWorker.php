<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetCollection;
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

        // echo("collection getTargetPath {$assetCollection->getTargetPath()} \n");

        // be sure to only process JS assets
        foreach ($assetCollection as $asset) {
            // asset collections don't have any source paths
            if ($asset->getSourcePath() && !preg_match('/.js$/', $asset->getSourcePath())) {
                return;
            }
        }

        // flatten!
        $newAssets = [];
        $this->flatten($newAssets, $assetCollection);

        $newAssetCollection = new AssetCollection(
            $newAssets,
            $assetCollection->getFilters(),
            $assetCollection->getSourceRoot(),
            $assetCollection->getVars()
        );

        $newAssetCollection->setTargetPath($assetCollection->getTargetPath());

        return $newAssetCollection;
    }

    private function flatten(& $newAssets, $assetCollection)
    {
        foreach ($assetCollection->all() as $asset) {
            if ($asset instanceof AssetReference) {
                $getAsset = function () {
                    return $this->resolve();
                };
                $asset = $getAsset->call($asset);
            }

            if ($asset instanceof AssetCollection) {
                $this->flatten($newAssets, $asset);
            } else {
                $newAssets[] = $asset;
            }
        }
    }
}
