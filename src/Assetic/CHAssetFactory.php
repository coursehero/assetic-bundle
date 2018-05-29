<?php

namespace CourseHero\UtilsBundle\Assetic;

use Symfony\Bundle\AsseticBundle\Factory\AssetFactory;
use Assetic\Asset\AssetCollection;

/**
 * This AssetFactory is used to provided a custom AssetCollection.
 * It is configured via this assetic bundle parameter: assetic.asset_factory.class
 */
class CHAssetFactory extends AssetFactory
{
    protected function createAssetCollection(array $assets = [], array $options = [])
    {
        if ($this->isDebug()) {
            // fall back to default behavior when in debug mode
            return new AssetCollection($assets, [], null, isset($options['vars']) ? $options['vars'] : []);
        }

        return new CHAssetCollection($assets, [], null, isset($options['vars']) ? $options['vars'] : []);
    }
}
