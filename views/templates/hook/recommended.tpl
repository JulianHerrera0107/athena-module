{if isset($recommended) && isset($num_recommended) && isset($mobile_threshold)}
    <div class="swiper swiperContainerRec">
        <div class="swiper-wrapper">
            {foreach from=$recommended key="name" item="item"}
                {foreach from=$item item="product"}
                    <div class="swiper-slide product_per_ customSlide">
                            {include file="catalog/_partials/miniatures/product.tpl" product=$product }
                    </div>
                {/foreach}
            {/foreach}
        </div>
        <!-- Botones de navegación -->
        <div class="navContainer">
            {if $num_recommended > $mobile_threshold}
                <div class="button-prev"></div>
                <div class="button-next"></div>
            {/if}
        </div>
    </div>
    
{/if}

<script>
    var swiper = new Swiper(".swiperContainerRec", {
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