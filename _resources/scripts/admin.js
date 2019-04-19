$(document).ready(function() {
    var $page = $('#pnwim-config'),
        $table = $page.find('#pnwim-media-table');

    var WARNING_MESSAGE = 'Are you sure?\nThis operation can\'t be undone.';

    function pnwimShowMessage(message) {
        $page.find('.alert').remove();
        $page.prepend('<div class="alert alert-info">' + message + '</div>');
    }

    $table.dataTable({
        processing: true,
        serverSide: true,
        ajax: $table.data('url'),
        autoWidth: false,
        filter: false,
        pageLength: 10,
        pagingType: "full_numbers",
        stateSave: true,
        cookieDuration: 300,
        sort: false,
        columns: [
            {
                className: "pnwim-td-photo"
            },
            {
                className: "pnwim-td-notes"
            },
            {
                className: "pnwim-td-status"
            },
            {
                className: "pnwim-td-actions"
            }
        ],
        fnDrawCallback: function() {
            $table.find('[data-action="destroy"]').on('click', function() {
                var $btn = $(this);
                if (confirm(WARNING_MESSAGE)) {
                    $.ajax({
                        url: $btn.data('url')
                    }).done(function() {
                        $table.DataTable().ajax.reload();
                    });
                }
            });
        }
    });

    $page.find('[data-action="exif-toggle"]').on('click', function(e) {
        e.stopPropagation();
        var $btn = $(this);
        $.ajax({
            url: $btn.data('url')
        }).done(function(response) {
            $btn.find('i').toggleClass('fa-check-square-o', response.state).toggleClass('fa-square-o', !response.state);
        });
    });

    $page.find('[data-action="missed-repair"]').on('click', function() {
        if (confirm(WARNING_MESSAGE)) {
            var $btn = $(this).button('loading');
            $.ajax({
                url: $btn.data('url')
            }).done(function(response) {
                pnwimShowMessage(response.records + ' record(s) was repaired.');
                $table.DataTable().ajax.reload();
                $btn.button('reset');
            });
        }
    });

    $page.find('[data-action="missed-delete"]').on('click', function() {
        if (confirm(WARNING_MESSAGE)) {
            var $btn = $(this).button('loading');
            $.ajax({
                url: $btn.data('url')
            }).done(function(response) {
                pnwimShowMessage(response.records + ' record(s) was deleted.');
                $table.DataTable().ajax.reload();
                $btn.button('reset');
            });
        }
    });
});