<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;
use Assetic\Filter\UglifyJs2Filter;
use CourseHero\UtilsBundle\Assetic\BundledAppFilter;

class CHUglifyJs2Filter extends UglifyJs2Filter
{
    public function filterDump(AssetInterface $asset)
    {
        $filters = $asset->getFilters();
        foreach ($filters as $filter) {
            if (get_class($filter) == BundledAppFilter::class) {
                return; // do not minify
            }
        }

        parent::filterDump($asset);
    }
}
