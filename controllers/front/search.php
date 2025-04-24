<?php

use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\ProductAssembler;
use PrestaShop\PrestaShop\Adapter\NewProducts\ProductSearchProvider;

class AthenaSearchModuleFrontController extends ModuleFrontController
{
    public function initContent()
{
    parent::initContent();
    
    $input = Tools::getValue('user_input');
    $image = isset($_FILES['file-input']) ? $_FILES['file-input'] : null;
    $img_curl = null;
    
    if ($image && isset($image['tmp_name']) && !empty($image['tmp_name'])) {
        $img_curl = new CURLFILE($image['tmp_name'], $image['type'], $image['name']);
    }
    
    $api_url = Tools::getValue('api_url');
    $recommended_subcategories = Tools::getValue('recommended_subcategories', '47,16,34,26,27,29,33');
    $recommended_treshold = Tools::getValue('recommended_treshold', '8');
    $isMobile = Tools::getValue('isMobile') === 'true';
    
    // Verificar si tenemos entrada del usuario o imagen
    if (empty($input) && $img_curl === null) {
        $this->context->smarty->assign([
            'title' => Configuration::get('ATHENA_TITLE'),
            'subtitle' => Configuration::get('ATHENA_SUBTITLE'),
            'image_logo_path' => Configuration::get('ATHENA_LOGO'),
            'legal_disclaimer' => Configuration::get('ATHENA_LEGAL_DISCLAIMER'),
            'module_dir' => $this->module->getPathUri(),
        ]);
        
        $this->setTemplate('module:athena/views/templates/hook/products.tpl');
        return;
    }
    
    // Llamar a la API
    error_log("Calling API with prompt: " . $input);
    if ($img_curl) {
        error_log("Image included in request");
    }
    
    $prodsAPI = $this->callAthenaAPI($input, $img_curl, $api_url, $recommended_subcategories, $recommended_treshold);
    error_log("API Response: " . substr($prodsAPI, 0, 500) . (strlen($prodsAPI) > 500 ? "..." : ""));
    
    // Decodificar la respuesta JSON con manejo de errores
    $prodsAPIdec = json_decode($prodsAPI, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error in initContent: " . json_last_error_msg());
        $this->context->smarty->assign([
            'products' => [],
            'recommended' => [],
            'total_products' => 0,
            'general_description' => 'Error al procesar la respuesta de la API: ' . json_last_error_msg(),
            'mobile_threshold' => $isMobile ? 2 : 4,
            'num_recommended' => 0,
            'module_dir' => $this->module->getPathUri(),
        ]);
        
        $this->setTemplate('module:athena/views/templates/hook/products.tpl');
        return;
    }
    
    // Procesar los productos con verificación
    $p = isset($prodsAPIdec['products']) ? $prodsAPIdec['products'] : [];
    $p2 = [];
    
    if (is_array($p)) {
        foreach ($p as $k => $v) {
            $description = isset($v['description']) ? $v['description'] : 'Sin descripción';
            $product_list = [];
            
            if (isset($v['list']) && is_array($v['list'])) {
                $ids = array_column($v['list'], 'id');
                if (!empty($ids)) {
                    $product_list = array_map([$this, 'getProductbyId'], $ids);
                    // Filtrar productos nulos
                    $product_list = array_filter($product_list, function($item) {
                        return $item !== null;
                    });
                }
            }
            
            $p2[$k] = [
                'description' => $description,
                'product_list' => $product_list
            ];
        }
    }
    
    // Procesar recomendaciones con verificación
    $rec = isset($prodsAPIdec['recommended']) ? $prodsAPIdec['recommended'] : [];
    $rec2 = [];
    
    if (is_array($rec)) {
        foreach ($rec as $k => $v) {
            if (is_array($v)) {
                $product_ids = array_column($v, 'id');
                if (!empty($product_ids)) {
                    $rec2[$k] = array_map([$this, 'getProductbyId'], $product_ids);
                    // Filtrar productos nulos
                    $rec2[$k] = array_filter($rec2[$k], function($item) {
                        return $item !== null;
                    });
                } else {
                    $rec2[$k] = [];
                }
            } else {
                $rec2[$k] = [];
            }
        }
    }
    
    $totalProds = isset($prodsAPIdec['num_products']) ? $prodsAPIdec['num_products'] : 0;
    $general_description = isset($prodsAPIdec['general_description']) ? $prodsAPIdec['general_description'] : 'realizar tu búsqueda';
    $mobile_threshold = $isMobile ? 2 : 4;
    $num_recommended = isset($prodsAPIdec['num_recommended']) ? $prodsAPIdec['num_recommended'] : 0;
    
    $this->context->smarty->assign([
        'products' => $p2,
        'recommended' => $rec2,
        'total_products' => $totalProds,
        'general_description' => $general_description,
        'mobile_threshold' => $mobile_threshold,
        'num_recommended' => $num_recommended,
        'module_dir' => $this->module->getPathUri(),
    ]);
    
    $this->setTemplate('module:athena/views/templates/hook/products.tpl');
}

