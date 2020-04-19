<?php

namespace GetPageSpeed;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputOption;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

class DevThemeActiveCommand extends AbstractMagentoCommand
{

    protected function configure()
    {
      $this
          ->setName('dev:theme:active')
          ->setDescription('Get list of used themes')
          ->addOption(
              'format',
              null,
              InputOption::VALUE_OPTIONAL,
              'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
          )
      ;
    }

   /**
    * @param \Symfony\Component\Console\Input\InputInterface $input
    * @param \Symfony\Component\Console\Output\OutputInterface $output
    * @return int|void
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if ($this->initMagento()) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
 
            $themeTableName = $resource->getTableName('theme'); //gives table name with prefix
            $configTableName = $resource->getTableName('core_config_data');
            $sql = sprintf('SELECT theme_path FROM `%s` LEFT JOIN `%s` ON `value` = `theme_id` WHERE `path`=\'design/theme/theme_id\'',
                $themeTableName, $configTableName); 
            $res = $connection->fetchCol($sql);

            if (!$input->getOption('format')) {
                $out = array();
                foreach ($res as $t) {
                    $out[] = '--theme ' . $t;
                }
                $out[] = '--theme Magento/backend';
                $output->writeln(implode(' ', $out));
            }

            if ($input->getOption('format') == 'json') {
                $output->writeln(
                    json_encode($res, JSON_PRETTY_PRINT)
                );
            }
        }
    }
}
