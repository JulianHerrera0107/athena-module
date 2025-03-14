$(document).ready(function() {
    // alert('atheja.jsss loaded!!!!');
    var isMobile = window.innerWidth <= 768;

    $('#customForm').on('submit', function(event) {
        $('#products-main-container').hide();
        $('#ajax-loader').show();

        if (isMobile) {
            var nextElement = document.getElementById('searchButton');
            var offset = 10; // Replace with the offset you want
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

        event.preventDefault();
        $.ajax({
            url: ajax_link,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {
                $('#productContainer').html(data);
                $('#products-main-container').show();
                $('#ajax-loader').hide();
                console.log('Success:');
            },
            error: function(error) {
                console.error('Error:', error);
                $('#ajax-loader').hide();
            }
        });
    });

    // Maneja el evento de cambio del input de archivo
    $('#file-input').on('change', function() {

        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#drop-area').css('background-image', 'url(' + e.target.result + ')');
                $('#TextArea').css('opacity', '0', 'transition', 'opacity 0.5s');
                $('#upload-button').css('opacity', '0', 'transition', 'opacity 0.5s')
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
                $('#TextArea').css('opacity', '0', 'transition', 'opacity 0.5s');
                $('#upload-button').css('opacity', '0', 'transition', 'opacity 0.5s')
            }
            reader.readAsDataURL(file);
        }

    });
});

