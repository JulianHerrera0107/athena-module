<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\ProductAssembler;
use PrestaShop\PrestaShop\Adapter\NewProducts\ProductSearchProvider;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Athena extends Module implements WidgetInterface
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'athena';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Inference';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Athena');
        $this->description = $this->l('Busca productos por medio de IA.');
        $this->confirmUninstall = $this->l('¿Estás seguro de que deseas desinstalar?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.7.99');
    }

    public function install()
    {
        Configuration::updateValue('ATHENA_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayAthenaContent');
    }

    public function uninstall()
    {
        Configuration::deleteByName('ATHENA_LIVE_MODE');

        return parent::uninstall();
    }

    public function getContent()
    {
        return $this->postProcess() . $this->getForm();
        // return $this->getForm();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submit'.$this->name)) {
            $title = Tools::getValue('ATHENA_TITLE');
            Configuration::updateValue('ATHENA_TITLE', $title);

            $subtitle = Tools::getValue('ATHENA_SUBTITLE');
            Configuration::updateValue('ATHENA_SUBTITLE', $subtitle);

            if (isset($_FILES['ATHENA_LOGO']) && !empty($_FILES['ATHENA_LOGO']['tmp_name'])) {
                $image = $_FILES['ATHENA_LOGO'];
                $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
                $targetDir = _PS_MODULE_DIR_ . $this->name . '/views/img/';
                $targetFile = $targetDir . 'custom_image.' . $ext;
    
                if (move_uploaded_file($image['tmp_name'], $targetFile)) {
                    Configuration::updateValue('ATHENA_LOGO', $targetFile);
                } else {
                    $this->context->controller->errors[] = $this->l('Error uploading the image.');
                }
            }

            $legal_disclaimer = Tools::getValue('ATHENA_LEGAL_DISCLAIMER');
            Configuration::updateValue('ATHENA_LEGAL_DISCLAIMER', $legal_disclaimer);

            $left_panel_name = Tools::getValue('ATHENA_LEFT_PANEL_NAME');
            Configuration::updateValue('ATHENA_LEFT_PANEL_NAME', $left_panel_name);

            $image_label = Tools::getValue('ATHENA_IMAGE_LABEL');
            Configuration::updateValue('ATHENA_IMAGE_LABEL', $image_label);

            $drag_and_drop_image_label = Tools::getValue('ATHENA_DRAG_AND_DROP_IMAGE_LABEL');
            Configuration::updateValue('ATHENA_DRAG_AND_DROP_IMAGE_LABEL', $drag_and_drop_image_label);

            $upload_image_button = Tools::getValue('ATHENA_UPLOAD_IMAGE_BUTTON');
            Configuration::updateValue('ATHENA_UPLOAD_IMAGE_BUTTON', $upload_image_button);

            $input_prompt = Tools::getValue('ATHENA_INPUT_PROMPT');
            Configuration::updateValue('ATHENA_INPUT_PROMPT', $input_prompt);

            $input_prompt_placeholder = htmlspecialchars(Tools::getValue('ATHENA_INPUT_PROMPT_PLACEHOLDER'));
            Configuration::updateValue('ATHENA_INPUT_PROMPT_PLACEHOLDER', $input_prompt_placeholder);

            $search_button = Tools::getValue('ATHENA_SEARCH_BUTTON');
            Configuration::updateValue('ATHENA_SEARCH_BUTTON', $search_button);

            return $this->displayConfirmation($this->l('Config saved'));

        } else if (Tools::isSubmit('submitAPIForm')) {

            $api_url = Tools::getValue('ATHENA_API_URL');
            Configuration::updateValue('ATHENA_API_URL', $api_url);

            $api_analyze_url = Tools::getValue('ATHENA_API_ANALYZE_URL');
            Configuration::updateValue('ATHENA_API_ANALYZE_URL', $api_analyze_url);

            $api_update_url = Tools::getValue('ATHENA_UPDATE_DB_URL');
            Configuration::updateValue('ATHENA_UPDATE_DB_URL', $api_update_url);

            $recommended_subcategories = Tools::getValue('ATHENA_RECOMMENDED_SUBCATEGORIES');
            Configuration::updateValue('ATHENA_RECOMMENDED_SUBCATEGORIES', $recommended_subcategories);

            $recommended_treshold = Tools::getValue('ATHENA_RECOMMENDED_TRESHOLD');
            Configuration::updateValue('ATHENA_RECOMMENDED_TRESHOLD', $recommended_treshold);

            return $this->displayConfirmation($this->l('API configuration saved'));
        } else if (Tools::isSubmit('submitUpdateDB')) {

            $api_update_url = Configuration::get('ATHENA_UPDATE_DB_URL');

            $api_url = Configuration::get('ATHENA_API_URL');

            $response = $this->callAthenaAPI($api_url, $api_update_url);
            $response = json_decode($response, true);

            if ($response['message'] == 'Database updated') {
                $db_last_modified = date('Y-m-d H:i:s');
            } else {
                $db_last_modified = 'No data available';
            }

            Configuration::updateValue('ATHENA_LAST_MODIFIED', $db_last_modified);

            return $this->displayConfirmation($this->l($response['message']));
        }
    }

    private function getForm()
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->allow_employee_form_lang = $this->context->controller->allow_employee_form_lang;
        $helper->default_form_language = $this->context->controller->default_form_language;
        $helper->title = $this->displayName;
        $helper->submit_action = 'submit'.$this->name;

        $helper->fields_value = [
            'ATHENA_TITLE' => Configuration::get('ATHENA_TITLE', $this->l('¿Necesitas ayuda para determinar qué materiales necesitas para crear un proyecto de arte o manualidades?')),
            'ATHENA_SUBTITLE' => Configuration::get('ATHENA_SUBTITLE', $this->l('Prueba nuestra plataforma de IA Athena® para ayudarte.')),
            'ATHENA_LOGO' => Configuration::get('ATHENA_LOGO', $this->l('')),
            'ATHENA_LEGAL_DISCLAIMER' => Configuration::get('ATHENA_LEGAL_DISCLAIMER', $this->l('Si bien nuestra plataforma de inteligencia artificial tiene como objetivo ayudar a los usuarios a seleccionar los productos necesarios para sus proyectos de arte o manualidades basados en indicaciones y cargas de imágenes, no podemos garantizar la precisión o adecuación de las recomendaciones proporcionadas, Creative Studio & Co. junto con cualquier organización afiliada no se hace responsable de ninguna discrepancia entre los productos sugeridos y las expectativas o resultados de los usuarios. Se alienta a los usuarios a ejercer su propio criterio y juicio al utilizar nuestra plataforma y comprar productos para sus proyectos.')),
            'ATHENA_LEFT_PANEL_NAME' => Configuration::get('ATHENA_LEFT_PANEL_NAME', $this->l('Athena')),
            'ATHENA_IMAGE_LABEL' => Configuration::get('ATHENA_IMAGE_LABEL', $this->l('Sube una imagen')),
            'ATHENA_DRAG_AND_DROP_IMAGE_LABEL' => Configuration::get('ATHENA_DRAG_AND_DROP_IMAGE_LABEL', $this->l('Arrastra y suelta el archivo aquí')),
            'ATHENA_UPLOAD_IMAGE_BUTTON' => Configuration::get('ATHENA_UPLOAD_IMAGE_BUTTON', $this->l('Subir imagen')),
            'ATHENA_INPUT_PROMPT' => Configuration::get('ATHENA_INPUT_PROMPT', $this->l('O escribe una descripción')),
            'ATHENA_INPUT_PROMPT_PLACEHOLDER' => Configuration::get('ATHENA_INPUT_PROMPT_PLACEHOLDER', $this->l('Ejemplo: Mi hijo quiere pintar un cuadro de Mickey Mouse ¿Que productos necesito?')),
            'ATHENA_SEARCH_BUTTON' => Configuration::get('ATHENA_SEARCH_BUTTON', $this->l('Buscar')),
        ];

        $form[] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Athena view configuration'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Title'),
                        'name' => 'ATHENA_TITLE',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Subtitle'),
                        'name' => 'ATHENA_SUBTITLE',
                    ],
                    [
                        'type' => 'file',
                        'label' => $this->l('Image'),
                        'name' => 'ATHENA_LOGO',
                        'image' => Configuration::get('ATHENA_LOGO'),
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Legal disclaimer'),
                        'name' => 'ATHENA_LEGAL_DISCLAIMER',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Left panel name'),
                        'name' => 'ATHENA_LEFT_PANEL_NAME',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Image label'),
                        'name' => 'ATHENA_IMAGE_LABEL',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Drag and drop image label'),
                        'name' => 'ATHENA_DRAG_AND_DROP_IMAGE_LABEL',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Upload image button'),
                        'name' => 'ATHENA_UPLOAD_IMAGE_BUTTON',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Input prompt'),
                        'name' => 'ATHENA_INPUT_PROMPT',
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->l('Input prompt placeholder'),
                        'name' => 'ATHENA_INPUT_PROMPT_PLACEHOLDER',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Search button'),
                        'name' => 'ATHENA_SEARCH_BUTTON',
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ]
        ];

        // Segundo formulario
        $helper2 = new HelperForm();
        $helper2->module = $this;
        $helper2->name_controller = $this->name;
        $helper2->identifier = $this->identifier;
        $helper2->token = Tools::getAdminTokenLite('AdminModules');
        $helper2->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper2->allow_employee_form_lang = $this->context->controller->allow_employee_form_lang;
        $helper2->default_form_language = $this->context->controller->default_form_language;
        $helper2->title = $this->displayName;
        $helper2->submit_action = 'submitAPIForm';

        $helper2->fields_value = [
            'ATHENA_API_URL' => Configuration::get('ATHENA_API_URL', $this->l('https://athena-dev-bsxjy2ymkq-uc.a.run.app')),
            'ATHENA_API_ANALYZE_URL' => Configuration::get('ATHENA_API_ANALYZE_URL', $this->l('/analyze')),
            'ATHENA_UPDATE_DB_URL' => Configuration::get('ATHENA_UPDATE_DB_URL', $this->l('/update_db')),
            'ATHENA_RECOMMENDED_SUBCATEGORIES' => Configuration::get('ATHENA_RECOMMENDED_SUBCATEGORIES', $this->l('47,16,34,26,27,29,33')),
            'ATHENA_RECOMMENDED_TRESHOLD' => Configuration::get('ATHENA_RECOMMENDED_TRESHOLD', $this->l('10')),
        ];

        $APIform[] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('API configuration'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('API URL'),
                        'name' => 'ATHENA_API_URL',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('API Analyze path'),
                        'name' => 'ATHENA_API_ANALYZE_URL',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('API update path'),
                        'name' => 'ATHENA_UPDATE_DB_URL',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Recommended subcategories'),
                        'name' => 'ATHENA_RECOMMENDED_SUBCATEGORIES',
                        'desc' => $this->l('Subcategories ID as comma separated values'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Recommended treshold'),
                        'name' => 'ATHENA_RECOMMENDED_TRESHOLD',
                        'desc' => $this->l('Treshold value for recommended products. Value between 1 and 10. Default 8'),
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitAPIForm'
                ],
            ]
        ];

        $helper3 = new HelperForm();
        $helper3->module = $this;
        $helper3->name_controller = $this->name;
        $helper3->identifier = $this->identifier;
        $helper3->token = Tools::getAdminTokenLite('AdminModules');
        $helper3->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper3->allow_employee_form_lang = $this->context->controller->allow_employee_form_lang;
        $helper3->default_form_language = $this->context->controller->default_form_language;
        $helper3->title = $this->displayName;
        $helper3->submit_action = 'submitUpdateDB';

        $helper3->fields_value = [
            'ATHENA_LAST_MODIFIED' => Configuration::get('ATHENA_LAST_MODIFIED', $this->l('No data available')),
        ];

        $formUpdateDB[] = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Update Athena database'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Last Modified Date'),
                        'name' => 'ATHENA_LAST_MODIFIED',
                        'readonly' => true,
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Update database'),
                    'name' => 'submitUpdateDB'
                ],
            ]
        ];

        return $helper->generateForm($form) . $helper2->generateForm($APIform) . $helper3->generateForm($formUpdateDB) ;
    }

    public function hookHeader()
    {
        $cmsId = Tools::getValue('id_cms');
        if ($cmsId == 7) {
            $link = new Link;
            $ajax_link = $link->getModuleLink('athena','search');
            Media::addJsDef(array(
                "ajax_link" => $ajax_link
            ));
            $this->context->controller->addJS($this->_path.'views/js/athena.js?v1.0.9');
            $this->context->controller->addCSS($this->_path . 'views/css/athena.css');
        }
    }

    public function hookDisplayAthenaContent($params) {
        // Cargar el archivo JavaScript del módulo
        $this->context->controller->registerJavascript(
            'athena-js',
            'modules/' . $this->name . '/views/js/athena.js',
            ['position' => 'bottom', 'priority' => 150]
        );
        
        // Cargar el archivo CSS del módulo
        $this->context->controller->registerStylesheet(
            'athena-css',
            'modules/' . $this->name . '/views/css/athena.css',
            ['media' => 'all', 'priority' => 150]
        );
        
        // Definir las variables de Athena
        $api_url = Configuration::get('ATHENA_API_URL');
        $api_analyze_url = Configuration::get('ATHENA_API_ANALYZE_URL');
        $recommended_subcategories = Configuration::get('ATHENA_RECOMMENDED_SUBCATEGORIES');
        $recommended_treshold = Configuration::get('ATHENA_RECOMMENDED_TRESHOLD');
        
        Media::addJsDef([
            'athena_api_url' => $api_url . $api_analyze_url,
            'athena_recommended_subcategories' => $recommended_subcategories,
            'athena_recommended_treshold' => $recommended_treshold
        ]);
        
        // Asignar variables a Smarty
        $this->context->smarty->assign([
            'module_dir' => $this->_path,
            'title' => Configuration::get('ATHENA_TITLE'),
            'subtitle' => Configuration::get('ATHENA_SUBTITLE'),
            'image_logo_path' => _MODULE_DIR_ . $this->name . '/views/img/' . Configuration::get('ATHENA_LOGO'),
            'legal_disclaimer' => Configuration::get('ATHENA_LEGAL_DISCLAIMER'),
            'left_panel_name' => Configuration::get('ATHENA_LEFT_PANEL_NAME'),
            'image_label' => Configuration::get('ATHENA_IMAGE_LABEL'),
            'drag_and_drop_image_label' => Configuration::get('ATHENA_DRAG_AND_DROP_IMAGE_LABEL'),
            'upload_image_button' => Configuration::get('ATHENA_UPLOAD_IMAGE_BUTTON'),
            'input_prompt' => Configuration::get('ATHENA_INPUT_PROMPT'),
            'input_prompt_placeholder' => Configuration::get('ATHENA_INPUT_PROMPT_PLACEHOLDER'),
            'search_button' => Configuration::get('ATHENA_SEARCH_BUTTON'),
            'api_url' => $api_url
        ]);
        
        return $this->display(__FILE__, 'views/templates/hook/fullpage.tpl');
    }
    
    public function renderWidget($hookName, array $configuration) {
        $this->getWidgetVariables($hookName, $configuration);
        return $this->fetch('module:athena/views/templates/hook/fullpage.tpl');
    }
    
    public function getWidgetVariables($hookName, array $configuration) {
        // Definir las variables de hookDisplayAthenaContent
        $api_url = Configuration::get('ATHENA_API_URL');
        
        $this->context->controller->registerJavascript(
            'athena-js',
            'modules/' . $this->name . '/views/js/athena.js',
            ['position' => 'bottom', 'priority' => 150]
        );
        
        Media::addJsDef([
            'athena_api_url' => $api_url . Configuration::get('ATHENA_API_ANALYZE_URL'),
            'athena_recommended_subcategories' => Configuration::get('ATHENA_RECOMMENDED_SUBCATEGORIES'),
            'athena_recommended_treshold' => Configuration::get('ATHENA_RECOMMENDED_TRESHOLD')
        ]);
        
        $variables = [
            'module_dir' => $this->_path,
            'title' => Configuration::get('ATHENA_TITLE'),
            'subtitle' => Configuration::get('ATHENA_SUBTITLE'),
            'image_logo_path' => _MODULE_DIR_ . $this->name . '/views/img/' . Configuration::get('ATHENA_LOGO'),
            'legal_disclaimer' => Configuration::get('ATHENA_LEGAL_DISCLAIMER'),
            'left_panel_name' => Configuration::get('ATHENA_LEFT_PANEL_NAME'),
            'image_label' => Configuration::get('ATHENA_IMAGE_LABEL'),
            'drag_and_drop_image_label' => Configuration::get('ATHENA_DRAG_AND_DROP_IMAGE_LABEL'),
            'upload_image_button' => Configuration::get('ATHENA_UPLOAD_IMAGE_BUTTON'),
            'input_prompt' => Configuration::get('ATHENA_INPUT_PROMPT'),
            'input_prompt_placeholder' => Configuration::get('ATHENA_INPUT_PROMPT_PLACEHOLDER'),
            'search_button' => Configuration::get('ATHENA_SEARCH_BUTTON'),
            'api_url' => $api_url
        ];
        
        $this->context->smarty->assign($variables);
        return $variables;
    }

    private function callAthenaAPI($api_url, $update_path)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $api_url . $update_path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Content-Length: 0'
            )
          ));

        $response = curl_exec($curl);

        if (curl_errno($curl )) {
            throw new Exception('Error al hacer la petición: ' . curl_error($curl ));
        }
    
        $httpCode = curl_getinfo($curl , CURLINFO_HTTP_CODE);
        if ($httpCode >= 400) {
            throw new Exception('La API respondió con el código de estado HTTP: ' . $httpCode);
        }

        curl_close($curl);
        return $response;
    }
}
