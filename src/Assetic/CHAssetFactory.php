<?php

namespace CourseHero\UtilsBundle\Assetic;

use Symfony\Bundle\AsseticBundle\Factory\AssetFactory;
use Assetic\Asset\AssetCollection;

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
