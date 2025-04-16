<link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
/>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=search" />
<link rel="stylesheet" href="{$module_dir}/views/css/athena.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<div>
    <!-- Sección Buscador de Creative Studios -->
    <div class="row">
        <div class="col-sm-12">
            <form name="customForm" id="customForm" class="customForm">
                <div class="searchContainer" style="background-image: url('{$module_dir}/views/img/searcher_cs.jpg');">
                    <div class="search-bar">
                        <span class="material-symbols-outlined">search</span>
                        <input type="text" name="user_input" id="user_input" placeholder='{$input_prompt_placeholder}'/>
                        <label for="file-input" accept="image/*">
                            <img id="upload-icon" src="{$module_dir}/views/img/image_icon.png" 
                            alt="{$upload_image_button}" class="image-icon"/>
                        </label>
                        <button id="searchButton" type="submit">{$search_button}</button>
                    </div>
                    
                </div>
            </form>
        </div>
    </div>
    
    <!-- Título de Athena -->
    <div class="row">
        <div class="col-xs-12">
            <div class="initial-text-main">
                <h1 class="initial-text-title-main">
                    {$title}
                </h1>
                <h2 class="initial-text-subtitle-main">
                    {$subtitle}
                </h2>
                <div class="initial-text-img-main">
                    <img src="{$module_dir}/views/img/{basename($image_logo_path)}" alt="Athena">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contenedor de Subida de Imagen / Izquierda -->
    <div class="row row-wrapper">
        <!-- Columna izquierda con área para subir imágenes -->
        <div class="col-xs-12 col-sm-4 col-md-3">
            <h1 class="text-uppercase title">
                {$left_panel_name}
            </h1>
            <p class="facet-title">
                {$image_label}
            </p>
            <div class="inputContainer">
                <div name="drop-area" id="drop-area" class="drop-area">
                    <span id="TextArea" class="TextArea">
                        {$drag_and_drop_image_label}
                    </span>
                    <input type="file" accept="image/*" name="file-input" id="file-input" form="customForm">
                    <label for="file-input" id="upload-button" class="upload-button">
                        {$upload_image_button}
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Contenedor de Resultados de Productos / Derecha -->
        <div name="productContainer" id="productContainer" class="js-content-wrapper col-xs-12 col-sm-8 col-md-9">
            {include file='module:athena/views/templates/hook/products.tpl'}
        </div>
    </div>
    
    <!-- Loader para AJAX -->
    <div id="ajax-loader" style="display: none;">
        {include file="module:athena/views/templates/hook/loader.tpl"}
    </div>
</div>