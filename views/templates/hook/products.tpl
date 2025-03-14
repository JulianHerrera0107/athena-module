<div>
    {if isset($products)}
        <div class="products-main-container" id="products-main-container">
            <h1 class="main-title">
                Hay {$total_products} productos que podrías utilizar para 
                {if substr($general_description, -1) != '.'}
                    {lcfirst($general_description)}.
                {else}
                    {lcfirst($general_description)}
                {/if}
            </h1>
            {foreach from=$products key="name" item="item"}
                <div class="item-header-container">
                    <h2 class="item-title">{$name}</h2>
                    <p class="item-description">
                        {$item['description']}
                    </p>
                </div>
                <div class="swiper swiperContainer">
                    <div class="swiper-wrapper">
                        {if count($item.product_list)> 0}
                            {foreach from=$item.product_list item="product"}
                                <div class="swiper-slide product_per_ customSlide">
                                    {include file="catalog/_partials/miniatures/product.tpl" product=$product }
                                </div>
                            {/foreach}
                        {else}
                            <div class="no-products">
                                <p>
                                    Sin stock, comunícate con uno de nuestros asesores para más información.
                                </p>
                            </div>
                        {/if}
                    </div>
                    <div class="navContainer">
                    {if count($item.product_list) > $mobile_threshold}
                            <div class="button-prev"></div>
                            <div class="button-next"></div>
                    {/if}
                    </div>
                </div>
            {/foreach}
            {if count($recommended) > 0}
                <div>
                    <h1 class="text-uppercase title">
                        También podrías necesitar
                    </h1>
                    {include file='module:athena/views/templates/hook/recommended.tpl' recommended=$recommended num_recommended=$num_recommended mobile_threshold=$mobile_threshold}
                </div>
            {/if}
        </div>
    {else}
        <div class="initial-text" id="products-main-container">
            <h1 class="initial-text-title">
                {$title}
            </h1>
            <h2 class="initial-text-subtitle">
                {$subtitle}
            </h2>
            <div class="initial-text-img">
                <img src="{$module_dir}/views/img/{basename($image_logo_path)}" alt="Athena">
            </div>
            <p class="initial-text-footnote">
                {$legal_disclaimer}
            </p>
        </div>
    {/if}
    {include file="module:athena/views/templates/hook/loader.tpl"}
</div>

<script>
    var swiper = new Swiper(".swiperContainer", {
        slidesPerView: 4, // Número de productos visibles por vez
        spaceBetween: 15, // Espacio entre productos
        navigation: {
            nextEl: '.button-next',
            prevEl: '.button-prev',
        },
        breakpoints: {
            1024: {
                slidesPerView: 4,
                spaceBetween: 15,
            },
            768: {
                slidesPerView: 3,
                spaceBetween: 15,
            },
            640: {
                slidesPerView: 2,
                spaceBetween: 15,
            },
            320: {
                slidesPerView: 1,
                spaceBetween: 15,
            },
        }
    });
</script>