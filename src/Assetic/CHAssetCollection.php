<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\HttpAsset;
use Assetic\Filter\FilterInterface;

class CHAssetCollection extends AssetCollection
{
    public static function getCacheBustingHash()
    {
        static $sourceHash = '';
        
        if ($sourceHash === '') {
            $class = new \ReflectionClass(self::class);
            $fileName = $class->getFileName();
            $classSource = file_get_contents($fileName);
            $sourceHash = crc32($classSource);
        }

        return $sourceHash;
    }

    public function dump(FilterInterface $additionalFilter = null)
    {
        // echo("========dump==========\n");
        // echo(count($this->all()));

        // be sure to only process JS assets
        foreach ($this as $singleAsset) {
            // asset collections don't have any source paths
            if ($singleAsset->getSourcePath() && !preg_match('/.js$/', $singleAsset->getSourcePath())) {
                return parent::dump($additionalFilter);
            }
        }

        $hasBundledAppFilter = false;
        foreach ($this->getFilters() as $filter) {
            if (get_class($filter) == BundledAppFilter::class) {
                $hasBundledAppFilter = true;
                break;
            }
        }

        if ($hasBundledAppFilter) {
            return parent::dump($additionalFilter);
        }

        // if the first call in the stack is the only call to CHAssetCollection::dump,
        // that means this is the _root_ asset collection
        // $backtrace = debug_backtrace(2);
        // $isRootAssetCollection = count(
        //     array_filter($backtrace, function($frame) {
        //         return $frame['class'] == self::class && $frame['function'] === 'dump';
        //     })
        // ) === 1;
        // echo("isRootAssetCollection: $isRootAssetCollection\n");

        // foreach ($this as $asset) {
        //     echo('asset: '.$asset->getSourcePath() . "\n");
        // }

        if (empty($this->all())) {
            return parent::dump($additionalFilter);
        }

        // loop through leaves and dump each asset
        $parts = [];
        foreach ($this as $asset) {
            $part = $asset->dump($additionalFilter);
            $parts[] = $part;
        }

        // concat with uglifyjs, so the source maps are combined properly
        $tmpInputs = [];
        foreach ($parts as $part) {
            $tmpInput = tempnam(sys_get_temp_dir(), 'input');
            file_put_contents($tmpInput, $part);
            $tmpInputs[] = $tmpInput;
        }

        $tmpOutput = tempnam(sys_get_temp_dir(), 'output');

        $targetPathForSourceMap = $this->getTargetPath() . '.map';

        $retArr = [];
        $retVal = -1;
        
        $host = 'https://coursehero.local';
        $sourceMappingURL = $host . '/sym-assets/' . $targetPathForSourceMap;

        $mangle = true;
        $compress = true;
        $extraArgs = ($mangle ? '-m' : '') . ' ' . ($compress ? '-c' : ''); // -c unused=false ?
        
        $bin = '/usr/bin/uglifyjs';
        // this is uglify-es CLI ... image is still using uglify-js for now
        // $cmd = "$bin $extraArgs --source-map \"root='coursehero:///',includeSources,url='$sourceMappingURL'\" -o $tmpOutput " . implode(' ', $tmpInputs);
        $cmd = "$bin $extraArgs --source-map $tmpOutput.map --source-map-root 'coursehero:///' --source-map-include-sources --source-map-url '$sourceMappingURL' -o $tmpOutput " . implode(' ', $tmpInputs);

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

        $result = file_get_contents($tmpOutput);

        // copy the source map to the sym-assets folder
        $asseticWriteToDir = '/var/www/html/coursehero/src/Control/sym-assets';
        $to = $asseticWriteToDir . '/' . $targetPathForSourceMap;

        // useful for local, the folder "sym-assets/js" doesn't always exist
        if (!is_dir(dirname($to))) {
            mkdir(dirname($to), 0777, true);
        }
        
        // translate source file names
        $sourceMap = json_decode(file_get_contents("$tmpOutput.map"), true);
        $sourceMap['sources'] = array_map(function ($asset) {
            if ($asset instanceof HttpAsset) {
                return 'cdn/' . $asset->getSourcePath();
            }

            $sourceFullPath = $this->getAbsoluteFilename($asset->getSourceRoot() . $asset->getSourcePath());
            return substr($sourceFullPath, strlen('/var/www/html/coursehero/src/'));
        }, $this->all());
        $sourceMap['sources'] = array_values($sourceMap['sources']);
        file_put_contents($to, json_encode($sourceMap));

        unlink($tmpOutput);
        unlink("$tmpOutput.map");
        foreach ($tmpInputs as $tmpInput) {
            unlink($tmpInput);
        }
        
        return $result;
    }

    // https://stackoverflow.com/a/39796579/2788187
    protected function getAbsoluteFilename($filename)
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