    private function getProductbyId($id){
        if (!$id || !is_numeric($id)) {
            error_log("Invalid product ID: " . print_r($id, true));
            return null;
        }
        
        $product = new Product($id, true, $this->context->language->id);
        
        if (!Validate::isLoadedObject($product)) {
            error_log("Product not found or invalid: ID " . $id);
            return null;
        }
        
        try {
            $productImages = $product->getImages($this->context->language->id);
            $coverImage = !empty($productImages) ? $this->context->link->getImageLink($product->link_rewrite, $productImages[0]['id_image'], 'home_default') : '';
            $coverImageLegend = !empty($productImages) ? $productImages[0]['legend'] : '';
            $coverImageWidth = !empty($productImages) && isset($productImages[0]['width']) ? $productImages[0]['width'] : '';
            $coverImageHeight = !empty($productImages) && isset($productImages[0]['height']) ? $productImages[0]['height'] : '';
            
            // Trabajar con specificPrice de manera segura
            $specificPrice = null;
            if (method_exists($product, 'getSpecificPrice')) {
                $specificPrice = $product->getSpecificPrice();
            }
            
            $discountType = null;
            $discountPercentage = null;
            $discountAmountToDisplay = null;
            
            if ($specificPrice && isset($specificPrice['reduction_type']) && isset($specificPrice['reduction'])) {
                $discountType = $specificPrice['reduction_type'] == 'percentage' ? 'percentage' : 'amount';
                
                if ($specificPrice['reduction_type'] == 'percentage') {
                    $discountPercentage = $specificPrice['reduction'] * 100 . '%';
                } else {
                    $discountAmountToDisplay = $this->context->currentLocale->formatPrice($specificPrice['reduction'], $this->context->currency->iso_code);
                }
            }
            
            $flags = $this->getProductFlags($product);
            $showAvailability = $product->available_for_order && $product->quantity > 0;
            
            $product_info = [
                'id' => $product->id,
                'id_product' => $product->id,
                'id_product_attribute' => $product->cache_default_attribute,
                'name' => $product->name,
                'description_short' => $product->description_short,
                'url' => $this->context->link->getProductLink($product),
                'cover' => [
                    'bySize' => [
                        'home_default' => [
                            'url' => $coverImage,
                            'legend' => $coverImageLegend,
                            'width' => $coverImageWidth,
                            'height' => $coverImageHeight,
                        ]
                    ],
                    'large' => [
                        'url' => $coverImage
                    ]
                ],
                'price' => $this->context->currentLocale->formatPrice($product->getPrice(true), $this->context->currency->iso_code),
                'has_discount' => $specificPrice ? true : false,
                'regular_price' => $this->context->currentLocale->formatPrice($product->getPriceWithoutReduct(true), $this->context->currency->iso_code),
                'discount_type' => $discountType,
                'discount_percentage' => $discountPercentage,
                'discount_amount_to_display' => $discountAmountToDisplay,
                'show_price' => true,
                'main_variants' => $product->getAttributeCombinations($this->context->language->id),
                'flags' => $flags,
                'quantity' => $product->quantity,
                'minimal_quantity' => $product->minimal_quantity,
                'show_availability' => $showAvailability,
                'allow_oosp' => $product->out_of_stock,
            ];
            
            return $product_info;
        } catch (Exception $e) {
            error_log("Error processing product ID " . $id . ": " . $e->getMessage());
            return null;
        }
    }

