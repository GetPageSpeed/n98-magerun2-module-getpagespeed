<?php

namespace GetPageSpeed;

use N98\Magento\Command\AbstractMagentoCommand;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ListCommand
 * @package N98\Magento\Command\Developer\Module
 */
class DevModuleListCommand extends AbstractMagentoCommand
{
    /**
     * @var array
     */
    protected $moduleList;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $moduleListObject;

    /**
     * @var \Magento\Framework\App\Utility\Files
     */
    protected $files;

    public function getModuleList()
    {
        return $this->moduleList;
    }

    /**
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     */
    public function inject(\Magento\Framework\Module\ModuleListInterface $moduleList, \Magento\Framework\App\Utility\Files $files, \Magento\Framework\Module\PackageInfo $info, \Magento\Framework\Component\ComponentRegistrar $componentRegistrar)
    {
        $this->moduleListObject = $moduleList;
        $this->files = $files; 
        $this->info = $info;
        $this->componentRegistrar = $componentRegistrar;
    }

    protected function configure()
    {
        $this
            ->setName('dev:module:list-extended')
            ->addOption(
                'vendor',
                null,
                InputOption::VALUE_OPTIONAL,
                'Show modules of a specific vendor (case insensitive)'
            )
            ->setDescription('List all installed modules')
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Output Format. One of [' . implode(',', RendererFactory::getFormats()) . ']'
            )
            ->addOption('updates', null, InputOption::VALUE_NONE, 'Also shows available updates')
            ->addOption('only-updates', null, InputOption::VALUE_NONE, 'Only shows modules with available updates')
            ->addOption('safe-updates', null, InputOption::VALUE_NONE, 'Only shows modules with available safe updates');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);

        $show_updates = [];
        
        if ($input->getOption('updates')) {
            $show_updates = ['', 'up-to-date', 'semver-safe-update', 'update-possible'];
        }

        if ($input->getOption('only-updates')) {
       	    $show_updates = ['semver-safe-update', 'update-possible'];
       	}

        if ($input->getOption('safe-updates')) {
       	    $show_updates = ['semver-safe-update'];
       	}


        $section_title = $show_updates ? 'Magento Modules (checking for updates...)' : 'Magento Modules';

        if ($input->getOption('format') == null) {
            $this->writeSection($output, $section_title);
        }

        $this->initMagento();

        $this->prepareModuleList($input->getOption('vendor'), $show_updates);

        if ($show_updates) {
            $this->getHelper('table')
                ->setHeaders(['Name', 'Version', 'Update Version', 'Composer Name', '(Schema) Version', 'Description'])
                ->renderByFormat($output, $this->moduleList, $input->getOption('format'));
        } else {
            $this->getHelper('table')
                ->setHeaders(['Name', 'Version', 'Composer Name', '(Schema) Version', 'Description'])
                ->renderByFormat($output, $this->moduleList, $input->getOption('format'));
        }
    }

    protected function prepareModuleList($vendor, $show_updates)
    {
        $this->moduleList = [];

        // remove "junk" phrases to reduce description field on terminal
        $strip_phrases = ['magento module', 'magento 2 module', 'for magento 2', 'for magento2', 'responsible for', 'provides a possibility to'];

        // known module descriptions, especially lengthy ones, we reword:
        $known_module_descriptions = [
            'Magento_WebapiSecurity' => 'Lessen security on some API resources',
            'Apptrian_FacebookPixel' => 'Adds Facebook Pixel with Dynamic Ads code on appropriate pages',
            'Ess_M2ePro' => 'Integration for eBay/Amazon/Walmart platforms',
            'OlegKoval_RegenerateUrlRewrites' => 'CLI tool to regenerate URL Rewrites of products',
            'Swissup_Suggestpage' => 'Show custom Suggest page after adding product to cart',
            'Wyomind_SimpleGoogleShopping' => 'Export data to a Google Merchant Account',
            'Magento_GoogleShoppingAds' => 'Connect your Magento admin with Google Merchant Center and Google Ads',
            'Wyomind_GoogleMerchantPromotions' => 'Google Merchant Promotions'
        ];
 
        if ($show_updates) { 
            $composer_outdated_list = [];
            # passing COMPOSER_HOME is required (even with shell_exec), because otherwise it's not using auth.json for whatever reason
            # TODO pass --working-dir to Magento root
            $composer_outdated_raw = shell_exec('COMPOSER_HOME=$HOME/.config/composer composer outdated --format json');
            $composer_outdated_res = json_decode($composer_outdated_raw, true);
            foreach ($composer_outdated_res['installed'] as $pkg) {
                $composer_outdated_list[$pkg['name']] = $pkg; // not only outdated packages with 'latest-status' = 'update-possible' included
            }
        }          

        foreach ($this->moduleListObject->getAll() as $moduleName => $info) {
            // First index is (probably always) vendor
            $moduleNameData = explode('_', $moduleName);

            if ($vendor !== null && strtolower($moduleNameData[0]) !== strtolower($vendor)) {
                continue;
            }

            $composer_name = $this->info->getPackageName($info['name']);
            # m2epro has one name in its composer.json while the other in packagist? wtf :p
            # we want always packagist names, please
            if ('m2e/ebay-amazon-magento2' == $composer_name) {
                $composer_name = 'm2epro/magento2-extension';
            }
            # so it probably makes more sense to check dirname inside vendor instead... where it was installed from = packagist name
            $version = $this->info->getVersion($info['name']);
            # TODO subclass Magento\Framework\Module\PackageInfo to avoid double reading composer.json files for description
            # https://www.magentoextensions.org/documentation//_package_info_8php_source.html
            $composer_path = $this->componentRegistrar->getPath(\Magento\Framework\Component\ComponentRegistrar::MODULE, $info['name']);
            $composer_path = $composer_path . '/composer.json'; 
            $composer_description = '';
            # make some known modules description errr.. shorter
            if (array_key_exists($info['name'], $known_module_descriptions)) {
                $composer_description = $known_module_descriptions[$info['name']];
            }
            if (! $composer_description && file_exists($composer_path)) {
                $composer_content = file_get_contents($composer_path);
                if ($composer_content) {
                	$composer_data = json_decode($composer_content, true);
                    if (isset($composer_data['description'])) {
                        # Some descriptions got HTML tags, who needs them in terminal
                        $composer_description = strip_tags($composer_data['description']);
                        $composer_description = str_ireplace($strip_phrases, '', $composer_description);
                        // remove double spaces after sanitizing junk phrases
                        preg_replace('/\s+/', ' ', $composer_description);    
                        // trim whitespaces from both sides, remove rightmost dot and uppercase
                        $composer_description = rtrim(ucfirst(trim($composer_description)), '.');
 
                    }
                }
            }

            if ($composer_description == 'N/A') {
                $composer_description = '';
            }

            # package's "name" in some cases can be different in vendor's composer.json vs inside package's installed composer.json
            # somehow, ended up with "m2epro/magento2-extension": "1.3.5" in main composer.json and vendor/m2epro/magento2-extension/composer.json having "name": "m2e/ebay-amazon-magento2", "version": "1.4.1",
            # so likely it was placed there manually or smth?  "m2epro/magento2-extension": "1.3.5"
            if ($show_updates) {
                $update_version = '';
                $update_status = '';
                if ($composer_name && array_key_exists($composer_name, $composer_outdated_list)) {
                    $p = $composer_outdated_list[$composer_name];
                    $update_version = $p['latest'];
                    $update_status = $p['latest-status'];
                    # TODO include optionally composer-only (not modules) from composer outdated output (it includes all pkgs :p with color coding for outdate)
                }
                if (in_array($update_status, $show_updates)) {
                    $this->moduleList[] = [$info['name'], $version, $update_version, $composer_name, $info['setup_version'], $composer_description];
                }
            } else {
                $this->moduleList[] = [$info['name'], $version, $composer_name, $info['setup_version'], $composer_description];
            }
        }
    }
}
