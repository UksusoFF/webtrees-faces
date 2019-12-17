var $facesImage = null,
    facesMid = null,
    facesMode = 'mark',
    facesWheelZoomOriginal = null,
    facesCboxTrapFocusState = null,
    facesCboxArrowKeyState = null,
    facesTouchMode = new MobileDetect(window.navigator.userAgent).mobile();

function facesInstall() {
    facesWheelZoomOriginal = wheelzoom;
    wheelzoom = function() {
        //
    };
    facesCboxTrapFocusState = $.colorbox.settings.trapFocus;
    $.colorbox.settings.trapFocus = false;
    facesCboxArrowKeyState = $().colorbox.settings.arrowKey;
}

function facesClean() {
    wheelzoom = facesWheelZoomOriginal;
    facesWheelZoomOriginal = null;
    $.colorbox.settings.trapFocus = facesCboxTrapFocusState;
    facesCboxTrapFocusState = null;
    facesCboxArrowKeyState = null;
    $('img.cboxPhoto').mapster('tooltip');
}

function facesRoute(action) {
    return window.WT_FACES.routes.data.replace(/%25action/, action);
}

function facesIndex(mid) {
    $.ajax({
        url: facesRoute('index'),
        type: 'GET',
        data: {
            mid: mid
        }
    }).done(function(response) {
        facesRender(response.map, response.edit, response.title);
    });
}

function facesAttach(mid, data) {
    $.ajax({
        url: facesRoute('attach'),
        type: 'POST',
        data: {
            mid: mid,
            pid: data.pid,
            coords: data.coords
        }
    }).done(function(response) {
        facesRender(response.map, response.edit, response.title);
    });
}

function facesDetach(mid, data) {
    $.ajax({
        url: facesRoute('detach'),
        type: 'POST',
        data: {
            mid: mid,
            pid: data.pid
        }
    }).done(function(response) {
        facesRender(response.map, response.edit, response.title);
    });
}

function facesResetImage() {
    var indent = $facesImage.css('margin-top');

    return $('#cboxLoadedContent').html($facesImage.clone()).css({
        'padding-top': indent,
    }).find('img.cboxPhoto').css({
        'margin-top': 0,
    });
}

function facesRender(map, edit, title) {
    var $image = facesResetImage(),
        mapName = 'faces-map-' + Date.now(),
        $map = $('<map/>', {
            id: 'faces-map',
            name: mapName
        }),
        areas = [], texts = [];

    $image.attr('usemap', '#' + mapName).after($map);

    $.each(map, function(key, item) {
        var link = item.link !== null ? item.link : '#';
        $map.append($('<area/>', {
            shape: item.coords.length > 4 ? 'poly' : 'rect',
            coords: item.coords.join(','),
            href: link,
            'data-pid': item.pid,
            'data-found': item.link !== null
        }));
        var text = '<a href="' + link + '" class="faces-title-name" data-pid="' + item.pid + '">' + item.name + '</a>';
        areas.push({
            key: item.pid.toString(),
            toolTip: '<div class="faces-tooltip-wrapper">' +
                '<p class="faces-tooltip-name">' + text + '</p>' +
                '<p class="faces-tooltip-life">' + item.life + '</p>' +
                '</div>'
        });
        if (edit && !facesTouchMode) {
            text = text + '<span class="faces-detach-individual" data-pid="' + item.pid + '" data-name="' + item.name + '"> (&times;)</span>';
        }
        texts.push(text);
    });

    $image.mapster({
        isSelectable: false,
        wrapClass: 'faces-photo-wrapper',
        mapKey: 'data-pid',
        fillColor: '000000',
        fillOpacity: 0,
        stroke: true,
        strokeColor: '3b5998',
        strokeWidth: 2,
        showToolTip: true,
        areas: areas,
        toolTipClose: facesTouchMode ? 'area-mouseout' : ['area-mouseout', 'image-mouseout'],
        onClick: facesTouchMode ? null : function(data) {
            if ($(data.e.target).data('found')) {
                window.location = $(data.e.target).attr('href');
            }
            return false;
        }
    });
    var $container = $('#cboxTitle'),
        buttons = edit && !facesTouchMode ? [
            '<span class="faces-attach-individual"> (+) </span>'
        ] : [];

    buttons.push('<span class="faces-toggle-mode"> (zoom) </span>');

    if (areas.length && !facesTouchMode) {
        $container.html(texts.join('<span class="faces-title-separator">, </span>') + buttons.join(''));
    } else {
        $container.html(title + buttons.join(''));
    }
    facesBindActions($image, $container);
}

