<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetInterface;
use Assetic\Asset\HttpAsset;
use Assetic\Filter\FilterInterface;
use Assetic\Filter\HashableInterface;

/**
 * Operates on CHAssetBags
 *
 * Concat, minify, and generate source maps from many JS assets
 *
 * This replaces the built in Uglify filter - it should no longer be used
 *
 * Some possible future improvements:
 * - Upgrade to uglify-es
 * - Allow for input sources to bring along their own source maps. Probably requires uglify-es
 * - If above works, should be able to remove the BundledWorker/Filter and rely on this instead for TypeScript source maps
 */
class SourceMapFilter implements FilterInterface, HashableInterface
{
    /** @var string */
    private $siteUrl;

    /** @var string */
    private $asseticWriteToDir;

    /** @var string */
    private $uglifyOpts;

    public function __construct(string $siteUrl, string $asseticWriteToDir, string $uglifyOpts = '')
    {
        $this->siteUrl = rtrim($siteUrl, '/');
        $this->asseticWriteToDir = rtrim($asseticWriteToDir, '/');
        $this->uglifyOpts = $uglifyOpts;
    }

    public function filterLoad(AssetInterface $assetBag)
    {
    }

    public function filterDump(AssetInterface $assetBag)
    {
        if (!($assetBag instanceof CHAssetBag)) {
            throw new \Exception('SourceMapFilter only works with CHAssetBag. Got ' . get_class($assetBag));
        }

        // loop through leaves and dump each asset
        $parts = [];
        foreach ($assetBag->getBag() as $asset) {
            $part = $asset->dump();
            $parts[] = $part;
        }

        // concat with uglifyjs, so the source maps are combined properly
        $tmpInputs = [];
        foreach ($parts as $i => $part) {
            // give the tmp file a meaningful name, so that uglifyjs output can be made sense of
            $filename = pathinfo($assetBag->getBag()[$i]->getSourcePath())['filename'];
            $tmpInput = tempnam(sys_get_temp_dir(), "smf-$filename-");
            file_put_contents($tmpInput, $part);
            $tmpInputs[] = $tmpInput;
        }

        $tmpOutput = tempnam(sys_get_temp_dir(), 'output') . '.js';

        $retArr = [];
        $retVal = -1;
        
        $targetPathForSourceMap = $assetBag->getAssetCollectionTargetPath() . '.map';
        $sourceMapURL = $this->siteUrl . '/sym-assets/' . $targetPathForSourceMap;
        
        $bin = '/usr/bin/uglifyjs';

        // this is uglify-es CLI ... image is still using uglify-js for now (note: should test well if upgrading)
        // $cmd = "$bin {$this->uglifyOpts} --source-map \"root='coursehero:///',includeSources,url=$sourceMapURL\" -o $tmpOutput " . implode(' ', $tmpInputs);
        
        // uglify-js
        $cmd = "$bin {$this->uglifyOpts} --source-map $tmpOutput.map --source-map-url $sourceMapURL --source-map-root 'coursehero:///' --source-map-include-sources -o $tmpOutput " . implode(' ', $tmpInputs);

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

        // translate source filenames
        // the 'sources' property is what dev tools (such as Chrome DevTools) display as the filename for the original source
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

    public function hash()
    {
        $data = get_object_vars($this);

        // asseticWriteToDir is different between build and webservers, so it must
        // not be used in generation of the filename hash
        unset($data['asseticWriteToDir']);

        $data['class'] = self::class;

        return md5(serialize($data));
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
