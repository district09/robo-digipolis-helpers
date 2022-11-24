<?php

namespace DigipolisGent\Robo\Helpers\EventHandler\DefaultHandler;

use Symfony\Component\EventDispatcher\GenericEvent;

class RemoteSettingsHandler extends SettingsHandler
{

    /**
     * {@inheritDoc}
     */
    public function handle(GenericEvent $event)
    {
        $this->readProperties();
        $user = $event->getArgument('user');
        $servers = $event->getArgument('servers');
        $privateKeyFile = $event->getArgument('privateKeyFile');
        $app = $event->getArgument('app');
        $timestamp = $event->getArgument('timestamp');
        $defaults = [
            'user' => $user,
            'private-key' => $privateKeyFile,
            'app' => $app,
            'createbackup' => true,
            'time' => $timestamp,
            'filesdir' => 'files',
        ];

        // Set up destination config.
        $replacements = array(
            '[user]' => $user,
            '[private-key]' => $privateKeyFile,
            '[app]' => $app,
            '[time]' => $timestamp,
        );
        if (is_array($servers)) {
            foreach ($servers as $key => $server) {
                $replacements['[server-' . $key . ']'] = $server;
                $defaults['server-' . $key] = $server;
            }
        }

        $settings = $this->processEnvironmentOverrides(
            ($this->tokenReplace($this->getConfig()->get('remote'), $replacements) ?? []) + $defaults
        );

        // Reverse the symlinks so the `current` symlink is the last one to be
        // created.
        $settings['symlinks'] = array_reverse($settings['symlinks'] ?? [], true);

        return $settings;
    }
}
