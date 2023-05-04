<?php

namespace GetPageSpeed;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

use Symfony\Component\Console\Input\InputOption;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;

use Magento\User\Model\ResourceModel\User\Collection as UserCollection;
use Magento\Framework\Validator\Locale;

class DeployLocaleActiveCommand extends AbstractMagentoCommand
{

    /**
     * @var UserCollection
     */
    private $userCollection;

    /**
     * @var \Magento\Store\Model\Config\StoreView
     */
    private $storeView;

    /**
     * @var Locale
     */
    private $locale;

    /**
     * @param UserCollection $userCollection
     * @param \Magento\Store\Model\Config\StoreView $storeView
     * @param Locale $locale
     */
    public function inject(
        UserCollection $userCollection,
        \Magento\Store\Model\Config\StoreView $storeView,
        Locale $locale
    ) {
       	$this->userCollection = $userCollection;
        $this->storeView = $storeView;
        $this->locale = $locale;
    }

    /**
     * Get admin user locales
     *
     * @return array
     */
    private function getAdminUserInterfaceLocales()
    {
        $locales = [];
        foreach ($this->userCollection as $user) {
            $locales[] = $user->getInterfaceLocale();
        }
	return $locales;
    }

    /**
     * Get used store and admin user locales
     *
     * @return array
     * @throws \InvalidArgumentException if unknown locale is provided by the store configuration
     */
    private function getUsedLocales()
    {
     	$usedLocales = array_merge(
            $this->storeView->retrieveLocales(),
            $this->getAdminUserInterfaceLocales()
        );
        return array_map(
            function ($locale) {
                if (!$this->locale->isValid($locale)) {
                    throw new \InvalidArgumentException(
                        $locale .
                        ' argument has invalid value, run info:language:list for list of available locales'
                    );
                }
                return $locale;
            },
            array_unique($usedLocales)
        );
    }


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

            $localeForAllStores = $this->getUsedLocales();

            if (!$input->getOption('format')) {
                $output->writeln(implode(' ', $localeForAllStores));
            }

            if ($input->getOption('format') == 'json') {
                $output->writeln(
                    json_encode($localeForAllStores, JSON_PRETTY_PRINT)
                );
            }

            return Command::SUCCESS;
        } else {
            return Command::FAILURE;
        }
    }
}
