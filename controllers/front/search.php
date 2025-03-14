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
        $image = $_FILES['file-input'];
        if ($image['tmp_name']){
            $img_curl = new CURLFILE($image['tmp_name'], $image['type'], $image['name']);
        } else {
            $img_curl = null;
        }
        // $sessionID = Tools::getValue('sessionID');
        $api_url = Tools::getValue('api_url');
        $recommended_subcategories = Tools::getValue('recommended_subcategories');
        $recommended_treshold = Tools::getValue('recommended_treshold');
        $isMobile = Tools::getValue('isMobile');
        
        if ($isMobile=='true') {
            $isMobile = true;
        } else {
            $isMobile = false;
        }
        
        $prodsAPI = $this->callAthenaAPI($input,$img_curl, $api_url);
        
        $prodsAPIdec = json_decode($prodsAPI,true);
        
        $p = $prodsAPIdec['products'];
        // $am = $this -> array_map('getProductbyId',array_column(reset($p),'id'));
        // $am = array_map([$this, 'getProductbyId'], array_column(reset($p),'id'));
        // if ($prodsAPIdec['products']) {
        //     echo '<pre>' . $prodsAPI . '</pre>';
        //     exit;
        // }
        // echo '<pre>' . $p . '</pre>';
        // exit;
        $p2 = [];
        foreach ($p as $k=>$v) {
            // $p2[$k] = $this->getProductsByIds(array_column($v, 'id'));
            if (empty($v['list'][0]['id'])) {
                $p2[$k] = [
                    'description' => $v['description'],
                    'product_list' => []
                ];
            } else {
                $p2[$k] = [
                    'description' => $v['description'],
                    'product_list' => array_map([$this,'getProductbyId'], array_column($v['list'], 'id'))
                ];
            }
            // $p2[$k] = array_map([$this,'getProductbyId'], array_column($v['list'], 'id'));
        }

        $rec = $prodsAPIdec['recommended'];
        $rec2 = [];
        foreach ($rec as $k=>$v) {
            // $rec2[$k] = $this->getProductsByIds(array_column($v, 'id'));
            $rec2[$k] = array_map([$this,'getProductbyId'], array_column($v, 'id'));
        }

        $totalProds = $prodsAPIdec['num_products'];
        $general_description = $prodsAPIdec['general_description'];
        $mobile_threshold = ($isMobile) ? 2 : 4;
        $num_recommended = $prodsAPIdec['num_recommended'];
        
        $this->context->smarty->assign([
            'products' => $p2,
            'recommended' => $rec2,
            'total_products' => $totalProds,
            'general_description' => $general_description,
            'mobile_threshold' => $mobile_threshold,
            'num_recommended' => $num_recommended
        ]);

        $this->setTemplate('module:athena/views/templates/hook/products.tpl');

    }

    private function getProductbyId($id){
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
        return $product_info;
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
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            // 'session_id' => $sessionID,
            // 'max_products' => '5',
            // 'max_subproducts' => '10',
            'prompt' => $prompt,
            // 'maximum_distance' => '8',
            'maximum_distance' => $recommended_treshold,
            // 'gpt4' => '1',
            // 'model_name' => 'gpt-4',
            // 'recommended_subcategories' => '{"subcategory":[47,16,34,26,27,29,33]}'),
            'recommended_subcategories' => '{"subcategory":[' . $recommended_subcategories . ']}'),
        // CURLOPT_HTTPHEADER => array(
        //     'Cookie: session=' . $sessionID
        // ),
        ));

        if ($image) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, array(
                // 'session_id' => $sessionID,
                // 'max_products' => '5',
                // 'max_subproducts' => '10',
                'prompt' => $prompt,
                // 'maximum_distance' => '8',
                'maximum_distance' => $recommended_treshold,
                // 'gpt4' => '1',
                // 'model_name' => 'gpt-4',
                // 'recommended_subcategories' => '{"subcategory":[47,16,34,26,27,29,33]}',
                'recommended_subcategories' => '{"subcategory":[' . $recommended_subcategories . ']}',
                'uploaded_image' => $image
            ));
        }

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
}
