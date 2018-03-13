<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\Worker\WorkerInterface;
use CourseHero\UtilsBundle\Assetic\BundledAppFilter;

class BundledAppWorker implements WorkerInterface
{
    public function process(AssetInterface $asset, AssetFactory $factory)
    {
        //disable
        return;

        if (!$factory->getAssetManager() || !$factory->getFilterManager() || !$factory->getFilterManager()->has('bundled')) {
            return;
        }

        if ($asset instanceof AssetCollectionInterface) {
            return;
        }

        $foundBundledFilter = false;
        foreach ($asset->getFilters() as $filter) {
            if (get_class($filter) === BundledAppFilter::class) {
                $foundBundledFilter = true;
                break;
            }
        }

        if (!$foundBundledFilter) {
            return;
        }

        echo("got one!\n");
        echo(count($factory->getAssetManager()->getNames()) . "\n");
        echo($asset->getTargetPath() . "\n");


        return;
        // WHY DOES THIS SEGFAULT?!

        if ($asset instanceof AssetCollectionInterface) {
            foreach ($asset->all() as $i => $leaf) {
                $this->hashAsset($leaf, $hash);
            }
        } else {
            $this->hashAsset($asset, $hash);
        }


        $names = $factory->getAssetManager()->getNames();
        // foreach ($names as $assetName) {
        for ($i = 0; $i < count($names); $i++) {
            $assetName = $names[$i];
            echo("1\n");
            $factory;
            echo("2\n");
            $factory->getAssetManager();
            echo("3\n");
            echo("$assetName\n");
            echo("4\n");
            if ($factory->getAssetManager()->has($assetName)) {
                echo("5\n");
                $a = $factory->getAssetManager()->get($assetName);
                echo("6\n");
            }
            // if ($a) {
                // $filters = $a->getFilters();
                
            // }

            // $hasBundledFilter = false;
            // foreach ($filters as $filter) {
            //     $hasBundledFilter = get_class($filter) === BundledAppFilter::class;
            // }

            // if ($hasBundledFilter) {
            //     echo("got something!\n");
            //     echo($assetName . "\n");
            //     echo("\n");
            // }

            // echo($assetName . "\n");
            // echo(join(', ', $a->getFilters()) . "\n");
        }

        if (count($factory->getAssetManager()->getNames()) > 1) {
            // throw new \Exception('the bundled filter can only have one asset! found: ' . join(', ', $factory->getAssetManager()->getNames()));
        }

        $targetPath = $factory->getAssetManager()->get($factory->getAssetManager()->getNames()[0])->getTargetPath();
        var_dump($targetPath);
    }
}