    private function getProductsByIds($productIds)
    {
        $products = [];
        foreach ($productIds as $id) {
            $product = new Product($id, true, $this->context->language->id);
            if (Validate::isLoadedObject($product)) {
                $productImages = $product->getImages($this->context->language->id);
                $coverImage = !empty($productImages) ? $this->context->link->getImageLink($product->link_rewrite, $productImages[0]['id_image'], 'home_default') : '';
                $coverImageLegend = !empty($productImages) ? $productImages[0]['legend'] : '';
                $coverImageWidth = !empty($productImages) && isset($productImages[0]['width']) ? $productImages[0]['width'] : '';
                $coverImageHeight = !empty($productImages) && isset($productImages[0]['height']) ? $productImages[0]['height'] : '';
                $discountType = $product->specificPrice ? ($product->specificPrice['reduction_type'] == 'percentage' ? 'percentage' : 'amount') : null;
                $discountPercentage = $product->specificPrice ? ($product->specificPrice['reduction_type'] == 'percentage' ? $product->specificPrice['reduction'] * 100 . '%' : null) : null;
                $discountAmountToDisplay = $product->specificPrice ? ($product->specificPrice['reduction_type'] == 'amount' ? $this->context->currentLocale->formatPrice($product->specificPrice['reduction'], $this->context->currency->iso_code) : null) : null;
                $flags = $this->getProductFlags($product);
    
                // Derivar show_availability de otras propiedades del producto
                $showAvailability = $product->available_for_order && $product->quantity > 0;
    
                $products[] = [
                    'id' => $product->id,
                    'id_product' => $product->id,
                    'id_product_attribute' => $product->cache_default_attribute,
                    'name' => $product->name,
                    'description_short' => $product->description_short,
                    'url' => $this->context->link->getProductLink($product),
                    'cover' => [
                        'bySize' => [
                            'home_default' => [
                                'url' => $coverImage,
                                'legend' => $coverImageLegend,
                                'width' => $coverImageWidth,
                                'height' => $coverImageHeight,
                            ]
                        ],
                        'large' => [
                            'url' => $coverImage
                        ]
                    ],
                    'price' => $this->context->currentLocale->formatPrice($product->getPrice(true), $this->context->currency->iso_code),
                    'has_discount' => $product->specificPrice ? true : false,
                    'regular_price' => $this->context->currentLocale->formatPrice($product->getPriceWithoutReduct(true), $this->context->currency->iso_code),
                    'discount_type' => $discountType,
                    'discount_percentage' => $discountPercentage,
                    'discount_amount_to_display' => $discountAmountToDisplay,
                    'show_price' => true,
                    'main_variants' => $product->getAttributeCombinations($this->context->language->id),
                    'flags' => $flags,
                    'quantity' => $product->quantity,
                    'minimal_quantity' => $product->minimal_quantity,
                    'show_availability' => $showAvailability,
                    'allow_oosp' => $product->out_of_stock,
                ];
            }
        }
        return $products;
    }
    
