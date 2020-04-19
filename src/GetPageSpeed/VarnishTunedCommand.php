<?php

namespace GetPageSpeed;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputOption;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

class VarnishTunedCommand extends AbstractMagentoCommand
{

    const HTTP_RESP_HDR_LEN_DEF = 8192; // 8Kb is default, but check with some Varnish command what is default for your version (TODO)
    const HTTP_RESP_SIZE_DEF = 32768;  // 32Kb default
    const WORKSPACE_BACKEND_DEF = 65536; // 64Kb default
    const PRODUCT_TAG_AVG_LEN = 21; // https://devdocs.magento.com/guides/v2.0/config-guide/varnish/tshoot-varnish-503.html

    protected function configure()
    {
      $this
          ->setName('varnish:tuned')
          ->setDescription('Get tuned Varnish params')
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
 
            $tableName = $resource->getTableName('catalog_category_product'); //gives table name with prefix
            $sql = sprintf("SELECT COUNT(`product_id`) AS `product_count` FROM `%s` GROUP BY `category_id` ORDER BY `product_count` DESC LIMIT 1", $tableName);
            $largestCategoryProductCount = $connection->fetchOne($sql);
            $http_resp_hdr_len = $largestCategoryProductCount * self::PRODUCT_TAG_AVG_LEN; 
 
            // Use default if calculated value is less
            if ($http_resp_hdr_len < self::HTTP_RESP_HDR_LEN_DEF) {
                $http_resp_hdr_len = self::HTTP_RESP_HDR_LEN_DEF;
            }

            $http_resp_size = self::HTTP_RESP_SIZE_DEF + $http_resp_hdr_len - self::HTTP_RESP_HDR_LEN_DEF;
            $workspace_backend = self::WORKSPACE_BACKEND_DEF + $http_resp_hdr_len - self::HTTP_RESP_HDR_LEN_DEF;
            $tunedConfig = compact('http_resp_hdr_len', 'http_resp_size', 'workspace_backend');

            if (!$input->getOption('format')) {
                $output->writeln('<info>Largest product category has this number of products: <comment>' . $largestCategoryProductCount . '</comment></info>');
            }

            if ($input->getOption('format') !== 'json') {
                $table = array($tunedConfig);
                $this->getHelper('table')
                    ->setHeaders(array_keys($tunedConfig))
                    ->renderByFormat($output, $table, $input->getOption('format'));
            } else {
                $output->writeln(
                    json_encode($tunedConfig, JSON_PRETTY_PRINT)
                );
            }
        }
    }
}
