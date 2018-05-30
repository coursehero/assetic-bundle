<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\HashableInterface;
use Assetic\Filter\FilterInterface;

/**
 * The only purpose of this filter is to provide a hash based on the source of CHAssetCollection
 */
class CHAssetCollectionFilter implements FilterInterface, HashableInterface
{
    public function filterLoad(AssetInterface $asset)
    {
    }

    public function filterDump(AssetInterface $asset)
    {
    }

    public function hash(): string
    {
        return CHAssetCollection::getCacheBustingHash();
    }
}
