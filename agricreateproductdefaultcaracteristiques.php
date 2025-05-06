<?php
/**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Agricreateproductdefaultcaracteristiques extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'agricreateproductdefaultcaracteristiques';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Maxime LE GENTIL';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('agricreateproductdefaultcaracteristiques');
        $this->description = $this->l('agricreateproductdefaultcaracteristiques');

        $this->confirmUninstall = $this->l('Êtes-vous sûr de désinstaller le module ?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        return parent::install() && 
               $this->registerHook('actionAdminProductsControllerSaveAfter') &&
               $this->registerHook('actionAdminProductsControllerSaveBefore') && 
               $this->registerHook('actionObjectProductAddAfter');
    }
    
    public function uninstall()
    {
        return parent::uninstall();
    }
    
    public function getContent()
    {
        $output = '';
        
        if (Tools::isSubmit('submit'.$this->name)) {
            // Traitement du formulaire
            $defaultFeatures = [];
            
            // Récupérer toutes les caractéristiques
            $features = Feature::getFeatures($this->context->language->id);
            
            foreach ($features as $feature) {
                $featureId = $feature['id_feature'];
                
                // Vérifier si cette caractéristique est activée par défaut
                $enabled = (bool)Tools::getValue('feature_enabled_'.$featureId);
                
                if ($enabled) {
                    $featureValueId = (int)Tools::getValue('feature_value_'.$featureId);
                    $customValue = Tools::getValue('feature_custom_'.$featureId);
                    
                    $defaultFeatures[$featureId] = [
                        'enabled' => $enabled,
                        'value_id' => $featureValueId,
                        'custom_value' => $customValue
                    ];
                }
            }
            
            // Sauvegarder les configurations
            Configuration::updateValue('AGRIDEFAULTFEATURES_CONFIG', json_encode($defaultFeatures));
            
            $output .= $this->displayConfirmation($this->trans('Settings updated', [], 'Admin.Notifications.Success'));
        }
        
        return $output.$this->renderForm();
    }
    
    protected function renderForm()
    {
        // Charger la configuration actuelle
        $defaultFeatures = json_decode(Configuration::get('AGRIDEFAULTFEATURES_CONFIG'), true);
        if (!is_array($defaultFeatures)) {
            $defaultFeatures = [];
        }
        
        // Récupérer toutes les caractéristiques
        $features = Feature::getFeatures($this->context->language->id);
        
        $fieldsForm = [];
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->trans('Paramètres des caractéristiques par défaut', [], 'Modules.Agridefaultfeatures.Admin'),
                'icon' => 'icon-cogs'
            ],
            'input' => [],
            'submit' => [
                'title' => $this->trans('Enregistrer', [], 'Admin.Actions'),
                'class' => 'btn btn-default pull-right'
            ]
        ];
        
        // Ajouter un champ pour chaque caractéristique
        foreach ($features as $feature) {
            $featureId = $feature['id_feature'];
            $featureEnabled = isset($defaultFeatures[$featureId]) && $defaultFeatures[$featureId]['enabled'];
            
            // Ajouter un champ pour activer/désactiver cette caractéristique
            $fieldsForm[0]['form']['input'][] = [
                'type' => 'switch',
                'label' => $feature['name'],
                'name' => 'feature_enabled_'.$featureId,
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->trans('Oui', [], 'Admin.Global')
                    ],
                    [
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->trans('Non', [], 'Admin.Global')
                    ]
                ],
                'is_bool' => true,
                'desc' => $this->trans('Activer cette caractéristique par défaut', [], 'Modules.Agridefaultfeatures.Admin')
            ];
            
            // Récupérer les valeurs de caractéristique
            $featureValues = FeatureValue::getFeatureValuesWithLang($this->context->language->id, $featureId);
            $valueOptions = [
                [
                    'id' => '0',
                    'name' => $this->trans('Valeur personnalisée', [], 'Modules.Agridefaultfeatures.Admin')
                ]
            ];
            
            foreach ($featureValues as $value) {
                $valueOptions[] = [
                    'id' => $value['id_feature_value'],
                    'name' => $value['value']
                ];
            }
            
            // Ajouter un champ pour sélectionner la valeur de caractéristique
            $fieldsForm[0]['form']['input'][] = [
                'type' => 'select',
                'label' => $this->trans('Valeur de', [], 'Modules.Agridefaultfeatures.Admin').' '.$feature['name'],
                'name' => 'feature_value_'.$featureId,
                'options' => [
                    'query' => $valueOptions,
                    'id' => 'id',
                    'name' => 'name'
                ],
                'desc' => $this->trans('Sélectionnez la valeur par défaut pour cette caractéristique', [], 'Modules.Agridefaultfeatures.Admin')
            ];
            
            // Ajouter un champ pour la valeur personnalisée
            $fieldsForm[0]['form']['input'][] = [
                'type' => 'text',
                'label' => $this->trans('Valeur personnalisée pour', [], 'Modules.Agridefaultfeatures.Admin').' '.$feature['name'],
                'name' => 'feature_custom_'.$featureId,
                'desc' => $this->trans('Utilisé seulement si "Valeur personnalisée" est sélectionnée', [], 'Modules.Agridefaultfeatures.Admin')
            ];
        }
        
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit'.$this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues($defaultFeatures),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        return $helper->generateForm($fieldsForm);
    }
    
    protected function getConfigFieldsValues($defaultFeatures)
    {
        $fields = [];
        
        // Récupérer toutes les caractéristiques
        $features = Feature::getFeatures($this->context->language->id);
        
        foreach ($features as $feature) {
            $featureId = $feature['id_feature'];
            
            // Valeurs par défaut
            $fields['feature_enabled_'.$featureId] = isset($defaultFeatures[$featureId]) ? $defaultFeatures[$featureId]['enabled'] : false;
            $fields['feature_value_'.$featureId] = isset($defaultFeatures[$featureId]) ? $defaultFeatures[$featureId]['value_id'] : 0;
            $fields['feature_custom_'.$featureId] = isset($defaultFeatures[$featureId]) ? $defaultFeatures[$featureId]['custom_value'] : '';
        }
        
        return $fields;
    }
    
    public function hookActionObjectProductAddAfter($params)
    {
        // Récupérer le produit nouvellement créé
        $product = $params['object'];
        
        // Appliquer les caractéristiques par défaut
        $this->applyDefaultFeatures($product->id);
    }
    
    public function hookActionAdminProductsControllerSaveAfter($params)
    {
        // Si c'est un nouveau produit (pas de modifications de caractéristiques existantes)
        if (isset($params['id_product']) && !isset($params['form']['step3']['features'])) {
            $this->applyDefaultFeatures($params['id_product']);
        }
    }
    
    public function hookActionAdminProductsControllerSaveBefore($params)
    {
        // Pour PrestaShop 8.1, vérifier si c'est un nouveau produit
        if (isset($params['form']['id_product']) && $params['form']['id_product'] == 0) {
            // Stocker un flag pour appliquer les caractéristiques par défaut après la création
            $this->context->cookie->agri_new_product = true;
        }
    }
    
    protected function applyDefaultFeatures($idProduct)
    {
        // Vérifier si les caractéristiques ont déjà été définies
        $existingFeatures = Product::getFeaturesStatic($idProduct);
        
        // Si des caractéristiques existent déjà, ne pas écraser
        if (!empty($existingFeatures)) {
            return;
        }
        
        // Charger la configuration
        $defaultFeatures = json_decode(Configuration::get('AGRIDEFAULTFEATURES_CONFIG'), true);
        
        if (is_array($defaultFeatures)) {
            foreach ($defaultFeatures as $featureId => $featureData) {
                if ($featureData['enabled']) {
                    if ($featureData['value_id'] > 0) {
                        // Ajouter la valeur prédéfinie
                        Product::addFeatureProductImport($idProduct, $featureId, $featureData['value_id']);
                    } else {
                        // Ajouter la valeur personnalisée
                        Product::addFeatureProductImport($idProduct, $featureId, 0, $featureData['custom_value']);
                    }
                }
            }
        }
    }
}
