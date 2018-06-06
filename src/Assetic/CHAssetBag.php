<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetInterface;
use Assetic\Asset\BaseAsset;
use Assetic\Filter\FilterInterface;

/**
 * See CHAssetFactory
 *
 * This is a custom AssetCollection that generates source maps. The default AssetCollection simply concats the input sources.
 * This custom AssetCollection passes the input sources to UglifyJS with source maps enabled.
 * This replaces the built in Uglify filter - it should no longer be used.
 * The FlattenWorker is required such that all AssetCollections, when dumped, are just a flat collection of assets.
 *
 * Some future possible improvements:
 * - Upgrade to uglify-es
 * - Allow for input sources to bring along their own source maps. Probably requires uglify-es
 * - If above works, should be able to remove the BundledWorker/Filter and rely on this instead for TypeScript source maps
 */


/**
 * TOOD explain
 */
class CHAssetBag extends BaseAsset
{
    private $bag;
    private $assetCollectionTargetPath;

    public function __construct(array $bag = [], $assetCollectionTargetPath = null, $filters = array(), $sourceRoot = null, $sourcePath = null)
    {
        $this->bag = $bag;
        $this->assetCollectionTargetPath = $assetCollectionTargetPath;

        parent::__construct($filters, $sourceRoot, $sourcePath);
    }

    public function add(AssetInterface $asset)
    {
        $this->bag[] = $asset;
    }

    public function getBag(): array
    {
        return $this->bag;
    }

    public function getAssetCollectionTargetPath()
    {
        return $this->assetCollectionTargetPath;
    }

    public function load(FilterInterface $additionalFilter = null)
    {
        $parts = [];
        foreach ($this->getBag() as $asset) {
            $asset->load($additionalFilter);
            $parts[] = $asset->getContent();
        }

        $this->doLoad(implode("\n", $parts));
    }

    /**
     * Returns the highest last-modified value of all assets in the current collection.
     *
     * @return integer|null A UNIX timestamp
     */
    public function getLastModified()
    {
        if (!count($this->bag)) {
            return;
        }

        $mtime = 0;
        foreach ($this->bag as $asset) {
            $assetMtime = $asset->getLastModified();
            if ($assetMtime > $mtime) {
                $mtime = $assetMtime;
            }
        }

        return $mtime;
    }
}
