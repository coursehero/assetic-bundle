<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\StringAsset;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\Worker\WorkerInterface;

class SourceMappingURLWorker implements WorkerInterface
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

        // this worker should only apply when BundleAppFilter is not present
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

        if (strpos($assetCollection->getTargetPath(), 'assetic/') === 0) {
            return;
        }

        if (empty($assetCollection->all())) {
            return;
        }

        // ----
        // get some Symfony params
        global $kernel;
        $siteUrl = rtrim($kernel->getContainer()->getParameter('site_url'), '/');
        $rootDir = rtrim($kernel->getContainer()->getParameter('kernel.root_dir'), '/');
        $asseticWriteToDir = "$rootDir/../../Control/sym-assets";

        $targetPathForSourceMap = $assetCollection->getTargetPath() . '.map';

        // copy the source map to the sym-assets folder
        $to = $asseticWriteToDir . '/' . $targetPathForSourceMap;

        // useful for local, the folder "sym-assets/js" doesn't always exist
        if (!is_dir(dirname($to))) {
            mkdir(dirname($to), 0777, true);
        }

        $finalAsset = $assetCollection->all()[0];
        $pathOnDisk = $finalAsset->getSourceRoot() . '/' . $finalAsset->getSourcePath();
        rename("$pathOnDisk.map", $to);
        
        $sourceMappingURL = $siteUrl . '/sym-assets/' . $targetPathForSourceMap;
        $assetCollection->add(new StringAsset("//# sourceMappingURL=$sourceMappingURL"));
        
        return $assetCollection;
    }
}
