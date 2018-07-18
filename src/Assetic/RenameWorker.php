<?php

namespace CourseHero\AsseticBundle\Assetic;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\Worker\WorkerInterface;

// TODO: make configurable

class RenameWorker implements WorkerInterface
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

        foreach ($assetCollection->all() as $asset) {
            $matches = [];
            if (preg_match("/js\/dist\/(.*)\\.js$/i", $asset->getSourcePath(), $matches)) {
                $appName = str_replace('/', '-', $matches[1]);
                break;
            }
        }

        $appName = $appName ?? 'bundle';

        if (strpos($assetCollection->getTargetPath(), 'js/' . $appName) === false) {
            $newTargetPath = str_replace('js/', "js/$appName-", $assetCollection->getTargetPath());
            
            // this target path is just part of the end result
            // the second hash (the cache busting part of the file name) is applied later by the cache busting worker
            $assetCollection->setTargetPath($newTargetPath);
        }
    }
}
