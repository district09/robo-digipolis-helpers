<?php

namespace DigipolisGent\Robo\Helpers\Util;

class RemoteConfig
{
    /**
     * The host to connect to.
     *
     * @var string
     */
    protected $host;

    /**
     * The user to connect with.
     *
     * @var string
     */
    protected $user;

    /**
     * The path to the private key to connect with.
     *
     * @var string
     */
    protected $privateKeyFile;

    /**
     * Remote settings as parsed from the `remote` key from properties.yml
     *
     * @var array
     */
    protected $remoteSettings;

    /**
     * The path to the root of the current release.
     *
     * @var string
     */
    protected $currentProjectRoot;

    public function __construct(string $host, string $user, string $privateKeyFile, array $remoteSettings, string $currentProjectRoot)
    {
        $this->host = $host;
        $this->user = $user;
        $this->privateKeyFile = $privateKeyFile;
        $this->remoteSettings = $remoteSettings;
        $this->currentProjectRoot = $currentProjectRoot;
    }

    public function getHost(): string {
        return $this->host;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPrivateKeyFile(): string
    {
        return $this->privateKeyFile;
    }

    public function getRemoteSettings(): array
    {
        return $this->remoteSettings;
    }

    public function getCurrentProjectRoot(): string
    {
        return $this->currentProjectRoot;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function setUser(string $user): void
    {
        $this->user = $user;
    }

    public function setPrivateKeyFile(string $privateKeyFile): void
    {
        $this->privateKeyFile = $privateKeyFile;
    }

    public function setRemoteSettings(array $remoteSettings): void
    {
        $this->remoteSettings = $remoteSettings;
    }

    public function setCurrentProjectRoot(string $currentProjectRoot): void
    {
        $this->currentProjectRoot = $currentProjectRoot;
    }
}
