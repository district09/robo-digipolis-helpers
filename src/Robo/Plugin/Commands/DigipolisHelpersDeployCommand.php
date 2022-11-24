<?php

namespace DigipolisGent\Robo\Helpers\Robo\Plugin\Commands;

use Consolidation\AnnotatedCommand\Events\CustomEventAwareInterface;
use Robo\Tasks;

class DigipolisHelpersDeployCommand extends Tasks implements CustomEventAwareInterface
{

    use \DigipolisGent\Robo\Helpers\Traits\DigipolisHelpersDeployCommandUtilities;

    /**
     * Build a site and push it to the servers.
     *
     * @param array $arguments
     *   Variable amount of arguments. The last argument is the path to the
     *   the private key file (ssh), the penultimate is the ssh user. All
     *   arguments before that are server IP's to deploy to.
     * @param array $opts
     *   The options for this task.
     *
     * @return \Robo\Contract\TaskInterface
     *   The deploy task.
     *
     * @option force-install
     *   Force install even if we could just update.
     * @option worker
     *   For load-balanced environments, server to execute the updates on.
     * @option app
     *   The name of the app
     *
     * @command digipolis:deploy
     */
  public function deployCommand(
      array $arguments,
      $opts = [
          'force-install' => false,
          'worker' => null,
          'app' => 'default',
      ]
  ) {
      return $this->deploy($arguments, $opts);
  }
}
