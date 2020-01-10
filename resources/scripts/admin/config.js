$(document).ready(function() {
    var $page = $('#faces-admin-config'),
        $table = $page.find('#faces-admin-config-table');

    function facesShowMessage(response) {
        $page.find('.alert').remove();
        $page.prepend(tmpl($('#faces-alert-template').html(), {
            message: response.message,
            readmore: response.link,
        }));
    }

    $table.dataTable({
        processing: true,
        serverSide: true,
        ajax: $table.data('url'),
        autoWidth: false,
        filter: false,
        pageLength: 10,
        pagingType: 'full_numbers',
        stateSave: true,
        cookieDuration: 300,
        sort: false,
        columns: [
            {
                className: 'faces-td-photo text-break'
            },
            {
                className: 'faces-td-notes'
            },
            {
                className: 'faces-td-status'
            },
            {
                className: 'faces-td-actions'
            }
        ],
        fnDrawCallback: function() {
            $table.find('[data-action="destroy"]').on('click', function() {
                if (confirm(window.WT_FACES_WARNING)) {
                    $.ajax({
                        url: $(this).data('url')
                    }).done(function(response) {
                        facesShowMessage(response);
                        $table.DataTable().ajax.reload();
                    });
                }
            });
        }
    });

    $page.find('[data-action="setting-exif"], [data-action="setting-linking"], [data-action="setting-meta"]').on('change', function(e) {
        $.ajax({
            url: $(this).data('url')
        }).done(function(response) {
            facesShowMessage(response);
        });
    });

    $page.find('[aria-labelledby="faces-settings-menu"]').on('click', function(e) {
        e.stopPropagation();
    });

    $page.find('[data-action="missed-repair"], [data-action="missed-delete"]').on('click', function() {
        if (confirm(window.WT_FACES_WARNING)) {
            $.ajax({
                url: $(this).data('url')
            }).done(function(response) {
                facesShowMessage(response);
                $table.DataTable().ajax.reload();
            });
        }
    });
});