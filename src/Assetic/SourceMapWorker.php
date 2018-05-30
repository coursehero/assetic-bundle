<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\StringAsset;
use Assetic\Asset\FileAsset;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\Worker\WorkerInterface;

// TODO
// This isn't working. I think this needs to be moved to a filter - if a worker
// is using Uglify, that means that the webservers would need to have+use UglifyJS

class SourceMapWorker implements WorkerInterface
{
    public function process(AssetInterface $assetCollection, AssetFactory $factory)
    {
        // only process the collection, not each individual asset
        if (!($assetCollection instanceof AssetCollectionInterface)) {
            return;
        }

        // be sure to only process JS assets
        foreach ($assetCollection as $asset) {
            // asset collections don't have any source paths
            if ($asset->getSourcePath() && !preg_match('/.js$/', $asset->getSourcePath())) {
                return;
            }
        }

        if (empty($assetCollection->all())) {
            return;
        }

        // this worker should only apply when BundleAppFilter is not present
        $hasBundledAppFilter = false;
        foreach ($assetCollection->getFilters() as $filter) {
            if (get_class($filter) == BundledAppFilter::class) {
                $hasBundledAppFilter = true;
                break;
            }
        }
        
        if ($hasBundledAppFilter) {
            return;
        }

        // flatten should have gotten rid of all asset collections
        foreach ($assetCollection as $asset) {
            if ($asset instanceof AssetCollectionInterface) {
                throw new \Exception("unexpected asset collection");
            }
        }

        if (strpos($assetCollection->getTargetPath(), 'assetic/') === 0) {
            return;
        }

        // echo("========== SourceMapWorker\n");
        // echo("target path is " . $assetCollection->getTargetPath() . "\n");
        // foreach ($assetCollection as $asset) {
        //     echo($asset->getSourcePath() ."\n");
        // }

        // ----
        // get some Symfony params
        // global $kernel;
        // $siteUrl = rtrim($kernel->getContainer()->getParameter('site_url'), '/');
        // $rootDir = rtrim($kernel->getContainer()->getParameter('kernel.root_dir'), '/');
        // $asseticWriteToDir = "$rootDir/../../Control/sym-assets";

        // loop through leaves and dump each asset
        $parts = [];
        foreach ($assetCollection as $asset) {
            $part = $asset->dump();
            $parts[] = $part;
        }

        // concat with uglifyjs, so the source maps are combined properly
        $tmpInputs = [];
        foreach ($parts as $part) {
            $tmpInput = tempnam(sys_get_temp_dir(), 'input');
            file_put_contents($tmpInput, $part);
            $tmpInputs[] = $tmpInput;
        }

        $tmpOutput = tempnam(sys_get_temp_dir(), 'output') . '.js';
        // echo("create $tmpOutput\n");

        // $targetPathForSourceMap = $assetCollection->getTargetPath() . '.map';

        $retArr = [];
        $retVal = -1;
        
        // $sourceMappingURL = $siteUrl . '/sym-assets/' . $targetPathForSourceMap;

        // $mangle = true;
        // $compress = true;
        $mangle = false;
        $compress = false;
        $extraArgs = ($mangle ? '-m' : '') . ' ' . ($compress ? '-c' : ''); // -c unused=false ?
        
        $bin = '/usr/bin/uglifyjs';
        // this is uglify-es CLI ... image is still using uglify-js for now
        // $cmd = "$bin $extraArgs --source-map \"root='coursehero:///',includeSources\" -o $tmpOutput " . implode(' ', $tmpInputs);
        $cmd = "$bin $extraArgs --source-map $tmpOutput.map --source-map-root 'coursehero:///' --source-map-include-sources -o $tmpOutput " . implode(' ', $tmpInputs);

        exec($cmd, $retArr, $retVal);
        if ($retVal !== 0) {
            throw new \Exception(implode("\n", $retArr));
        }

        if (!file_exists($tmpOutput)) {
            throw new \RuntimeException('Error creating output file.');
        }

        if (!file_exists("$tmpOutput.map")) {
            throw new \RuntimeException('Error creating source map for output file.');
        }

        // remove the last line of the output JS - UglifyJS has no option to repress adding
        // the sourceMappingURL attirbute. Will set it after The FilehashCacheBustingWorker runs
        exec("head -n -1 $tmpOutput > $tmpOutput.0 ; mv $tmpOutput.0 $tmpOutput");

        // $result = file_get_contents($tmpOutput);

        // copy the source map to the sym-assets folder
        // $to = $asseticWriteToDir . '/' . $targetPathForSourceMap;

        // // useful for local, the folder "sym-assets/js" doesn't always exist
        // if (!is_dir(dirname($to))) {
        //     mkdir(dirname($to), 0777, true);
        // }

        // load the data and delete the line from the array
        // $lines = file('filename.txt');
        // $last = sizeof($lines) - 1 ;
        // unset($lines[$last]);

        // // write the new data to the file
        // $fp = fopen('filename.txt', 'w');
        // fwrite($fp, implode('', $lines));
        // fclose($fp);
        
        // translate source file names
        // the 'sources' property is what dev tools (such as Chrome DevTools) display as the file names for the original sources
        $sourceMap = json_decode(file_get_contents("$tmpOutput.map"), true);
        $sourceMap['sources'] = array_map(function ($asset) {
            if ($asset instanceof HttpAsset) {
                return 'cdn/' . $asset->getSourcePath();
            }

            $sourceFullPath = $this->getAbsoluteFilename($asset->getSourceRoot() . $asset->getSourcePath());
            return substr($sourceFullPath, strlen('/var/www/html/coursehero/src/'));
        }, $assetCollection->all());
        $sourceMap['sources'] = array_values($sourceMap['sources']);
        file_put_contents("$tmpOutput.map", json_encode($sourceMap));

        // remove temporary files
        // unlink($tmpOutput);
        // unlink("$tmpOutput.map");
        foreach ($tmpInputs as $tmpInput) {
            unlink($tmpInput);
        }

        $newAssets = [new FileAsset($tmpOutput)];
        
        // these won't do anything - only provide sensible debug output when using -vvv
        foreach ($assetCollection->all() as $asset) {
            $dummyAsset = new StringAsset('', $asset->getFilters(), $asset->getSourceRoot(), $asset->getSourcePath());
            $dummyAsset->setLastModified($asset->getLastModified());
            $newAssets[] = $dummyAsset;
        }

        $newAssetCollection = new AssetCollection(
            $newAssets,
            $assetCollection->getFilters(),
            $assetCollection->getSourceRoot(),
            $assetCollection->getVars()
        );

        $newAssetCollection->setTargetPath($assetCollection->getTargetPath());

        return $newAssetCollection;
    }

     // https://stackoverflow.com/a/39796579/2788187
    protected function getAbsoluteFilename(string $filename)
    {
        $path = [];
        foreach (explode('/', $filename) as $part) {
           // ignore parts that have no value
            if (empty($part) || $part === '.') {
                continue;
            }
       
            if ($part !== '..') {
               // cool, we found a new part
                array_push($path, $part);
            } else if (count($path) > 0) {
               // going back up? sure
                array_pop($path);
            } else {
               // now, here we don't like
                throw new \Exception('Climbing above the root is not permitted.');
            }
        }
       
        return "/" . join('/', $path);
    }
}
