function pnwimGetData(mid) {
    $.ajax({
        url: 'module.php?mod=photo_note_with_image_map&mod_action=note_get',
        type: 'GET',
        data: {
            mid: mid
        }
    }).done(function(response) {
        pnwimRender(response.map, response.edit, response.title);
    });
}

function pnwimAddData(mid, data) {
    $.ajax({
        url: 'module.php?mod=photo_note_with_image_map&mod_action=note_add',
        type: 'POST',
        data: {
            mid: mid,
            pid: data.pid,
            coords: data.coords
        }
    }).done(function(response) {
        pnwimRender(response.map, response.edit, response.title);
    });
}

function pnwimDeleteData(mid, data) {
    $.ajax({
        url: 'module.php?mod=photo_note_with_image_map&mod_action=note_delete',
        type: 'POST',
        data: {
            mid: mid,
            pid: data.pid
        }
    }).done(function(response) {
        pnwimRender(response.map, response.edit, response.title);
    });
}

function pnwimResetImage() {
    return $('#cboxLoadedContent').html($pnwimImage.clone()).find('img.cboxPhoto');
}

function pnwimRender(map, edit, title) {
    var $image = pnwimResetImage(),
        mapName = 'pnwim-map-' + Date.now(),
        $map = $('<map/>', {
            id: 'pnwim-map',
            name: mapName
        }),
        areas = [], texts = [];
    $image.attr('usemap', '#' + mapName).after($map);

    $.each(map, function(key, item) {
        var link = item.found ? 'individual.php?pid=' + item.pid : '#';
        $map.append($('<area/>', {
            shape: item.coords.length > 4 ? 'poly' : 'rect',
            coords: item.coords.join(','),
            href: link,
            'data-pid': item.pid,
            'data-found': item.found
        }));
        var text = '<a href="' + link + '" class="pnwim-title-name" data-pid="' + item.pid + '">' + item.name + '</a>';
        areas.push({
            key: item.pid.toString(),
            toolTip: '<div class="pnwim-tooltip-wrapper">' +
            '<p class="pnwim-tooltip-name">' + text + '</p>' +
            '<p class="pnwim-tooltip-life">' + item.life + '</p>' +
            '</div>'
        });
        if (edit && !pnwimTouchMode) {
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
        toolTipClose: pnwimTouchMode ? 'area-mouseout' : ['area-mouseout', 'image-mouseout'],
        onClick: pnwimTouchMode ? null : function(data) {
            if ($(data.e.target).data('found')) {
                window.location = $(data.e.target).attr('href');
            }
            return false;
        }
    });
    var $container = $('#cboxTitle'),
        buttons = edit && !pnwimTouchMode ? [
            '<span class="pnwim-mark-individual"> (+) </span>'
        ] : [];

    buttons.push('<span class="pnwim-toggle-mode"> (zoom) </span>');

    if (areas.length && !pnwimTouchMode) {
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
    $container.find('.pnwim-toggle-mode').on('click', function() {
        $image = pnwimResetImage();
        if (pnwimMode === 'mark') {
            pnwimMode = 'zoom';
            wheelzoom($image);
            $('#cboxTitle').addClass('pnwim-zoom-mode');
        } else {
            pnwimMode = 'mark';
            pnwimGetData(pnwimMid);
            $('#cboxTitle').removeClass('pnwim-zoom-mode');
        }
    });
    $container.find('.pnwim-remove-individual').on('click', function(e) {
        var $target = $(e.target),
            pid = $target.data('pid'),
            name = $target.data('name'),
            $dialog = $('<div id="dialog-confirm" title="Warning">' +
                '<p>Are you sure that want delete "' + name + '" from image?</p>' +
                '<p class="pnwim-dialog-warning">This operation can\'t be undone.</p>' +
                '</div>').dialog({
                modal: true,
                dialogClass: 'pnwim-dialog',
                open: function() {
                    $image.mapster('tooltip');
                    $().colorbox.settings.arrowKey = false;
                },
                close: function() {
                    $().colorbox.settings.arrowKey = pnwimCboxArrowKey;
                    $dialog.dialog('destroy').remove();
                },
                buttons: {
                    'Delete': function() {
                        pnwimDeleteData(pnwimMid, {
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
        var imgSelect = $image.imgAreaSelect({
            handles: false,
            show: true,
            instance: true,
            parent: $('#colorbox'),
            imageHeight: $image.naturalHeight(),
            imageWidth: $image.naturalWidth(),
            onSelectEnd: function(img, selection) {
                var $dialog = $('<div id="dialog-form" title="Information">' +
                    '<p>Enter individuals id or something else:</p>' +
                    '<input type="text" name="pid" class="ui-autocomplete-input ui-widget-content">' +
                    '</div>').dialog({
                    modal: true,
                    dialogClass: 'pnwim-dialog',
                    open: function() {
                        $image.mapster('tooltip');
                        $().colorbox.settings.arrowKey = false;
                        $('.pnwim-dialog').find('[name="pid"]').autocomplete({
                            source: function(request, response) {
                                $.getJSON('autocomplete.php', {
                                    field: 'INDI',
                                    term: request.term
                                }, response);
                            }
                        }).data('ui-autocomplete')._renderItem = function(ul, item) {
                            return $('<li></li>')
                                .data('item.autocomplete', item)
                                .append("<a>" + item.label + "</a>")
                                .appendTo(ul);
                        }
                    },
                    close: function() {
                        $().colorbox.settings.arrowKey = pnwimCboxArrowKey;
                        $dialog.dialog('destroy').remove();
                    },
                    buttons: {
                        'Add': function() {
                            var pid = $dialog.find('[name="pid"]').val();
                            if (pid) {
                                pnwimAddData(pnwimMid, {
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
                imgSelect.cancelSelection();
                imgSelect.remove();
            }
        });
    });
}

$(document).bind('cbox_complete', function() {
    var $target = $().colorbox.element();

    pnwimMid = new RegExp('[\?&]mid=([^&#]*)').exec($target.attr('href'))[1];
    $pnwimImage = $('img.cboxPhoto');

    if ($pnwimImage.length && pnwimMid) {
        pnwimGetData(pnwimMid);
    }
});

var $pnwimImage = null,
    pnwimMid = null,
    pnwimMode = 'mark',
    pnwimWheelZoomDisabled = null,
    pnwimCboxTrapFocusState = null,
    pnwimCboxArrowKey = null,
    pnwimTouchMode = new MobileDetect(window.navigator.userAgent).mobile();

$(document).bind('cbox_open', function() {
    pnwimWheelZoomDisabled = $.fn.wheelzoom;
    $.fn.wheelzoom = function() {
        //
    };
    pnwimCboxTrapFocusState = $.colorbox.settings.trapFocus;
    $.colorbox.settings.trapFocus = false;
    pnwimCboxArrowKey = $().colorbox.settings.arrowKey;
});

$(document).bind('cbox_cleanup', function() {
    $.fn.wheelzoom = pnwimWheelZoomDisabled;
    pnwimWheelZoomDisabled = null;
    $.colorbox.settings.trapFocus = pnwimCboxTrapFocusState;
    pnwimCboxTrapFocusState = null;
    pnwimCboxArrowKey = null;
    $('img.cboxPhoto').mapster('tooltip');
});
