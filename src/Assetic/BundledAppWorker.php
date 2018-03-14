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

        if ($hasBundledAppFilter && $numAssets > 1) {
            $assetNamesFormatted = join(', ', array_map(function ($a) {
                return $a->getSourcePath();
            }, $asset->all()));
            throw new \Exception("The BundledAppFilter may only have one input, but multiple were given: $assetNamesFormatted");
        }

        if ($hasBundledAppFilter && $numAssets === 0) {
            throw new \Exception("The BundledAppFilter must have one input, but none given");
        }

        if ($hasBundledAppFilter && $numAssets === 1) {
            // try to maintain app name
            // example input source path: ../../js/dist/proco/annotations/app.js
            // example initial target path: js/bcdd303.js (this hash is the assetic configuration hash, not the content hash)
            // goal: target path at js/proco-annotations-app-bcdd303.js
            $matches = [];
            if (preg_match("/js\/dist\/(.*)\\.js/i", $asset->all()[0]->getSourcePath(), $matches)) {
                $appName = str_replace('/', '-', $matches[1]);
            } else {
                $appName = 'app';
            }

            if (strpos($asset->getTargetPath(), 'js/' . $appName) === false) {
                $newTargetPath = str_replace('js/', 'js/' . $appName . '-', $asset->getTargetPath());
                
                // this target path is just part of the end result
                // the second hash (the cache busting part of the file name) is applied later by the cache busting worker
                $asset->setTargetPath($newTargetPath);
            }
        }
    }
}
