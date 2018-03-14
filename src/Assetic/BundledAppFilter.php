<?php

namespace CourseHero\UtilsBundle\Assetic;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;
use Assetic\Filter\UglifyJs2Filter;

class BundledAppFilter implements FilterInterface
{
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
     * sym-assets/js/0da6667_proco-annotations-app.js
     * sym-assets/js/0da6667_proco-annotations-app.js.map
     *
     * Because the UglifyJS filter would strip source maps, disable it (see CHUglifyJs2Filter)
     * Bundled code should already be minified.
     * Attempt to rename asset based on bundle name.
     * Add source map attribute for browser debugging: //# sourceMappingURL=<asset basename>.map
     * One single input is enforced with BundledAppWorker
     *
     */
    public function filterDump(AssetInterface $asset)
    {
        $from = $asset->getSourceRoot() . '/' . $asset->getSourcePath() . '.map';

        if (!file_exists($from)) {
            // no source map - abort
            return;
        }

        $content = $asset->getContent();
        $symAssetsSourceMapsRoot = "/var/www/html/coursehero/src/Control/sym-assets-maps/";
        $host = 'https://coursehero.local';

        // $asset->getTargetPath() can look like '_controller/js/0da6667_app_1.js'
        $targetPath = str_replace('_controller/', '', $asset->getTargetPath());
        $sourceMapHash = substr(hash_file('sha1', $from), 0, 7);
        $to = $symAssetsSourceMapsRoot . $sourceMapHash . '_' . basename($asset->getTargetPath()) . '.map';

        $errors = false;
        if (!copy($from, $to)) {
            $errors = error_get_last();
            throw new \Exception('issue copying source map ' . $errors['type'] . ' ' . $errors['message']);
        }

        $sourceMappingURL = $host . '/sym-assets-maps/' . basename($to);
        $asset->setContent($content . "\n//# sourceMappingURL=" . $sourceMappingURL);
    }
}
