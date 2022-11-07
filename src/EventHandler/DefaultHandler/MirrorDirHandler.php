<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use DigipolisGent\Robo\Helpers\EventHandler\AbstractTaskEventHandler;
use Symfony\Component\EventDispatcher\GenericEvent;

class MirrorDirHandler extends AbstractTaskEventHandler
{

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        $dir = $event->getArgument('dir');
        $destination = $event->getArgument('destination');
        if (!is_dir($dir)) {
            return $this->collectionBuilder();
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
