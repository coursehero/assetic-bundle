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


        foreach ($this->am->getNames() as $name) {
            $this->dumpAsset($name, $stdout);
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
            $v2Target = "";
            $asset->setValues($combination);

            // resolve the target path
            $target = rtrim($this->basePath, '/').'/'.$asset->getTargetPath();
            $this->doAssetDump($asset, $stdout, $target);
            if (preg_match("/sym-assets\/css\/.*\.css/i", $target)) {
                $v2Target = preg_replace("/sym-assets\/css\/(.*\.css)/i", "/sym-assets/css/v2_$1", $target);
                $this->doAssetDump($asset, $stdout, $v2Target, "v2");
            }
        }
    }

    private function doAssetDump(AssetInterface $asset, OutputInterface $stdout, $target, $version = 0){
        $target = str_replace('_controller/', '', $target);
        $target = VarUtils::resolve($target, $asset->getVars(), $asset->getValues());

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
                    $root = $leaf->getSourceRoot();
                    $path = $leaf->getSourcePath();
                    $stdout->writeln(sprintf('        <comment>%s/%s</comment>', $root ?: '[unknown root]', $path ?: '[unknown path]'));
                }
            } else {
                $root = $asset->getSourceRoot();
                $path = $asset->getSourcePath();
                $stdout->writeln(sprintf('        <comment>%s/%s</comment>', $root ?: '[unknown root]', $path ?: '[unknown path]'));
            }
        }

        if (\file_exists($target)){
            $stdout->writeln(
                '<info>found</info>'
            );
            return;
        } else{
            $stdout->writeln(
                '<comment>creating</comment>'
            );
        }

        if ($version === "v2"){
            $patterns = [
                '/#ff6900/',
                '/#e65f00/',
                '/#ff781a/',
                '/#ff8026/',
                '/#F83/',
                '/#f06300/',
                '/#eb6100/',
                '/#ff8129/'
            ];

            $replacements = [
                '#e00099',
                '#CC008B',
                '#fd00ad',
                '#fd00ad',
                '#CC008B',
                '#CC008B',
                '#fd00ad',
                '#fd00ad'
            ];
            $v2Asset = preg_replace($patterns, $replacements, $asset->dump());
            if (false === @file_put_contents($target, $v2Asset)) {
                    throw new \RuntimeException('Unable to write file '.$target);
            }
        } else {
            if (false === @file_put_contents($target, $asset->dump())) {
                throw new \RuntimeException('Unable to write file '.$target);
            }
        }
    }
}
