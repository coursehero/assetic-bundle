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

        if ($asset instanceof AssetCollectionInterface) {
            foreach ($asset->all() as $i => $leaf) {
                $this->hashAsset($leaf, $hash);
            }
        } else{
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

        if (!isset($hashCache[$asset->getTargetPath()])){
            $sourcePath = $asset->getSourcePath();
            $sourceRoot = $asset->getSourceRoot();
            if (!($sourcePath && $sourceRoot && file_exists($sourceRoot . "/" . $sourcePath))){
                $hashCache[$asset->getTargetPath()] = hash('sha1', $asset->dump());
            } else{
                $hashCache[$asset->getTargetPath()] = hash_file('sha1', $sourceRoot . "/" . $sourcePath);
            }
        }
        $data = $hashCache[$asset->getTargetPath()];
        hash_update($hash, $data);
    }
}
