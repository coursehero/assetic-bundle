<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CourseHero\UtilsBundle\Command;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Util\VarUtils;
use CourseHero\UtilsBundle\Assetic\CHAssetBag;
use Symfony\Bundle\AsseticBundle\Command\DumpCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dumps assets to the filesystem.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class RebuildCommand extends DumpCommand
{
    static $assetTargetPaths;

    protected function configure()
    {
        $this
            ->setName('assetic:rebuild')
            ->setDescription('Rebuilds all assets that require to the filesystem')
            ->addArgument('write_to', InputArgument::OPTIONAL, 'Override the configured asset root')
            ->addOption('forks', null, InputOption::VALUE_REQUIRED, 'Fork work across many processes (requires kriswallsmith/spork)')
            ->addOption('watch', null, InputOption::VALUE_NONE, 'DEPRECATED: use assetic:watch instead')
            ->addOption('force', null, InputOption::VALUE_NONE, 'DEPRECATED: use assetic:watch instead')
            ->addOption('period', null, InputOption::VALUE_REQUIRED, 'DEPRECATED: use assetic:watch instead', 1)
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $stdout)
    {
        // capture error output
        $stderr = $stdout instanceof ConsoleOutputInterface
            ? $stdout->getErrorOutput()
            : $stdout;


        // print the header
        $stdout->writeln(sprintf('Dumping all <comment>%s</comment> assets.', $input->getOption('env')));
        $stdout->writeln(sprintf('Debug mode is <comment>%s</comment>.', $this->am->isDebug() ? 'on' : 'off'));
        $stdout->writeln('');

        self::$assetTargetPaths = [];
        foreach ($this->am->getNames() as $name) {
            $this->dumpAsset($name, $stdout);
        }

        $stdout->writeln('<comment>Finished rebuilding assets.</comment>');

        if (OutputInterface::VERBOSITY_VERBOSE <= $stdout->getVerbosity()) {
            $stdout->writeln('<comment>JavaScript assets (KB)</comment>');
            $this->printAssetSizes('/js\/.*\.js$/', $stdout);
    
            $stdout->writeln('<comment>CSS assets (KB)</comment>');
            $this->printAssetSizes('/css\/.*\.css$/', $stdout);
        }
    }


    /**
     * Writes an asset.
     *
     * If the application or asset is in debug mode, each leaf asset will be
     * dumped as well.
     *
     * @param string          $name   An asset name
     * @param OutputInterface $stdout The command output
     */
    public function dumpAsset($name, OutputInterface $stdout)
    {
        $asset = $this->am->get($name);
        $formula = $this->am->getFormula($name);

        // No need to compile the named JS collections separately
        // usages of @some-named-collection will be flattened into individual files,
        // and won't benefit from this caching
        // these files are assetic/*_js and assetic/*.js
        if (preg_match('/^assetic\/.*[._]js$/', $asset->getTargetPath())) {
            return;
        }

        // start by dumping the main asset
        $this->doDump($asset, $stdout);

        $debug = isset($formula[2]['debug']) ? $formula[2]['debug'] : $this->am->isDebug();
        $combine = isset($formula[2]['combine']) ? $formula[2]['combine'] : !$debug;

        // dump each leaf if no combine
        if (!$combine) {
            foreach ($asset as $leaf) {
                $this->doDump($leaf, $stdout);
            }
        }
    }

    protected function printAssetSizes(string $regex = null, OutputInterface $stdout)
    {
        // /var/www/html/coursehero/src/Control/sym-assets/
        $outputDir = rtrim(realpath($this->getContainer()->getParameter('assetic.write_to')), '/') . '/';
        
        $targetPathsRelativeToOutputDir = array_map(function ($path) use ($outputDir) {
            return str_replace($outputDir, '', realpath($path));
        }, self::$assetTargetPaths);

        if ($regex) {
            $targetPathsRelativeToOutputDir = array_filter($targetPathsRelativeToOutputDir, function ($path) use ($regex) {
                return preg_match($regex, $path);
            });
        }

        if (empty($targetPathsRelativeToOutputDir)) {
            echo("no assets matching $regex\n");
            return;
        }

        $filenameList = join(' ', $targetPathsRelativeToOutputDir);
        $stdout->writeln(shell_exec("cd $outputDir; du -ac --apparent-size $filenameList | sort -rg"));
    }

    /**
     * Performs the asset dump.
     *
     * @param AssetInterface  $asset  An asset
     * @param OutputInterface $stdout The command output
     *
     * @throws RuntimeException If there is a problem writing the asset
     */
    private function doDump(AssetInterface $asset, OutputInterface $stdout)
    {
        $combinations = VarUtils::getCombinations(
            $asset->getVars(),
            $this->getContainer()->getParameter('assetic.variables')
        );

        foreach ($combinations as $combination) {
            $asset->setValues($combination);

            // resolve the target path
            $target = rtrim($this->basePath, '/').'/'.$asset->getTargetPath();
            $target = str_replace('_controller/', '', $target);
            $target = VarUtils::resolve($target, $asset->getVars(), $asset->getValues());
            self::$assetTargetPaths[] = $target;

            if (!is_dir($dir = dirname($target))) {
                $stdout->writeln(sprintf(
                    '<comment>%s</comment> <info>[dir+]</info> %s',
                    date('H:i:s'),
                    $dir
                ));

                if (false === @mkdir($dir, 0777, true)) {
                    throw new \RuntimeException('Unable to create directory '.$dir);
                }
            }

            $stdout->writeln(sprintf(
                '<comment>%s</comment> <info>[file+]</info> %s',
                date('H:i:s'),
                $target
            ));

            if (OutputInterface::VERBOSITY_VERBOSE <= $stdout->getVerbosity()) {
                if ($asset instanceof AssetCollectionInterface) {
                    foreach ($asset as $leaf) {
                        if ($leaf instanceof CHAssetBag) {
                            $stdout->writeln('        <comment>[CHAssetBag]</comment>');
                            foreach ($leaf->getBag() as $singleAsset) {
                                $root = $singleAsset->getSourceRoot();
                                $path = $singleAsset->getSourcePath();
                                $stdout->writeln(sprintf('        <comment>%s/%s</comment>', $root ?: '[unknown root]', $path ?: '[unknown path]'));
                            }
                        } else {
                            $root = $leaf->getSourceRoot();
                            $path = $leaf->getSourcePath();
                            $stdout->writeln(sprintf('        <comment>%s/%s</comment>', $root ?: '[unknown root]', $path ?: '[unknown path]'));
                        }
                    }
                } else {
                    $root = $asset->getSourceRoot();
                    $path = $asset->getSourcePath();
                    $stdout->writeln(sprintf('        <comment>%s/%s</comment>', $root ?: '[unknown root]', $path ?: '[unknown path]'));
                }
            }

            if (\file_exists($target)) {
                $stdout->writeln(
                    '<info>found</info>'
                );
                continue;
            } else {
                $stdout->writeln(
                    '<comment>creating</comment>'
                );
            }

            if (false === @file_put_contents($target, $asset->dump())) {
                throw new \RuntimeException('Unable to write file '.$target);
            }
        }
    }
}
