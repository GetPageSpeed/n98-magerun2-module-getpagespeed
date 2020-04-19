<?php

namespace GetPageSpeed;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputOption;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

class DeployLocaleActiveCommand extends AbstractMagentoCommand
{

    protected function configure()
    {
      $this
          ->setName('deploy:locale:active')
          ->setDescription('Get list of active locales (GetPageSpeed)')
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


            /** @var MagentoFrameworkAppObjectManager $objManager **/
            $objManager = \Magento\Framework\App\ObjectManager::getInstance();

            /** @var MagentoStoreModelStoreManagerInterface|MagentoStoreModelStoreManager $storeManager **/
            $storeManager = $objManager->get('Magento\Store\Model\StoreManagerInterface');
            $stores = $storeManager->getStores($withDefault = true);

            //Get scope config
            /** @var MagentoFrameworkAppConfigScopeConfigInterface|MagentoFrameworkAppConfig $scopeConfig **/
            $scopeConfig = $objManager->get('Magento\Framework\App\Config\ScopeConfigInterface');

            //new empty array to store locale codes
            $localeForAllStores = [];

            //To get list of locale for all stores;
            foreach($stores as $store) {
                $localeForAllStores[] = $scopeConfig->getValue('general/locale/code', 'store', $store->getStoreId());
            }

            $localeForAllStores = array_unique($localeForAllStores);

            if (!$input->getOption('format')) {
                $output->writeln(implode(' ', $localeForAllStores));
            }

            if ($input->getOption('format') == 'json') {
                $output->writeln(
                    json_encode($localeForAllStores, JSON_PRETTY_PRINT)
                );
            }
        }
    }
}
