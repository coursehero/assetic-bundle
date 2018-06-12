<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetInterface;
use Assetic\Asset\BaseAsset;
use Assetic\Filter\FilterInterface;

/**
 * This is like an AssetCollection, but it is treated like an Asset. AssetCollections cannot have filters applied to them,
 * and always dumbly concat its assets together. CHAssetBag allows for applying filters that combines multiple assets in complex ways
 * (for example, SourceMapFilter). CHAssetBags are created by FlattenWorker, which groups an AssetCollection's assets into a single CHAssetBag,
 * and applies a filter based on provided rules.
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

        return max(array_map(function ($asset) {
            return $asset->getLastModified();
        }, $this->bag));
    }
}
