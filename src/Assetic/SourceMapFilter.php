<?php

namespace CourseHero\AsseticBundle\Assetic;

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
 * - Allow for input sources to bring along their own source maps.
 * - If above works, should be able to remove the BundledWorker/Filter and rely on this instead for TypeScript source maps
 */
class SourceMapFilter implements FilterInterface, HashableInterface
{
    /** @var string */
    private $siteUrl;

    /** @var string */
    private $sourceMapRoot;

    /** @var string */
    private $asseticWriteToDir;
    
    /** @var string */
    private $sourceMapSourcePathTrim;

    /** @var string */
    private $uglifyBin;

    /** @var string */
    private $uglifyOpts;

    public function __construct(array $options)
    {
        $this->siteUrl = rtrim($options['site_url'], '/');
        $this->sourceMapRoot = $options['source_map_root'] ?? 'sources:///';
        $this->asseticWriteToDir = rtrim($options['assetic_write_to'], '/');
        $this->sourceMapSourcePathTrim = $options['source_map_source_path_trim'] ?? '';
        $this->uglifyBin = $options['uglify_bin'] ?? 'uglifyjs'; // must be uglify-es
        $this->uglifyOpts = $options['uglify_opts'] ?? '';
    }

    public function filterLoad(AssetInterface $assetBag)
    {
    }

    public function filterDump(AssetInterface $assetBag)
    {
        if (!($assetBag instanceof CHAssetBag)) {
            throw new \Exception('SourceMapFilter only works with CHAssetBag. Got ' . get_class($assetBag));
        }

        $tmpOutput = tempnam(sys_get_temp_dir(), 'output') . '.js';
        $tmpInputs = $this->getUglifyJsInputs($assetBag);

        try {
            return $this->doFilterDump($assetBag, $tmpOutput, $tmpInputs);
        } finally {
            unlink($tmpOutput);
            unlink("$tmpOutput.map");
            foreach ($tmpInputs as $tmpInput) {
                unlink($tmpInput);
            }
        }
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

    protected function getUglifyJsInputs(CHAssetBag $assetBag)
    {
        $tmpInputs = [];
        foreach ($assetBag->getBag() as $asset) {
            $part = $asset->dump();
            
            // give the tmp file a meaningful name, so that uglifyjs output can be made sense of
            $filename = pathinfo($asset->getSourcePath())['filename'];
            $tmpInput = tempnam(sys_get_temp_dir(), "smf-$filename-");
            
            file_put_contents($tmpInput, $part);
            $tmpInputs[] = $tmpInput;
        }

        return $tmpInputs;
    }

    protected function doFilterDump(CHAssetBag $assetBag, string $tmpOutput, array $tmpInputs)
    {
        $assetCollectionTargetPath = $assetBag->getAssetCollectionTargetPath();
        $targetPathForSourceMap = $assetCollectionTargetPath . '.map';
        $sourceMapURL = "{$this->siteUrl}/$targetPathForSourceMap";

        $cmd = "{$this->uglifyBin} {$this->uglifyOpts} --source-map \"root='{$this->sourceMapRoot}',includeSources,url=$sourceMapURL,filename='$assetCollectionTargetPath'\" -o $tmpOutput " . implode(' ', $tmpInputs);

        $retArr = [];
        $retVal = -1;
        exec($cmd, $retArr, $retVal);
        if ($retVal !== 0) {
            throw new \Exception(implode("\n", $retArr));
        }

        if (!file_exists($tmpOutput)) {
            throw new \RuntimeException('Error creating output file.');
        }
        $minifiedCode = file_get_contents($tmpOutput);

        if (!file_exists("$tmpOutput.map")) {
            throw new \RuntimeException('Error creating source map for output file.');
        }
        $sourceMap = json_decode(file_get_contents("$tmpOutput.map"), true);

        // translate source filenames
        // the 'sources' property is what dev tools (such as Chrome DevTools) display as the filename for the original source
        $sourceMap['sources'] = array_map(function ($asset) {
            if ($asset instanceof HttpAsset) {
                return 'cdn/' . $asset->getSourcePath();
            }
            
            // remove relative path elements
            $sourceFullPath = $this->getAbsoluteFilename($asset->getSourceRoot() . '/' . $asset->getSourcePath());
            
            // remove the first part of the path - what's left should be relative to the root project directory
            $sourceFullPath = preg_replace("#^{$this->sourceMapSourcePathTrim}#", '', $sourceFullPath);
            
            return $sourceFullPath;
        }, $assetBag->getBag());
        $sourceMap['sources'] = array_values($sourceMap['sources']);

        // save the source map to the sym-assets folder
        $to = $this->asseticWriteToDir . '/' . $targetPathForSourceMap;
        if (!file_put_contents($to, json_encode($sourceMap))) {
            $error = error_get_last();
            throw new \Exception('issue copying source map ' . $error['type'] . ' ' . $error['message']);
        }
        
        $assetBag->setContent($minifiedCode);
        return $assetBag;
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
            } elseif (count($path) > 0) {
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
