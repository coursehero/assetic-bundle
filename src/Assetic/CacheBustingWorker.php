<?php
namespace CourseHero\UtilsBundle\Assetic;


use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Factory\LazyAssetManager;
use  Assetic\Factory\Worker\WorkerInterface;

/**
 * Adds cache busting code
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class CacheBustingWorker implements WorkerInterface
{
    protected $am;
    private $separator;

    public function __construct(LazyAssetManager $am, $separator = '-')
    {
        $this->am = $am;
        $this->separator = $separator;
    }

    public function process(AssetInterface $asset)
    {
        if (!$path = $asset->getTargetPath()) {
            // no path to work with
            return;
        }

        if (!$search = pathinfo($path, PATHINFO_EXTENSION)) {
            // nothing to replace
            return;
        }

        $replace = $this->separator.$this->getHash($asset).'.'.$search;
        if (preg_match('/'.preg_quote($replace, '/').'$/', $path)) {
            // already replaced
            return;
        }

        $asset->setTargetPath(
            preg_replace('/\.'.preg_quote($search, '/').'$/', $replace, $path)
        );
    }

    protected function getHash(AssetInterface $asset)
    {
        $hash = hash_init('sha1');

        if ($asset instanceof AssetCollectionInterface) {
            foreach ($asset as $i => $leaf) {
                $sourcePath = $leaf->getSourcePath();
                $sourceRoot = $leaf->getSourceRoot();
                hash_update($hash, $sourcePath && $sourceRoot ? hash_file('sha1', $sourceRoot."/".$sourcePath) : $i);
            }
        }

        return substr(hash_final($hash), 0, 7);
    }
}
