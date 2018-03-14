<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
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
    public function __construct($separator = '-')
    {
        parent::__construct($separator);
    }

    /**
     * Get the sha1 hash of an asset or asset collection
     *
     * @param AssetInterface $asset
     * @param AssetFactory $factory
     * @return string
     */
    protected function getHash(AssetInterface $asset, AssetFactory $factory)
    {
        $hash = hash_init('sha1');

        // NOTE: we are processing all of these files twice ... once when this worker is called w/
        // I think we can get the desired behevior by making copying the login in CacheBustingWorker::getHash, but changing line 64 to hash
        // the file contents instead of the source path
        // TODO: add some logging and explore this further

        if ($asset instanceof AssetCollectionInterface) {
            foreach ($asset->all() as $i => $leaf) {
                $this->hashAsset($leaf, $hash);
            }
        } else {
            $this->hashAsset($asset, $hash);
        }

        return substr(hash_final($hash), 0, 7);
    }

    /**
     * Update a given hash with the sha1 hash of an individual asset
     *
     * @param AssetInterface $asset
     * @param $hash
     */
    protected function hashAsset(AssetInterface $asset, $hash)
    {
        static $hashCache = [];

        $data = null;
        if ($asset->getTargetPath()) {
            if (!isset($hashCache[$asset->getTargetPath()])) {
                $hashCache[$asset->getTargetPath()] = $this->getAssetHash($asset);
            }

            $data = $hashCache[$asset->getTargetPath()];
        } else {
            $data = $this->getAssetHash($asset);
        }

        hash_update($hash, $data);
    }

    protected function getAssetHash(AssetInterface $asset)
    {
        $sourcePath = $asset->getSourcePath();
        $sourceRoot = $asset->getSourceRoot();
        if ($sourcePath && $sourceRoot && file_exists($sourceRoot . "/" . $sourcePath)) {
            return hash_file('sha1', $sourceRoot . "/" . $sourcePath);
        }

        //if we can't find the file locally we have to dump it to hash the contents
        // because this could run during cache clear, get the unfiltered hash of contents
        $filters = $asset->getFilters();
        $asset->clearFilters();
        
        $hash =  hash('sha1', $asset->dump());

        foreach ($filters as $filter) {
            $asset->ensureFilter($filter);
        }

        return $hash;
    }
}
