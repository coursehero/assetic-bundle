<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\AssetReference;
use Assetic\Filter\HashableInterface;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\Worker\CacheBustingWorker;

/**
 * Adds cache busting based on the hash of all asset contents
 */
class FilehashCacheBustingWorker extends CacheBustingWorker
{
    public function process(AssetInterface $asset, AssetFactory $factory)
    {
        if (!($asset instanceof AssetCollectionInterface)) {
            return;
        }

        return parent::process($asset, $factory);
    }

    protected function getHash(AssetInterface $assetCollection, AssetFactory $factory): string
    {
        $hash = hash_init('sha1');
        $content = $this->getUnfilteredAssetContent($assetCollection);
        hash_update($hash, $content);

        // Assetic generates a hash for the filters applied before workers, but not after
        // only applies to CHAssetBag
        foreach ($assetCollection as $asset) {
            if (!$asset instanceof CHAssetBag) {
                continue;
            }
            
            foreach ($asset->getFilters() as $filter) {
                $filterHash = $filter instanceof HashableInterface ? $filter->hash() : serialize($filter);
                hash_update($hash, $filterHash);
            }
        }

        return substr(hash_final($hash), 0, 7);
    }

    protected function getUnfilteredAssetContent(AssetCollectionInterface $assetCollection): string
    {
        $cloned = clone $assetCollection;
        $cloned->clearFilters();
        foreach ($cloned as $asset) {
            $asset->clearFilters();
        }
        $cloned->load();
        return $cloned->getContent();
    }
}
