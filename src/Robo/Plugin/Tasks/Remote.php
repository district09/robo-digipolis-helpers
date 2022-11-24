<?php

namespace DigipolisGent\Robo\Helpers\Robo\Plugin\Tasks;

use DigipolisGent\CommandBuilder\CommandBuilder;
use DigipolisGent\Robo\Task\Deploy\Ssh\Auth\AbstractAuth;
use Robo\Contract\BuilderAwareInterface;
use Robo\Task\BaseTask;

abstract class Remote extends BaseTask implements BuilderAwareInterface
{
    use \Robo\Common\BuilderAwareTrait;

    /**
     * The SSH host.
     *
     * @var string
     */
    protected $host;

    /**
     * Authentication for the ssh host.
     *
     * @var AbstractAuth
     */
    protected $auth;

    /**
     * SSH timeout in seconds.
     *
     * @var int
     */
    protected $timeout = 60;

    /**
     * Working directory for the ssh command.
     *
     * @var string
     */
    protected $cwd;

    /**
     * Creates a new Remote task.
     *
     * @param string $host
     *   The host to create the backup on.
     * @param AbstractAuth $auth
     *   The authentication to use to connect to the host.
     * @param string $cwd
     *   The working directory to execute the commands in.
     */
    public function __construct($host, AbstractAuth $auth, $cwd)
    {
        $this->host = $host;
        $this->auth = $auth;
        $this->cwd = $cwd;
    }

    /**
     * The timeout in seconds.
     *
     * @param int $timeout
     *
     * @return $this
     */
    public function timeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        return $this->collectionBuilder()->taskSsh($this->host, $this->auth)
            ->remoteDirectory($this->cwd, true)
            ->timeout($this->timeout)
            ->exec((string) $this->getCommand())
            ->run();
    }

    /**
     * Get the command to run the backup over ssh.
     *
     * @return CommandBuilder
     */
    abstract protected function getCommand(): CommandBuilder;
}
