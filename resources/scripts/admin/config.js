$(document).ready(function() {
    var $page = $('#faces-admin-config'),
        $table = $page.find('#faces-admin-config-table');

    var WARNING_MESSAGE = 'Are you sure?\nThis operation can\'t be undone.';

    function facesShowMessage(response) {
        var readmore = response.hasOwnProperty('link')
            ? ' <a href="' + response.link + '" target="_blank">Read more...</a>'
            : '';
        $page.find('.alert').remove();
        $page.prepend(
            '<div class="alert alert-info alert-dismissible">' +
            response.message + readmore +
            '<span class="close" data-dismiss="alert">&times;</span></div>'
        );
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
                className: 'pnwim-td-photo text-break'
            },
            {
                className: 'pnwim-td-notes'
            },
            {
                className: 'pnwim-td-status'
            },
            {
                className: 'pnwim-td-actions'
            }
        ],
        fnDrawCallback: function() {
            $table.find('[data-action="destroy"]').on('click', function() {
                if (confirm(WARNING_MESSAGE)) {
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

    $page.find('[data-action="setting-exif"], [data-action="setting-linking"]').on('change', function(e) {
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
        if (confirm(WARNING_MESSAGE)) {
            $.ajax({
                url: $(this).data('url')
            }).done(function(response) {
                facesShowMessage(response);
                $table.DataTable().ajax.reload();
            });
        }
    });
});