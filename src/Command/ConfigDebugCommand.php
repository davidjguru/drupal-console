<?php
/**
 * @file
 * Contains \Drupal\AppConsole\Command\ConfigDebugCommand.
 */

namespace Drupal\AppConsole\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drupal\Component\Serialization\Yaml;

class ConfigDebugCommand extends ContainerAwareCommand
{
  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setName('config:debug')
      ->setDescription('Show the current configuration')
      ->addArgument('config-name', InputArgument::OPTIONAL, 'Config name')
    ;
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $config_name = $input->getArgument('config-name');

    $container = $this->getContainer();
    $configFactory = $container->get('config.factory');

    $table = $this->getHelperSet()->get('table');
    $table->setlayout($table::LAYOUT_COMPACT);

    if (!$config_name) {
      $this->getAllConfigurations($output, $table, $configFactory);
    }
    else {
      $configStorage = $container->get('config.storage');
      $this->getConfigurationByName($output, $table, $configStorage, $config_name);
    }
  }

  /**
   * @param $output         OutputInterface
   * @param $table          TableHelper
   * @param $configFactory  ConfigFactory
     */
  private function getAllConfigurations($output, $table, $configFactory){
    $names = $configFactory->listAll();
    $table->setHeaders(['Name']);
    foreach ($names as $name) {
      $table->addRow([$name]);
    }
    $table->render($output);
  }

  /**
   * @param $output         OutputInterface
   * @param $table          TableHelper
   * @param $configStorage  ConfigStorage
   * @param $config_name    String
   */
  private function getConfigurationByName($output, $table, $configStorage, $config_name){
    if ($configStorage->exists($config_name)) {
      $table->setHeaders([$config_name]);

      $configuration = $configStorage->read($config_name);
      $configurationEncoded = Yaml::encode($configuration);

      $table->addRow([$configurationEncoded]);
    }
    $table->render($output);
  }
}