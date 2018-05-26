<?php

namespace CourseHero\UtilsBundle\Assetic;

use Symfony\Bundle\AsseticBundle\Factory\AssetFactory;

class CHAssetFactory extends AssetFactory
{
    protected function createAssetCollection(array $assets = [], array $options = [])
    {
        return new CHAssetCollection($assets, [], null, isset($options['vars']) ? $options['vars'] : []);
    }
}