function facesBindActions($image, $container) {
    $container.find('.faces-title-name').on('mouseenter', function(e) {
        $image.mapster('highlight', $(e.target).data('pid').toString());
    });
    $container.find('.faces-title-name').on('mouseleave', function() {
        $image.mapster('highlight', false);
    });
    $container.find('.faces-toggle-mode').on('click', function() {
        $image = facesResetImage();
        if (facesMode === 'mark') {
            facesMode = 'zoom';
            wheelzoom($image);
            $('#cboxTitle').addClass('faces-zoom-mode');
        } else {
            facesMode = 'mark';
            facesIndex(facesMid);
            $('#cboxTitle').removeClass('faces-zoom-mode');
        }
    });
    $container.find('.faces-detach-individual').on('click', function(e) {
        var $target = $(e.target),
            name = $target.data('name');

        var $dialog = $($('#faces-detach-modal-template').html().replace(/%name/, name));

        $dialog.on('shown.bs.modal', function() {
            $image.mapster('tooltip');
            $().colorbox.settings.arrowKey = false;
            $('.modal-backdrop').before($dialog);
        });

        $dialog.on('hidden.bs.modal', function() {
            console.log($().colorbox.settings.arrowKey);
            $().colorbox.settings.arrowKey = facesCboxArrowKeyState;
            console.log($().colorbox.settings.arrowKey);
            $dialog.remove();
        });

        $dialog.find('#faces-detach-button').on('click', function() {
            var pid = $target.data('pid');
            if (pid) {
                facesDetach(facesMid, {
                    pid: pid
                });
            }
            $dialog.modal('hide');
        });

        $('body').append($dialog);

        $dialog.modal('show');
    });
    $container.find('.faces-attach-individual').on('click', function() {
        //Disable controls
        var imgSelect = $image.imgAreaSelect({
            handles: false,
            show: true,
            instance: true,
            parent: $('#colorbox'),
            imageHeight: $image.naturalHeight(),
            imageWidth: $image.naturalWidth(),
            onSelectEnd: function(img, selection) {
                var $dialog = $($('#faces-attach-modal-template').html());

                $dialog.on('shown.bs.modal', function() {
                    $image.mapster('tooltip');
                    $().colorbox.settings.arrowKey = false;
                    $('.modal-backdrop').before($dialog);
                });

                $dialog.on('hidden.bs.modal', function() {
                    console.log($().colorbox.settings.arrowKey);
                    $().colorbox.settings.arrowKey = facesCboxArrowKeyState;
                    console.log($().colorbox.settings.arrowKey);
                    $dialog.remove();
                });

                $dialog.find('select.select2').select2({
                    dropdownParent: $dialog,
                    width: '100%',
                    tags: true,
                    escapeMarkup: function(x) {
                        return x;
                    },
                });

                $dialog.find('#faces-attach-button').on('click', function() {
                    var pid = $dialog.find('#faces-attach-pid').find(":selected").val();
                    if (pid) {
                        facesAttach(facesMid, {
                            pid: pid,
                            coords: [
                                selection.x1,
                                selection.y1,
                                selection.x2,
                                selection.y2,
                            ]
                        });
                    }
                    $dialog.modal('hide');
                });

                $('body').append($dialog);

                $dialog.modal('show');

                imgSelect.cancelSelection();
                imgSelect.remove();
            }
        });
    });
}

$(document).bind('cbox_complete', function() {
    facesMid = new RegExp('[\?&]xref=([^&#]*)').exec($().colorbox.element().attr('href'))[1];
    $facesImage = $('img.cboxPhoto');

    if ($facesImage.length && facesMid) {
        facesIndex(facesMid);
    }
});

$(document).bind('cbox_open', function() {
    facesInstall();
});

$(document).bind('cbox_cleanup', function() {
    facesClean();
});
