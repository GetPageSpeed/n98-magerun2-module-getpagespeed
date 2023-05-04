<?php

namespace GetPageSpeed;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

use Symfony\Component\Console\Input\InputOption;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

class DbMaintainSearchQuery extends AbstractMagentoCommand
{

    protected function configure()
    {
      $this
          ->setName('db:maintain:search-query')
          ->setDescription('Safe cleaning of search queries');
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

            $table = $resource->getTableName('search_query');
            $sql = "DELETE FROM ${table} WHERE num_results = 0 AND updated_at < NOW() - INTERVAL 1 MONTH";
            $connection->query($sql);

            return Command::SUCCESS;
        } else {
            return Command::FAILURE;
        }
    }
}