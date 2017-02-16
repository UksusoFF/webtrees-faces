function pnwimGetData($image) {
    $.ajax({
        url: 'module.php?mod=photo_note_with_image_map&mod_action=map_get',
        type: 'GET',
        data: {
            mid: $image.data('mid')
        }
    }).done(function(response) {
        pnwimRender($image, response.data.map, response.data.edit, response.data.title);
    });
}

function pnwimAddData($image, data) {
    $.ajax({
        url: 'module.php?mod=photo_note_with_image_map&mod_action=map_add',
        type: 'POST',
        data: {
            mid: $image.data('mid'),
            pid: data.pid,
            coords: data.coords
        }
    }).done(function(response) {
        pnwimRender($image, response.data.map, response.data.edit, response.data.title);
    });
}

function pnwimDeleteData($image, data) {
    $.ajax({
        url: 'module.php?mod=photo_note_with_image_map&mod_action=map_delete',
        type: 'POST',
        data: {
            mid: $image.data('mid'),
            pid: data.pid
        }
    }).done(function(response) {
        pnwimRender($image, response.data.map, response.data.edit, response.data.title);
    });
}

function pnwimRender($image, map, edit, title) {
    $('#pnwim-map').remove();
    $image.mapster('tooltip');
    $image.mapster('unbind');

    var map_name = 'pnwim-map-' + Date.now(),
        $map = $('<map/>', {
            id: 'pnwim-map',
            name: map_name
        }),
        areas = [], texts = [];
    $image.attr('usemap', '#' + map_name).after($map);

    $.each(map, function(key, item) {
        var link = item.found ? 'individual.php?pid=' + item.pid : '#';
        $map.append($('<area/>', {
            shape: item.coords.length > 4 ? 'poly' : 'rect',
            coords: item.coords.join(','),
            href: link,
            'data-pid': item.pid,
            'data-found': item.found
        }));
        areas.push({
            key: item.pid.toString(),
            toolTip: '<div class="pnwim-tooltip-wrapper">' +
            '<p class="pnwim-tooltip-name">' + item.name + '</p>' +
            '<p class="pnwim-tooltip-life">' + item.life + '</p>' +
            '</div>'
        });
        var text = '<a href="' + link + '" class="pnwim-title-name" data-pid="' + item.pid + '">' + item.name + '</a>';
        if (edit) {
            text = text + '<span class="pnwim-remove-individual" data-pid="' + item.pid + '" data-name="' + item.name + '"> (&times;)</span>';
        }
        texts.push(text);
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
            '<span class="pnwim-mark-individual"> (+) </span>'
        ] : [];

    if (areas.length) {
        $container.html(texts.join('<span class="pnwim-title-separator">, </span>') + buttons.join(''));
    } else {
        $container.html(title + buttons.join(''));
    }
    pnwimBindActions($image, $container);
}

function pnwimBindActions($image, $container) {
    $container.find('.pnwim-title-name').on('mouseenter', function(e) {
        $image.mapster('highlight', $(e.target).data('pid').toString());
    });
    $container.find('.pnwim-title-name').on('mouseleave', function() {
        $image.mapster('highlight', false);
    });
    $container.find('.pnwim-remove-individual').on('click', function(e) {
        var $target = $(e.target),
            pid = $target.data('pid'),
            name = $target.data('name'),
            $dialog = $('<div id="dialog-confirm" title="Warning">' +
                '<p>Are you sure that want delete "' + name + '" from image?</p>' +
                '<p class="pnwim-dialog-warning">This operation can\'t be undone.</p>' +
                '</div>').dialog({
                show: 'blind',
                hide: 'blind',
                modal: true,
                dialogClass: 'pnwim-dialog',
                open: function() {
                    $().colorbox.settings.arrowKey = false;
                },
                close: function() {
                    $().colorbox.settings.arrowKey = pnwim_cbox_arrow_key;
                    $dialog.dialog('destroy').remove();
                },
                buttons: {
                    'Delete': function() {
                        pnwimDeleteData($image, {
                            pid: pid
                        });
                        $dialog.dialog('close');
                    },
                    'Cancel': function() {
                        $dialog.dialog('close');
                    }
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
                var $dialog = $('<div id="dialog-form" title="Information">' +
                    '<p>Enter individuals id or something else:</p>' +
                    '<input type="text" name="pid" required class="ui-widget-content">' +
                    '</div>').dialog({
                    show: 'blind',
                    hide: 'blind',
                    modal: true,
                    dialogClass: 'pnwim-dialog',
                    open: function() {
                        $().colorbox.settings.arrowKey = false;
                    },
                    close: function() {
                        $().colorbox.settings.arrowKey = pnwim_cbox_arrow_key;
                        $dialog.dialog('destroy').remove();
                    },
                    buttons: {
                        'Add': function() {
                            var pid = $dialog.find('[name="pid"]').val();
                            if (pid) {
                                pnwimAddData($image, {
                                    pid: pid,
                                    coords: [selection.x1, selection.y1, selection.x2, selection.y2]
                                });
                            }
                            $dialog.dialog('close');
                        },
                        'Cancel': function() {
                            $dialog.dialog('close');
                        }
                    }
                });
                img_select.cancelSelection();
                img_select.remove();
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
        pnwimGetData($image)
    }
});

var pnwim_wheel_zoom_disabled = null,
    pnwim_cbox_trap_focus_state = null,
    pnwim_cbox_arrow_key = null;

$(document).bind('cbox_open', function() {
    pnwim_wheel_zoom_disabled = $.fn.wheelzoom;
    $.fn.wheelzoom = function() {
        //
    };
    pnwim_cbox_trap_focus_state = $.colorbox.settings.trapFocus;
    $.colorbox.settings.trapFocus = false;
    pnwim_cbox_arrow_key = $().colorbox.settings.arrowKey;
});

$(document).bind('cbox_cleanup', function() {
    $.fn.wheelzoom = pnwim_wheel_zoom_disabled;
    pnwim_wheel_zoom_disabled = null;
    $.colorbox.settings.trapFocus = pnwim_cbox_trap_focus_state;
    pnwim_cbox_trap_focus_state = null;
    pnwim_cbox_arrow_key = null;
});