<?php

namespace CourseHero\AsseticBundle\Assetic;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\AssetReference;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\Worker\CacheBustingWorker;
use Assetic\Filter\HashableInterface;
use CourseHero\AsseticBundle\Utils;

/**
 * Adds cache busting based on the hash of all asset contents
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

    protected function getHash(AssetInterface $assetCollection, AssetFactory $factory): string
    {
        $hash = hash_init('sha1');
        $content = $this->getUnfilteredAssetContent($assetCollection);
        hash_update($hash, $content);

        foreach ($assetCollection as $asset) {
            $ext = pathinfo($asset->getSourcePath(), PATHINFO_EXTENSION);
            if ($ext === 'scss') {
                $this->hashScssContent($hash, $asset);
            }
        }

        // Assetic generates a hash for the filters applied before workers, but not after
        // only applies to CHAssetBag
        foreach ($assetCollection as $asset) {
            if (!$asset instanceof CHAssetBag) {
                continue;
            }
            
            foreach ($asset->getFilters() as $filter) {
                $filterHash = $filter instanceof HashableInterface ? $filter->hash() : serialize($filter);
                hash_update($hash, $filterHash);
            }
        }

        return substr(hash_final($hash), 0, 7);
    }

    protected function getUnfilteredAssetContent(AssetCollectionInterface $assetCollection): string
    {
        $cloned = clone $assetCollection;
        $cloned->clearFilters();
        foreach ($cloned as $asset) {
            $asset->clearFilters();
        }
        $cloned->load();
        return $cloned->getContent();
    }

    protected function hashScssContent(&$hash, AssetInterface $asset)
    {
        $assetPath = $asset->getSourceRoot() . '/' . $asset->getSourcePath();
        $content = file_get_contents($assetPath);
        $statements = preg_split("/[;}]/", $content);
        $importStatements = array_filter($statements, function($statement) {
            return preg_match("/\s*@import/", $statement);
        });

        foreach ($importStatements as $importStatement) {
            $resolvedPaths = Utils\resolveScssImport([$asset->getSourceDirectory()], $importStatement);
            foreach ($resolvedPaths as $import => $paths) {
                $found = null;
                foreach ($paths as $path) {
                    if (file_exists($path)) {
                        $found = $path;
                        break;
                    }
                }

                if (!$found) {
                    throw new \Error("Could not resolve import for $import (found in $assetPath)");
                }

                hash_update($hash, file_get_contents($found));
            }
        }
    }
}
