jQuery(document).ready(function($) {
    console.log("tag-image-table.js carregado");

    var modal = document.getElementById("exclude-tags-modal");
    var btn = document.getElementById("open-exclude-tags-modal");
    var span = document.getElementsByClassName("close-modal")[0];

    if (btn) {
        btn.onclick = function() {
            console.log("Botão 'Excluir Tags' clicado");
            modal.style.display = "block";
        }
    }

    if (span) {
        span.onclick = function() {
            modal.style.display = "none";
        }
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Função para inicializar o modal de exclusão de tags
    function initExcludeTagsModal() {
        $("#open-exclude-tags-modal").on("click", function() {
            console.log("Botão 'Excluir Tags' clicado");
            $("#exclude-tags-modal").show();
        });

        $("#close-modal").on("click", function() {
            $("#exclude-tags-modal").hide();
        });

        $(window).on("click", function(event) {
            if (event.target == $("#exclude-tags-modal")[0]) {
                $("#exclude-tags-modal").hide();
            }
        });
    }

    // Inicializar o modal
    initExcludeTagsModal();

    // Função de autocomplete para campos de tags
    function applyAutocomplete(selector) {
        $(selector).autocomplete({
            source: function(request, response) {
                var excludedTags = Array.isArray(tagImageTable.excluded_tags) ? tagImageTable.excluded_tags : [];
                var filteredTags = tagImageTable.existing_tags.filter(function(tag) {
                    return !excludedTags.includes(tag) && tag.toLowerCase().includes(request.term.toLowerCase());
                });
                response(filteredTags);
            },
            minLength: 1,
            delay: 100,
            select: function(event, ui) {
                event.preventDefault(); // Prevenir inserção padrão

                var currentVal = $(this).val().trim();
                var tags = currentVal.length ? currentVal.split(',') : [];
                tags = tags.map(tag => tag.trim()).filter(tag => tag.length > 0); // Remover espaços e entradas vazias

                // Adiciona a tag selecionada, garantindo que não é repetida
                if (!tags.includes(ui.item.value)) {
                    tags.push(ui.item.value);
                }

                $(this).val(tags.join(', ') + ', '); // Atualiza o campo de entrada com as tags
            },
            focus: function(event, ui) {
                event.preventDefault(); // Impede que o valor seja inserido no campo enquanto se navega
                $(this).val(ui.item.value);
            }
        }).on('keydown', function(event) {
            // Permitir a inserção de múltiplas tags separadas por vírgula apenas ao soltar a tecla ENTER ou TAB
            if (event.keyCode === $.ui.keyCode.ENTER || event.keyCode === $.ui.keyCode.TAB) {
                event.preventDefault(); // Previne o comportamento padrão

                var currentVal = $(this).val().trim();
                var tags = currentVal.length ? currentVal.split(',') : [];
                tags = tags.map(tag => tag.trim()).filter(tag => tag.length > 0); // Remover espaços e entradas vazias

                $(this).val(tags.join(', ') + ', '); // Atualiza o campo com as tags válidas
            }
        });
    }

    // Aplicar autocomplete para o campo de tags em massa e campos individuais
    applyAutocomplete('#bulk-tag-input');
    applyAutocomplete('.custom-tags');

    // Selecionar/desmarcar todas as imagens
    $('#select-all, #select-all-items, #select-all-bottom').click(function() {
        $('.image-checkbox').prop('checked', this.checked);
    });

    // Sincronizar estado dos checkboxes de seleção de todos
    $('.image-checkbox').on('change', function() {
        var allChecked = $('.image-checkbox').length === $('.image-checkbox:checked').length;
        $('#select-all, #select-all-items, #select-all-bottom').prop('checked', allChecked);
    });

    // Adicionar tag em massa
    $('#bulk-tag-button, #bulk-tag-button-bottom').click(function() {
        var selectedImages = $('.image-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedImages.length === 0) {
            alert('Por favor, selecione pelo menos uma imagem.');
            return;
        }

        var tags = $(this).siblings('input[type="text"]').val().trim();
        if (tags === '') {
            alert('Por favor, insira as tags que deseja adicionar.');
            return;
        }

        // Ajax para adicionar tags às imagens selecionadas
        $.ajax({
            url: tagImageTable.ajax_url,
            method: 'POST',
            data: {
                action: 'add_bulk_tags',
                image_ids: selectedImages,
                tags: tags,
                nonce: tagImageTable.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Atualizar as tags visualmente na tabela sem recarregar a página
                    selectedImages.forEach(function(imageId) {
                        var existingTags = $('.existing-tags[data-image-id="' + imageId + '"]');
                        var newTags = tags.split(',').map(function(tag) {
                            return tag.trim();
                        });

                        newTags.forEach(function(tag) {
                            // Checar se a tag não está já listada antes de adicionar
                            if (!existingTags.text().includes(tag)) {
                                existingTags.append('<span class="tag-item" data-tag-id="' + tag + '" style="display: inline-block; margin-right: 5px; padding: 3px 6px; border: 1px solid #ddd; border-radius: 4px;">' + tag + ' <span class="remove-tag" style="cursor:pointer;">&times;</span></span> ');
                            }
                        });
                    });

                    // Limpar o campo de tags em massa após sucesso
                    $('#bulk-tag-input, #bulk-tag-input-bottom').val('');
                } else {
                    alert('Erro: ' + response.data);
                }
            },
            error: function() {
                alert('Houve um erro ao adicionar as tags.');
            }
        });
    });

    // Aplicar número de imagens por página
    $('#apply-images-per-page').click(function() {
        var imagesPerPage = $('#images-per-page').val();
        var url = new URL(window.location.href);
        url.searchParams.set('images_per_page', imagesPerPage);
        window.location.href = url.toString();
    });

    // Ir para página específica
    $('#apply-goto-page').click(function() {
        var page = $('#goto-page').val();
        var url = new URL(window.location.href);
        url.searchParams.set('paged', page);
        window.location.href = url.toString();
    });

    // Salvar tags individuais com Enter ou clique no botão Salvar
    $('.custom-tags').on('keydown', function(event) {
        if (event.keyCode === $.ui.keyCode.ENTER) {
            event.preventDefault(); // Impede o comportamento padrão
            $(this).siblings('.save-tags').click(); // Dispara o clique do botão Salvar
        }
    });

    $('.save-tags').click(function() {
        var imageId = $(this).data('image-id');
        var tags = $('.custom-tags[data-image-id="' + imageId + '"]').val().trim();

        if (tags === '') {
            alert('Por favor, insira as tags que deseja salvar.');
            return;
        }

        // Ajax para salvar as tags
        $.ajax({
            url: tagImageTable.ajax_url,
            method: 'POST',
            data: {
                action: 'save_custom_tags',
                image_id: imageId,
                tags: tags,
                nonce: tagImageTable.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Atualizar as tags exibidas na tabela sem recarregar
                    var existingTags = $('.existing-tags[data-image-id="' + imageId + '"]');
                    existingTags.empty(); // Limpar tags existentes

                    // Checar se as tags não estão vazias antes de adicionar
                    response.data.tags.split(',').forEach(function(tag) {
                        tag = tag.trim();
                        if (tag) {
                            existingTags.append('<span class="tag-item" data-tag-id="' + tag + '" style="display: inline-block; margin-right: 5px; padding: 3px 6px; border: 1px solid #ddd; border-radius: 4px;">' + tag + ' <span class="remove-tag" style="cursor:pointer;">&times;</span></span> ');
                        }
                    });

                    // Limpar o campo de tags individuais após sucesso
                    $('.custom-tags[data-image-id="' + imageId + '"]').val('');
                } else {
                    alert('Erro: ' + response.data);
                }
            },
            error: function() {
                alert('Houve um erro ao salvar as tags.');
            }
        });
    });

    // Monitorar mudanças na altura e alterar a cor do botão "Salvar" se houver alterações não salvas
    $('.dimension-input[data-type="altura"]').on('input', function() {
        var imageId = $(this).data('image-id');
        var saveButton = $('.save-dimensions[data-image-id="' + imageId + '"]');

        saveButton.addClass('unsaved'); // Adicionar classe "unsaved" quando houver alterações
    });

    // Salvar informações adicionais
    $('.save-dimensions').click(function() {
        var imageId = $(this).data('image-id');
        var dimensions = {};

        // Coletar todas as dimensões
        $('.dimension-input[data-image-id="' + imageId + '"]').each(function() {
            var type = $(this).data('type');
            dimensions[type] = $(this).val();
        });

        // Ajax para salvar as informações
        $.ajax({
            url: tagImageTable.ajax_url,
            method: 'POST',
            data: {
                action: 'save_dimensions',
                image_id: imageId,
                dimensions: dimensions,
                nonce: tagImageTable.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Remover classe "unsaved" após salvar
                    var saveButton = $('.save-dimensions[data-image-id="' + imageId + '"]');
                    saveButton.removeClass('unsaved');
                } else {
                    alert('Erro: ' + response.data);
                }
            },
            error: function() {
                alert('Houve um erro ao salvar as informações.');
            }
        });
    });

    $(document).on('click', '.remove-tag', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var tagItem = $(this).closest('.tag-item');
        var tagId = tagItem.data('tag-id'); // Obter o ID da tag
        var imageId = tagItem.closest('.existing-tags').data('image-id');
    
        // Ajax para remover a tag
        $.ajax({
            url: tagImageTable.ajax_url,
            method: 'POST',
            data: {
                action: 'remove_custom_tag',
                image_id: imageId,
                tag_id: tagId, // Usar o ID da tag
                nonce: tagImageTable.nonce
            },
            success: function(response) {
                if (response.success) {
                    tagItem.remove(); // Remover a tag da exibição sem recarregar
                } else {
                    alert('Erro: ' + response.data);
                }
            },
            error: function() {
                alert('Houve um erro ao remover a tag.');
            }
        });
    });

    // Filtrar imagens sem tags
    $('#filter-no-tags').click(function() {
        var url = new URL(window.location.href);
        url.searchParams.set('filter', 'no-tags');
        window.location.href = url.toString();
    });

    // Remover filtro e mostrar todas as imagens
    $('#show-all').click(function() {
        var url = new URL(window.location.href);
        url.searchParams.delete('filter');
        window.location.href = url.toString();
    });

    // Esconder/Mostrar imagem
    $(document).on('click', '.toggle-visibility', function() {
        var button = $(this);
        var imageId = button.data('image-id');

        $.ajax({
            url: tagImageTable.ajax_url,
            method: 'POST',
            data: {
                action: 'toggle_image_visibility',
                image_id: imageId,
                nonce: tagImageTable.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.new_status === '1') {
                        button.text('Mostrar');
                    } else {
                        button.text('Esconder');
                    }
                } else {
                    alert('Erro ao alterar a visibilidade da imagem.');
                }
            },
            error: function() {
                alert('Houve um erro ao processar a solicitação.');
            }
        });
    });

    // Função para excluir tags do modal
    function deleteExcludedTags() {
        var selectedTags = $('#excluded-tags-list').val();

        if (selectedTags.length === 0) {
            alert('Por favor, selecione ao menos uma tag para excluir.');
            return;
        }

        // Ajax para excluir as tags selecionadas
        $.ajax({
            url: tagImageTable.ajax_url,
            method: 'POST',
            data: {
                action: 'delete_excluded_tags',
                tags: selectedTags,
                nonce: tagImageTable.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Remover as tags excluídas da lista
                    selectedTags.forEach(function(tag) {
                        $('#excluded-tags-list option[value="' + tag + '"]').remove();
                    });
                } else {
                    alert('Erro: ' + response.data);
                }
            },
            error: function() {
                alert('Houve um erro ao excluir as tags.');
            }
        });
    }

    // Função para adicionar tags à lista de exclusão
    function addExcludedTags() {
        var selectedTags = $('#existing-tags-list').val();
        console.log('Selected Tags:', selectedTags);
    
        if (selectedTags.length === 0) {
            alert('Por favor, selecione ao menos uma tag para adicionar à exclusão.');
            return;
        }
    
        // Ajax para adicionar as tags à lista de exclusão
        $.ajax({
            url: tagImageTable.ajax_url,
            method: 'POST',
            data: {
                action: 'add_excluded_tags',
                tags: selectedTags,
                nonce: tagImageTable.nonce
            },
            success: function(response) {
                console.log('Resposta completa:', response);
                if (response.success) {
                    console.log('Tags adicionadas com sucesso:', response.data);
                    // Adicionar as tags à lista de exclusão
                    selectedTags.forEach(function(tag) {
                        var tagName = $('#existing-tags-list option[value="' + tag + '"]').text();
                        $('#excluded-tags-list').append('<option value="' + tag + '">' + tagName + '</option>');
                    });
                    alert('Tags adicionadas com sucesso!');
                } else {
                    console.error('Erro ao adicionar tags:', response.data);
                    alert('Erro: ' + (response.data || 'Erro desconhecido ao adicionar tags.'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro na requisição AJAX:', status, error);
                console.error('Resposta do servidor:', xhr.responseText);
                console.error('Status do erro:', xhr.status);
                console.error('Texto do status:', xhr.statusText);
                alert('Houve um erro ao adicionar as tags à exclusão. Por favor, verifique o console para mais detalhes.');
            }
        });
    }

    // Adicionar evento ao botão de excluir tags
    $('#remove-excluded-tag').click(deleteExcludedTags);

    // Adicionar evento ao botão de adicionar tags à exclusão
    $('#add-excluded-tag').click(addExcludedTags);
});