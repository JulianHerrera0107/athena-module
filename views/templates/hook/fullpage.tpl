<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=search" />
<link rel="stylesheet" href="{$module_dir}/views/css/athena.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<div class="athena-container">
    <!-- Sección Buscador de Creative Studios - Visible -->
    <div class="row search-section">
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
                        <input type="file" accept="image/*" name="file-input" id="file-input" style="display:none;">
                        <button id="searchButton" type="submit">{$search_button}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Título de Athena - Oculto -->
    <div class="row initial-text-main" style="display:none;">
        <div class="col-xs-12">
            <div class="initial-text-content">
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
    
    <!-- Interfaz de Productos - Oculta -->
    <div class="row row-wrapper" style="display:none;">
        
        <!-- Contenedor de Resultados de Productos / Izquierda -->
        <div name="productContainer" id="productContainer" class="js-content-wrapper col-xs-12 col-sm-8 col-md-9">
            {include file='module:athena/views/templates/hook/products.tpl'}
        </div>

        <!-- Contenedor Área para subir imágenes / Derecha -->
        <div class="col-xs-12 col-sm-4 col-md-3">
            <h1 class="text-uppercase title">
                {$left_panel_name}
            </h1>
            <div class="inputContainer">
                <div name="drop-area" id="drop-area" class="drop-area">
                    <span id="TextArea" class="TextArea">
                        {$drag_and_drop_image_label}
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loader para AJAX -->
    <div id="ajax-loader" style="display: none;">
        {include file="module:athena/views/templates/hook/loader.tpl"}
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ocultar elementos inicialmente
        $('.row-wrapper').hide();
        $('.initial-text-main').hide();
        
        // Función para mostrar la interfaz completa
        function showInterface() {
            $('.row-wrapper').show();
            $('.initial-text-main').show();
        }
        
        // Mostrar interfaz con en el botón de búsqueda
        $('#searchButton').on('click', function() {
            showInterface();
        });
        
        // Mostrar interfaz al subir un archivo
        $('#file-input').on('change', function() {
            var file = this.files[0];
            if (file) {
                // Mostrar la interfaz
                showInterface();
                
                // Actualizar la vista previa de la imagen
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#drop-area').css('background-image', 'url(' + e.target.result + ')');
                    $('#TextArea').css('opacity', '0');
                    $('#upload-button').css('opacity', '0');
                }
                reader.readAsDataURL(file);
            }
        });
    });
</script>