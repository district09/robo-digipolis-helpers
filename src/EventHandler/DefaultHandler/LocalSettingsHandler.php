<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use Symfony\Component\EventDispatcher\GenericEvent;

class LocalSettingsHandler extends SettingsHandler
{

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        $this->readProperties();
        $app = $event->getArgument('app');
        $timestamp = $event->getArgument('timestamp');
        $defaults = [
            'app' => $app,
            'time' => is_null($timestamp) ? $this->time : $timestamp,
            'project_root' => $this->getConfig()->get('digipolis.root.project'),
            'web_root' => $this->getConfig()->get('digipolis.root.web'),
            'filesdir' => 'files',
        ];

        // Set up destination config.
        $replacements = array(
            '[project_root]' => $this->getConfig()->get('digipolis.root.project'),
            '[web_root]' => $this->getConfig()->get('digipolis.root.web'),
            '[app]' => $app,
            '[time]' => is_null($timestamp) ? $this->time : $timestamp,
        );

        return ($this->tokenReplace($this->getConfig()->get('local'), $replacements) ?? []) + $defaults;
    }
}
