<?php

namespace DigipolisGent\Robo\Helpers\Robo\Plugin\Tasks;

use DigipolisGent\CommandBuilder\CommandBuilder;
use Robo\Contract\BuilderAwareInterface;
use Robo\Result;
use Robo\Task\BaseTask;
use Symfony\Component\Finder\Finder;

class SwitchPrevious extends BaseTask implements BuilderAwareInterface
{
    use \Robo\Common\BuilderAwareTrait;
    /**
     * The releases directory.
     *
     * @var string
     */
    protected $releasesDir;

    /**
     * The current release directory.
     *
     * @var string
     */
    protected $currentSymlink;

    /**
     * Creates a new SwitchPrevious task.
     *
     * @param string $releasesDir
     *   The releases directory.
     * @param string $currentSymlink
     *   The current release directory.
     */
    public function __construct($releasesDir, $currentSymlink)
    {
        $this->releasesDir = $releasesDir;
        $this->currentSymlink = $currentSymlink;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $finder = new Finder();
        // Get all releases.
        $releases = iterator_to_array(
            $finder
                ->directories()
                ->in($this->releasesDir)
                ->sortByName()
                ->depth(0)
                ->getIterator()
        );
        // Last element is the current release.
        array_pop($releases);
        if ($releases) {
            // Normalize the paths.
            $currentDir = readlink($this->currentSymlink);
            $releasesDir = realpath($this->releasesDir);
            // Get the right folder within the release dir to symlink.
            // The current directory will look something like
            // $releasesDir/[releasenumber]/[webroot]. It's possible that
            // [webroot] is empty (meaning the webroot is the same as the
            // project root, but the code below takes that into account. We
            // first strip off $releasesDir/, so [releasenumber]/[webroot]
            // remains.
            $relativeRootDir = substr($currentDir, strlen($releasesDir . '/'));
            // We explode the path in to parts, shift off [releasenumber] so
            // that [webroot] (== the relative path to the web directory, might
            // be an empty string) remains.
            $parts = explode('/', $relativeRootDir);
            array_shift($parts);
            $relativeWebDir = implode('/', $parts);
            // We find the previous release path and append the relative web
            // directory to it, to find the target for our new symlink.
            $previous = end($releases)->getRealPath() . '/' . $relativeWebDir;
            return (
                $this->collectionBuilder()->taskExec(
                    (string) CommandBuilder::create('ln')
                        ->addFlag('s')
                        ->addFlag('T')
                        ->addFlag('f')
                        ->addArgument($previous)
                        ->addArgument($this->currentSymlink)
                )
            )
            ->run();
        }
        return Result::success($this);
    }
}
