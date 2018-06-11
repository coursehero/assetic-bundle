<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetInterface;
use Assetic\Asset\HttpAsset;
use Assetic\Filter\FilterInterface;

class SourceMapFilter implements FilterInterface
{
    /** @var string */
    private $siteUrl;

    /** @var string */
    private $asseticWriteToDir;

    public function __construct(string $siteUrl, string $asseticWriteToDir)
    {
        $this->siteUrl = rtrim($siteUrl, '/');
        $this->asseticWriteToDir = rtrim($asseticWriteToDir, '/');
    }

    public function filterLoad(AssetInterface $assetBag)
    {
    }

    public function filterDump(AssetInterface $assetBag)
    {
        if (!($assetBag instanceof CHAssetBag)) {
            throw new \Exception('SourceMapFilter only works with CHAssetBag. Got ' . get_class($asset));
        }

        // loop through leaves and dump each asset
        $parts = [];
        foreach ($assetBag->getBag() as $asset) {
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

        $retArr = [];
        $retVal = -1;
        
        $mangle = true;
        $compress = true;
        $extraArgs = ($mangle ? '-m' : '') . ' ' . ($compress ? '-c unused=false' : ''); // can't rely on what uglify thinks is 'unusued' code truly being unused

        $targetPathForSourceMap = $assetBag->getAssetCollectionTargetPath() . '.map';
        $sourceMapURL = $this->siteUrl . '/sym-assets/' . $targetPathForSourceMap;
        
        $bin = '/usr/bin/uglifyjs';
        // this is uglify-es CLI ... image is still using uglify-js for now (note: should test well if upgrading)
        // $cmd = "$bin $extraArgs --source-map \"root='coursehero:///',includeSources,url=$sourceMapURL\" -o $tmpOutput " . implode(' ', $tmpInputs);
        
        // uglify-js
        $cmd = "$bin $extraArgs --source-map $tmpOutput.map --source-map-url $sourceMapURL --source-map-root 'coursehero:///' --source-map-include-sources -o $tmpOutput " . implode(' ', $tmpInputs);

        exec($cmd, $retArr, $retVal);
        if ($retVal !== 0) {
            throw new \Exception(implode("\n", $retArr));
        }

        if (!file_exists($tmpOutput)) {
            $this->cleanUp($tmpOutput, $tmpInputs);
            throw new \RuntimeException('Error creating output file.');
        }
        $minifiedCode = file_get_contents($tmpOutput);

        if (!file_exists("$tmpOutput.map")) {
            $this->cleanUp($tmpOutput, $tmpInputs);
            throw new \RuntimeException('Error creating source map for output file.');
        }
        $sourceMap = json_decode(file_get_contents("$tmpOutput.map"), true);

        // translate source file names
        // the 'sources' property is what dev tools (such as Chrome DevTools) display as the file names for the original sources
        $sourceMap['sources'] = array_map(function ($asset) {
            if ($asset instanceof HttpAsset) {
                return 'cdn/' . $asset->getSourcePath();
            }
            
            $sourceFullPath = $this->getAbsoluteFilename($asset->getSourceRoot() . $asset->getSourcePath());
            return substr($sourceFullPath, strlen('/var/www/html/coursehero/src/'));
        }, $assetBag->getBag());
        $sourceMap['sources'] = array_values($sourceMap['sources']);

        // save the source map to the sym-assets folder
        $to = $this->asseticWriteToDir . '/' . $targetPathForSourceMap;
        if (!file_put_contents($to, json_encode($sourceMap))) {
            $errors = error_get_last();
            $this->cleanUp($tmpOutput, $tmpInputs);
            throw new \Exception('issue copying source map ' . $errors['type'] . ' ' . $errors['message']);
        }

        $this->cleanUp($tmpOutput, $tmpInputs);
        
        $assetBag->setContent($minifiedCode);
        return $assetBag;
    }

    protected function cleanUp(string $tmpOutput, array $tmpInputs)
    {
        unlink($tmpOutput);
        unlink("$tmpOutput.map");
        foreach ($tmpInputs as $tmpInput) {
            unlink($tmpInput);
        }
    }

    // https://stackoverflow.com/a/39796579/2788187
    protected function getAbsoluteFilename(string $filename): string
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
