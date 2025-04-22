$(document).ready(function() {
    // console.log("Athena.js loaded with debug!");
    
    // Ocultar la interfaz de búsqueda de productos y título inicial
    $('.row-wrapper').hide();
    $('.initial-text-main').hide();
    $('#ajax-loader').hide();
    
    var isMobile = window.innerWidth <= 768;
    
    // Función para mostrar toda la interfaz
    function showInterface() {
        console.log("Mostrando interfaz");
        $('.row-wrapper').show();
        $('.initial-text-main').show();
    }
    
    // Mostrar interfaz cuando el usuario selecciona una imagen
    $('#file-input').on('change', function() {
        console.log("Archivo seleccionado");
        showInterface();
    });

    // Manejo del envío del formulario
    $('#customForm').on('submit', function(event) {
        event.preventDefault();
        console.log("Formulario enviado");
        
        // Mostrar loader y ocultar resultados anteriores
        $('#products-main-container').hide();
        $('#ajax-loader').show();
        showInterface();

        if (isMobile) {
            var nextElement = document.getElementById('searchButton');
            var offset = 10;
            var elementPosition = nextElement.getBoundingClientRect().top;
            var offsetPosition = elementPosition - offset;
    
            window.scrollTo({
                 top: offsetPosition,
                 behavior: "smooth"
            });
            nextElement.scrollIntoView({behavior: "smooth"});
        }

        var formData = new FormData(this);
        
        if (formData.get('user_input') === '') {
            formData.set('user_input', ' ');
        }

        formData.set('api_url', api_url);
        formData.set('recommended_subcategories', recommended_subcategories);
        formData.set('recommended_treshold', recommended_treshold);
        formData.set('isMobile', isMobile);

        // Realizar petición AJAX
        event.preventDefault();
        $.ajax({
            url: ajax_link,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {
                console.log('AJAX Success: Datos recibidos');
                $('#productContainer').html(data);
                $('#products-main-container').show();
                $('#ajax-loader').hide();
                showInterface(); 
            },
            error: function(error) {
                console.error('AJAX Error:', error);
                $('#ajax-loader').hide();
            }
        });
    });

    // Manejo de la imagen subida mediante el input file
    $('#file-input').on('change', function() {
        var file = this.files[0];
        if (file) {
            console.log("Procesando archivo:", file.name);
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#drop-area').css('background-image', 'url(' + e.target.result + ')');
                $('#TextArea').css('opacity', '0');
                $('#upload-button').css('opacity', '0');
            }
            reader.readAsDataURL(file);
        }
    });

    // Manejo de arrastrar y soltar
    var dropArea = $('#drop-area');

    dropArea.on('dragover', function(event) {
        event.preventDefault();
        event.stopPropagation();
        dropArea.addClass('dragover');
    });

    dropArea.on('dragleave', function(event) {
        event.preventDefault();
        event.stopPropagation();
        dropArea.removeClass('dragover');
    });

    dropArea.on('drop', function(event) {
        event.preventDefault();
        event.stopPropagation();
        dropArea.removeClass('dragover');

        var files = event.originalEvent.dataTransfer.files;
        $('#file-input')[0].files = files;

        var file = files[0];
        if (file) {
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