    private function getProductFlags($product)
    {
        $flags = [];
        if ($product->on_sale) {
            $flags['on_sale'] = [
                'type' => 'on-sale',
                'label' => $this->trans('On sale', [], 'Shop.Theme.Catalog')
            ];
        }
        if ($product->specificPrice && $product->specificPrice['reduction']) {
            $flags['discount'] = [
                'type' => 'discount',
                'label' => $this->trans('Reduced price', [], 'Shop.Theme.Catalog')
            ];
        }
        if ($product->new) {
            $flags['new'] = [
                'type' => 'new',
                'label' => $this->trans('New', [], 'Shop.Theme.Catalog')
            ];
        }
        return $flags;
    }

    private function callAthenaAPI($prompt, $image=null, $api_url, $recommended_subcategories = '47,16,34,26,27,29,33', $recommended_treshold = '8')
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
        // CURLOPT_URL => 'https://athena-bsxjy2ymkq-uc.a.run.app/analyze',
        // CURLOPT_URL => 'https://athena-dev-bsxjy2ymkq-uc.a.run.app/analyze',
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_SSL_VERIFYPEER => false, //ELIMINAR EN PRODUCCIÓN PARA EL CERTIFICADO SSL
        CURLOPT_SSL_VERIFYHOST => 0 //ELIMINAR EN PRODUCCIÓN PARA EL CERTIFICADO SSL
        // CURLOPT_POSTFIELDS => array(
        //     // 'session_id' => $sessionID,
        //     // 'max_products' => '5',
        //     // 'max_subproducts' => '10',
        //     'prompt' => $prompt,
        //     // 'maximum_distance' => '8',
        //     'maximum_distance' => $recommended_treshold,
        //     // 'gpt4' => '1',
        //     // 'model_name' => 'gpt-4',
        //     // 'recommended_subcategories' => '{"subcategory":[47,16,34,26,27,29,33]}'),
        //     'recommended_subcategories' => '{"subcategory":[' . $recommended_subcategories . ']}'),
        // // CURLOPT_HTTPHEADER => array(
        // //     'Cookie: session=' . $sessionID
        // // ),
        ));
        $post_fields = array(
            'prompt' => $prompt,
            'maximum_distance' => $recommended_treshold,
            'recommended_subcategories' => '{"subcategory":[' . $recommended_subcategories . ']}'
        );

        if ($image) {
            $post_fields['uploaded_image'] = $image;
        }
        
        $curl_options[CURLOPT_POSTFIELDS] = $post_fields;
        
        // Aplicar las opciones de cURL
        curl_setopt_array($curl, $curl_options);
        
        // Ejecutar la solicitud
        $response = curl_exec($curl);
        
        // Capturar información sobre la solicitud
        $info = curl_getinfo($curl);
        $error = curl_error($curl);
        $errno = curl_errno($curl);
        
        // Registrar la información de la solicitud
        error_log("API URL: " . $api_url);
        error_log("HTTP Code: " . $info['http_code']);
        error_log("Total time: " . $info['total_time'] . " seconds");
        
        if ($errno) {
            error_log("cURL Error: " . $error . " (Code: " . $errno . ")");
            return json_encode([
                'products' => [],
                'recommended' => [],
                'num_products' => 0,
                'general_description' => 'Error en la conexión con la API: ' . $error,
                'num_recommended' => 0
            ]);
        }
        
        if ($info['http_code'] != 200) {
            error_log("API returned HTTP code: " . $info['http_code']);
            error_log("API Response: " . $response);
            return json_encode([
                'products' => [],
                'recommended' => [],
                'num_products' => 0,
                'general_description' => 'La API respondió con un código de error: ' . $info['http_code'],
                'num_recommended' => 0
            ]);
        }
        
        curl_close($curl);
        
        // Validar que la respuesta es JSON válido
        json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Invalid JSON response: " . json_last_error_msg());
            error_log("First 1000 characters of response: " . substr($response, 0, 1000));
            return json_encode([
                'products' => [],
                'recommended' => [],
                'num_products' => 0,
                'general_description' => 'Error al procesar la respuesta de la API: ' . json_last_error_msg(),
                'num_recommended' => 0
            ]);
        }
        
        return $response;
    }
}
