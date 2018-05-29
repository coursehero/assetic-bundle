<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;

class BundledAppFilter implements FilterInterface
{
    /** @var string */
    private $host;

    /** @var string */
    private $asseticWriteToDir;

    public function __construct(string $host, string $asseticWriteToDir)
    {
        $this->host = rtrim($host, '/');
        $this->asseticWriteToDir = rtrim($asseticWriteToDir, '/');
    }

    /**
     * @inheritdoc
     */
    public function filterLoad(AssetInterface $asset)
    {
    }

    /**
     * @inheritdoc
     *
     * Goal: take as input one single bundled JS application and copy any source map into sym-assets.
     *
     * ex results: input '../../js/dist/proco/annotations/app.js' =>
     * sym-assets/js/proco-annotations-app-0da6667-f66966b.js
     * sym-assets/js/proco-annotations-app-0da6667-f66966b.js.map
     *
     * Because the UglifyJS filter would strip source maps, disable it (see CHUglifyJs2Filter)
     * Bundled code should already be minified
     * Add source map attribute for browser debugging: //# sourceMappingURL=<urlToSourceMap>
     * One single input is enforced with BundledAppWorker
     */
    public function filterDump(AssetInterface $asset)
    {
        $from = $asset->getSourceRoot() . '/' . $asset->getSourcePath() . '.map';

        if (!file_exists($from)) {
            // no source map - abort
            return;
        }

        // $asset->getTargetPath() can look like '_controller/js/0da6667_app_1.js' (debug mode - local asset generation w/o cache busting worker)
        //                                    or 'js/0da6667-f66955b_app_1-f66955b.js' (production w/ cache busting worker)
        // want to save the map file at 'js/0da6667-f66955b.js.map'. don't really care where it goes in local
        $targetPathForSourceMap = str_replace('_controller/', '', $asset->getTargetPath());
        $targetPathForSourceMap = explode('_', $targetPathForSourceMap, 2)[0]; // grab everything to left of first '_'
        $targetPathForSourceMap = $targetPathForSourceMap . '.map';
        $to = $this->asseticWriteToDir . '/' . $targetPathForSourceMap;

        // useful for local, the folder "sym-assets/js" doesn't always exist
        if (!is_dir(dirname($to))) {
            mkdir(dirname($to), 0777, true);
        }

        if (!copy($from, $to)) {
            $errors = error_get_last();
            throw new \Exception('issue copying source map ' . $errors['type'] . ' ' . $errors['message']);
        }

        $sourceMappingURL = $this->host . '/sym-assets/' . $targetPathForSourceMap;
        $asset->setContent($asset->getContent() . "\n//# sourceMappingURL=" . $sourceMappingURL);
    }
}
