<link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
/>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&icon_names=search" />
<link rel="stylesheet" href="{$module_dir}/views/css/athena.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>


<div >
    <div class="row row-wrapper">
        <form name="customForm" class="col-sm-12">
            <div class="searchContainer" style="background-image: url('{$module_dir}/views/img/searcher_cs.jpg');">
                <div class="search-bar">
                    <span class="material-symbols-outlined">search</span>
                    <input type="text" name="user_input" id="user_input" placeholder='{$input_prompt_placeholder}'/>
                    <label for="file-input" accept="image/*" name="file-input">
                    <img id="upload-button" src="{$module_dir}/views/img/image_icon.png" 
                    alt="{* Subir imagen *}{$upload_image_button}" class="image-icon"/>
                    </label>
                    <button type="submit">{* Buscar *} {$search_button}</button>
                </div>
            </div>
        </form>
        <div class="initial-text-main">
            <h1 class="initial-text-title-main">
                {* ¿Necesitas ayuda para determinar qué materiales necesitas para crear un proyecto de arte o manualidades? *}
                {$title}
            </h1>
            <h2 class="initial-text-subtitle-main">
                {* Prueba nuestra plataforma de IA Athena ® para ayudarte. *}
                {$subtitle}
            </h2>
            <div class="initial-text-img-main">
                {* <img src="{$module_dir}views/img/iaStars.png" alt="Athena"> *}
                <img src="{$module_dir}/views/img/{basename($image_logo_path)}" alt="Athena">
            </div>
        </div>
        <div name="productContainer" id="productContainer" class="js-content-wrapper left-column col-xs-12 col-sm-8 col-md-9">
            {include file='module:athena/views/templates/hook/products.tpl'}
        </div>
        <div class="col-xs-12 col-sm-4 col-md-3">
            <h1 class="text-uppercase title">
                {* Athena *}
                {$left_panel_name}
            </h1>
            <p class="facet-title">
                {* Sube una imagen *}
                {$image_label}
            </p>
            <form name="customForm" id="customForm" class="col-xs-12 col-sm-4 col-md-3 customForm">
                <div class = "inputContainer">
                    <div name="drop-area" id="drop-area" class="drop-area">
                        <span id="TextArea" class="TextArea">
                            {* Arrastra y suelta el archivo aquí *}
                            {$drag_and_drop_image_label}
                        </span>
                        <input type="file" accept="image/*" name="file-input" id="file-input">
                        <label for="file-input" id="upload-button" class="upload-button">
                            {* Subir imagen *}
                            {$upload_image_button}
                        </label>
                    </div>
                </div>
                <p class="facet-title internal-title">
                    {* O escribe una descripción *}
                    {$input_prompt}
                </p>

                <div class="inputContainerv2">
                    <textarea type = "text" name="user_input" id="user_input" class="InputText" placeholder='{$input_prompt_placeholder}'></textarea>
                </div>

                <button id='searchButton' type="submit" class="action-button">
                    {* Buscar *}
                    {$search_button}
                </button>
            </form>
        </div>
    </div>
    {* {include file="module:athena/views/templates/hook/loader.tpl"} *}
</div>