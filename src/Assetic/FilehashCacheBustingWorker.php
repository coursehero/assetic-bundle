<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\AssetReference;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\Worker\CacheBustingWorker;

/**
 * Adds cache busting based on the hash of all asset contents
 *
 *
 * TODO: Move this to open source package
 * @package CourseHero\AsseticFilehashBuster
 * @author Jason Wentworth <wentwj@gmail.com>
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

    protected function getHash(AssetInterface $asset, AssetFactory $factory): string
    {
        $hash = hash_init('sha1');
        $content = $this->getAssetContent($asset);
        hash_update($hash, $content);
        return substr(hash_final($hash), 0, 7);
    }

    protected function getAssetContent(AssetInterface $asset): string
    {
        // grab the actual asset, if this is a reference
        if ($asset instanceof AssetReference) {
            // call the private method "resolve"
            $getAsset = function () {
                return $this->resolve();
            };
            $asset = $getAsset->call($asset);
        }

        if (empty($asset->getContent())) {
            $asset->load();
        }

        return $asset->getContent();
    }
}
