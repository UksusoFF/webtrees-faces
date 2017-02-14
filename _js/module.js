var pnwim_not_scaled_map = [],
    pnwim_edit = false,
    pnwim_migration_mode = false;

function pnwimGetData($image) {
    $.ajax({
        url: 'module.php?mod=photo_note_with_image_map&mod_action=map',
        type: 'GET',
        data: {
            _method: 'get',
            mid: $image.data('mid')
        }
    }).done(function(response) {
        pnwimRender($image, response.data.map, response.data.edit);
    });
}

function pnwimSaveData($image, on_success) {
    var map = [];

    $.each(pnwim_not_scaled_map, function(key, item) {
        map.push({
            pid: item.pid,
            coords: item.coords
        });
    });

    $.ajax({
        url: 'module.php?mod=photo_note_with_image_map&mod_action=map',
        type: 'POST',
        data: {
            _method: 'save',
            mid: $image.data('mid'),
            map: map
        }
    }).done(function(response) {
        pnwimRender($image, response.data.map, response.data.edit);
        if (typeof on_success != 'undefined') {
            on_success(response);
        }
    });
}

function pnwimRender($image, map, edit) {
    $('#pnwim-map').remove();
    $image.mapster('unbind');

    pnwim_not_scaled_map = $.extend({}, map);
    pnwim_edit = edit;

    var map_name = 'pnwim-map-' + Date.now(),
        $map = $('<map/>', {
            id: 'pnwim-map',
            name: map_name
        }),
        areas = [], text = [];
    $image.attr('usemap', '#' + map_name).after($map);

    $.each(pnwim_not_scaled_map, function(key, item) {
        var link = item.found ? 'individual.php?pid=' + item.pid : '#';
        $map.append($('<area/>', {
            shape: item.coords.length > 4 ? 'poly' : 'rect',
            coords: item.coords.join(','),
            href: link,
            'data-pid': item.pid,
            'data-found': item.found
        }));
        areas.push({
            key: item.pid,
            toolTip: '<div class="pnwim-tooltip-wrapper">' +
            '<p class="pnwim-tooltip-name">' + item.name + '</p>' +
            '<p class="pnwim-tooltip-life">' + item.life + '</p>' +
            '</div>'
        });
        text.push('<a href="' + link + '" class="pnwim-title-name" data-pid="' + item.pid + '">' + item.name + '</a>');
    });

    $image.mapster({
        isSelectable: false,
        wrapClass: 'pnwim-photo-wrapper',
        mapKey: 'data-pid',
        fillColor: '000000',
        fillOpacity: 0,
        stroke: true,
        strokeColor: '3b5998',
        strokeWidth: 2,
        showToolTip: true,
        areas: areas,
        onClick: function(data) {
            if ($(data.e.target).data('found')) {
                window.location = $(data.e.target).attr('href');
            }
            return false;
        }
    });
    var $container = $('#cboxTitle'),
        buttons = edit ? [
            pnwim_migration_mode ? '' : '<span class="pnwim-mark-individual"> (+) </span>',
            pnwim_migration_mode ? '' : '<span class="pnwim-reset-changes hidden"> (reset) </span>',
            pnwim_migration_mode ? '<span class="pnwim-migration"> (save) </span>' :
                '<span class="pnwim-save-changes hidden"> (save) </span>'
        ] : [];

    $container.html(text.join('<span class="pnwim-title-separator">, </span>') + buttons.join(''));
    pnwimBindActions($image, $container);
}

function pnwimBindActions($image, $container) {
    $container.find('.pnwim-title-name').on('mouseenter', function(e) {
        $image.mapster('highlight', $(e.target).data('pid'));
    });
    $container.find('.pnwim-title-name').on('mouseleave', function() {
        $image.mapster('highlight', false);
    });
    $container.find('.pnwim-reset-changes').on('click', function() {
        pnwimGetData($image)
    });
    $container.find('.pnwim-save-changes').on('click', function() {
        pnwimSaveData($image);
    });
    $container.find('.pnwim-migration').on('click', function() {
        pnwimSaveData($image, function(response) {
            $.colorbox.close();
            if (response.success) {
                $('<div id="dialog-message" title="Warning">' +
                    '<p>Image map saved successfully in module settings. Please remove map from image note.</p>' +
                    '</div>').dialog({
                    show: 'blind',
                    hide: 'blind'
                });
            }
        });
    });
    $container.find('.pnwim-mark-individual').on('click', function() {
        var img_select = $image.imgAreaSelect({
            handles: false,
            show: true,
            instance: true,
            parent: $('#colorbox'),
            imageHeight: $image.naturalHeight(),
            imageWidth: $image.naturalWidth(),
            onSelectEnd: function(img, selection) {
                var pid = prompt('Enter individuals id or something else:', '');
                if (pid) {
                    pnwim_not_scaled_map[pid] = {
                        found: false,
                        pid: pid,
                        name: pid,
                        life: '',
                        coords: [selection.x1, selection.y1, selection.x2, selection.y2]
                    };
                }
                img_select.cancelSelection();
                img_select.remove();
                if (pid) {
                    pnwimRender($image, pnwim_not_scaled_map, pnwim_edit);
                    $('.pnwim-reset-changes').removeClass('hidden');
                    $('.pnwim-save-changes').removeClass('hidden');
                }
            }
        });
    });
}

$(document).bind('cbox_complete', function() {
    var $target = $().colorbox.element(),
        $image = $('img.cboxPhoto'),
        mid = new RegExp('[\?&]' + 'mid' + '=([^&#]*)').exec($target.attr('href'))[1];
    if ($image.length && mid) {
        $image.data('mid', mid);
        var $map = $($target.data("objeNote"));
        if ($map && $map.is('map')) {
            pnwim_not_scaled_map = [];
            $map.find('area').each(function() {
                var pid = $(this).data('pid');
                pnwim_not_scaled_map[pid] = {
                    found: false,
                    pid: pid,
                    name: pid,
                    life: '',
                    coords: $(this).attr('coords').split(',')
                };
            });
            pnwim_migration_mode = true;
            pnwimRender($image, pnwim_not_scaled_map, true)
        } else {
            pnwim_migration_mode = false;
            pnwimGetData($image)
        }
    }
});

var pnwim_wheel_zoom_disabled = null;

$(document).bind('cbox_open', function() {
    pnwim_wheel_zoom_disabled = $.fn.wheelzoom;
    $.fn.wheelzoom = function() {
        //
    };
});

$(document).bind('cbox_cleanup', function() {
    $.fn.wheelzoom = pnwim_wheel_zoom_disabled;
    pnwim_wheel_zoom_disabled = null;
});