<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use Robo\Task\Filesystem\FilesystemStack;

trait DigipolisMirrorDirCommandTrait
{
    /**
     * @return FilesystemStack
     */
    protected function taskFilesystemStack()
    {
        return $this->task(FilesystemStack::class);
    }

    /**
     * Mirror a directory.
     *
     * @param string $dir
     *   Path of the directory to mirror.
     * @param string $destination
     *   Path of the directory where $dir should be mirrored.
     *
     * @return \Robo\Contract\TaskInterface
     *   The mirror dir task.
     *
     * @command digipolis:mirror-dir
     */
    public function digipolisMirrorDir($dir, $destination)
    {
        if (!is_dir($dir)) {
            return;
        }
        $task = $this->taskFilesystemStack();
        $task->mkdir($destination);

        $directoryIterator = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $recursiveIterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($recursiveIterator as $item) {
            $destinationFile = $destination . '/' . $recursiveIterator->getSubPathName();
            if (file_exists($destinationFile)) {
                continue;
            }
            if (is_link($item)) {
                if ($item->getRealPath() !== false) {
                    $task->symlink($item->getLinkTarget(), $destinationFile);
                }
                continue;
            }
            if ($item->isDir()) {
                $task->mkdir($destinationFile);
                continue;
            }
            $task->copy($item, $destinationFile);
        }
        return $task;
    }
}
