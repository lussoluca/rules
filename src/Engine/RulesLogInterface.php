<?php

namespace Drupal\rules\Engine;

use Psr\Log\LogLevel;

/**
 * Interface RulesLogInterface
 */
interface RulesLogInterface {

  /**
   * Logs a log message.
   *
   * @param $msg
   * @param array $args
   * @param string $logLevel
   * @param null $scope
   * @param null $path
   *
   * @see rules_log()
   */
  public function log($msg, $args = [], $logLevel = LogLevel::INFO, $scope = NULL, $path = NULL);

  /**
   * Checks the log and throws an exception if there were any problems.
   *
   * @param string $logLevel
   *
   * @throws \Exception
   */
  public function checkLog($logLevel = LogLevel::WARNING);

  /**
   * Checks the log for (error) messages with a log level equal
   * or higher than the given one.
   *
   * @param string $logLevel
   *
   * @return bool
   *   Whether the an error has been logged.
   */
  public function hasErrors($logLevel = LogLevel::WARNING);

  /**
   * Gets an array of logged messages.
   */
  public function get();

  /**
   * Renders the whole log.
   */
  public function render();

  /**
   * Clears the logged messages.
   */
  public function clear();
}
