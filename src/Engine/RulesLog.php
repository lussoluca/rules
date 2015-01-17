<?php

/**
 * @file
 * Contains \Drupal\rules\RulesLog.
 */

namespace Drupal\rules\Engine;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Psr\Log\LogLevel;

/**
 * The rules default logging class.
 */
class RulesLog implements RulesLogInterface {
  use LinkGeneratorTrait;
  use StringTranslationTrait;

  protected $log = [];
  protected $logLevel;
  protected $line = 0;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $loggerChannel;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * @param \Drupal\Core\Logger\LoggerChannelInterface $loggerChannel
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   */
  public function __construct(LoggerChannelInterface $loggerChannel, ConfigFactoryInterface $config) {
    $this->loggerChannel = $loggerChannel;
    $this->config = $config;

    $this->logLevel = $config->get('rules.config')->get('rules_log_level');
  }

  public function __clone() {
    throw new \Exception("Cannot clone the logger.");
  }

  /**
   * {@inheritdoc}
   */
  public function log($msg, $args = [], $logLevel = LogLevel::INFO, $scope = NULL, $path = NULL) {
    $config = $this->config->get('rules.config');

    $rules_log_errors = $config->get('rules_log_errors');
    $rules_debug_log = $config->get('rules_debug_log');
    $rules_debug = $config->get('rules_debug');

    if ($logLevel >= $rules_log_errors) {
      $this->loggerChannel->log($logLevel, $msg, $args);
    }

    // Do nothing in case debugging is totally disabled.
    if (!$rules_debug_log && !$rules_debug) {
      return;
    }

    if ($logLevel >= $this->logLevel) {
      $this->log[] = [$msg, $args, $logLevel, microtime(TRUE), $scope, $path];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkLog($logLevel = LogLevel::WARNING) {
    foreach ($this->log as $entry) {
      if ($entry[2] >= $logLevel) {
        throw new \Exception($this->render());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasErrors($logLevel = LogLevel::WARNING) {
    foreach ($this->log as $entry) {
      if ($entry[2] >= $logLevel) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    return $this->log;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $line = 0;
    $output = [];
    while (isset($this->log[$line])) {
      $vars['head'] = $this->t($this->log[$line][0], $this->log[$line][1]);
      $vars['log'] = $this->renderHelper($line);
      $output[] = [
        '#theme' => 'rules_debug_element',
        '#head' => $vars['head'],
        '#log' => $vars['log'],
      ];
      $line++;
    }

    return $output;
  }

  /**
   * Renders the log of one event invocation.
   *
   * @param int $line
   *
   * @return array
   */
  protected function renderHelper(&$line = 0) {
    $startTime = isset($this->log[$line][3]) ? $this->log[$line][3] : 0;
    $output = [];
    while ($line < count($this->log)) {
      if ($output && !empty($this->log[$line][4])) {
        // The next entry stems from another evaluated set, add in its log
        // messages here.
        $vars['head'] = $this->t($this->log[$line][0], $this->log[$line][1]);
        if (isset($this->log[$line][5])) {
          $vars['link'] = '[' . $this->l('edit', Url::fromUri($this->log[$line][5])) . ']';
        }
        $vars['log'] = $this->renderHelper($line);
        $output[] = [
          '#theme' => 'rules_debug_element',
          '#head' => $vars['head'],
          '#link' => $vars['link'],
          '#log' => $vars['log'],
        ];
      }
      else {
        $formatted_diff = round(($this->log[$line][3] - $startTime) * 1000, 3) .' ms';
        $msg = $formatted_diff .' '. $this->t($this->log[$line][0], $this->log[$line][1]);
        if ($this->log[$line][2] >= LogLevel::WARNING) {
          $level = $this->log[$line][2] == LogLevel::WARNING ? 'warn' : 'error';
          $msg = '<span class="rules-debug-' . $level . '">'. $msg .'</span>';
        }
        if (isset($this->log[$line][5]) && !isset($this->log[$line][4])) {
          $msg .= ' [' . $this->l('edit', Url::fromUri($this->log[$line][5])) . ']';
        }
        $output[] = $msg;

        if (isset($this->log[$line][5]) && !$this->log[$line][4]) {
          // This was the last log entry of this set.
          return [
            '#theme' => 'item_list',
            '#items' => $output,
          ];
        }
      }
      $line++;
    }
    return [
      '#theme' => 'item_list',
      '#items' => $output,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function clear() {
    $this->log = [];
  }
}
