var facesMid = null,
    facesMode = 'mark',
    facesTouchMode = new MobileDetect(window.navigator.userAgent).mobile() !== null;

function facesRoute(action) {
    return window.WT_FACES.routes.data.replace('FACES_ACTION', action);
}

function facesIndex(mid) {
    facesMid = mid;

    $.ajax({
        url: facesRoute('index'),
        type: 'GET',
        data: {
            mid: mid
        }
    }).done(function(response) {
        facesRender(response.map, response.edit, response.title, response.meta);
    });
}

function facesAttach(mid, data, exists) {
    $.ajax({
        url: facesRoute('attach'),
        type: 'POST',
        data: {
            mid: mid,
            pid: data.pid,
            coords: data.coords
        }
    }).done(function(response) {
        facesRefresh();

        if (exists && response.linker !== null) {
            $.ajax({
                url: response.linker.url,
                type: 'POST',
                data: response.linker.data,
            })
        }
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
    }).done(function() {
        facesRefresh();
    });
}

function facesRefresh() {
    var instance = $.fancybox.getInstance();

    instance.jumpTo(instance.currIndex);
}

function facesClean() {
    var instance = $.fancybox.getInstance();

    var $image = instance.$refs.stage.find('.fancybox-slide--current img.fancybox-image');

    $image.mapster('tooltip');
    $image.mapster('unbind');

    $('#faces-map').remove();
}

function facesRender(map, edit, title, meta) {
    var instance = $.fancybox.getInstance();

    var $caption = instance.$refs.caption.find('.fancybox-caption__body');
    var $image = instance.$refs.stage.find('.fancybox-slide--current img.fancybox-image');

    instance.$refs.container.toggleClass('faces-readonly', !(edit && !facesTouchMode));

    var mapName = 'faces-map-' + Date.now();
    var $map = $('<map/>', {
        id: 'faces-map',
        name: mapName,
    });

    var areas = [];
    var texts = [];

    $image.attr('usemap', '#' + mapName).after($map);

    $.each(map, function(_, item) {
        var link = item.link !== null ? item.link : '#';
        var key = encodeURIComponent(item.pid);

        $map.append($('<area/>', {
            shape: item.coords.length > 4 ? 'poly' : 'rect',
            coords: item.coords.join(','),
            href: link,
            'data-key': key,
        }));

        areas.push({
            key: key,
            toolTip: tmpl($('#faces-tooltip-template').html(), {
                person: item,
            }),
        });

        var text = tmpl($('#faces-person-template').html(), {
            person: item,
            key: key,
            link: link,
            edit: edit && !facesTouchMode,
        });

        texts.push(text);
    });

    $image.mapster({
        isSelectable: false,
        wrapClass: 'faces-mapster-wrapper',
        mapKey: 'data-key',
        fillColor: '000000',
        fillOpacity: 0,
        stroke: true,
        strokeColor: '3b5998',
        strokeWidth: 2,
        showToolTip: true,
        areas: areas,
        toolTipClose: facesTouchMode
            ? 'area-mouseout'
            : [
                'area-mouseout',
                'image-mouseout'
            ],
        onClick: facesTouchMode
            ? null
            : function(data) {
                var $target = $(data.e.target);
                var link = $target.attr('href');

                if (link !== '#') {
                    window.location = link;
                }

                return false;
            }
    });

    var $content = $('<div>');

    if (areas.length) {
        $content.html(texts.join(''));
    }

    if (meta !== null) {
        $content.prepend('<div class="faces-subtitle">' + meta + '</div>');
    }

    $content.prepend('<div class="faces-title">' + title + '</div>');

    $caption.empty();
    $caption.append($content);

    facesBindCaptionActions($image, instance);
    facesBindToolbarActions($image, instance);
}

function facesBindCaptionActions($image, instance) {
    instance.$refs.caption.find('.faces-person-name').on('mouseenter', function(e) {
        $image.mapster('highlight', $(e.target).data('key'));
    });

    instance.$refs.caption.find('.faces-person-name').on('mouseleave', function() {
        $image.mapster('highlight', false);
    });

    instance.$refs.caption.find('.faces-person-detach').on('click', function(e) {
        var $target = $(e.target);

        var $dialog = $(tmpl($('#faces-detach-modal-template').html(), {
            name: $target.data('name'),
        }));

        $dialog.on('shown.bs.modal', function() {
            $image.mapster('tooltip');
            $('.modal-backdrop').before($dialog);
        });

        $dialog.on('hidden.bs.modal', function() {
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
}

function facesBindToolbarActions($image, instance) {
    instance.$refs.toolbar.find('[data-fancybox-fzoom]').on('click', function() {
        alert('TODO: Zoom not implemented yet ;)');
        /*
        $image = facesWrap().find('img.cboxPhoto');
        if (facesMode === 'mark') {
            facesMode = 'zoom';
            facesEnableWheelZoom();
            wheelzoom($image);
            $('.faces-content-wrapper').addClass('faces-zoom-mode');
        } else {
            facesMode = 'mark';
            facesDisableWheelZoom();
            facesIndex(facesMid);
            $('.faces-content-wrapper').removeClass('faces-zoom-mode');
        }*/
    });

    instance.$refs.toolbar.find('[data-fancybox-fadd]').on('click', function() {
        instance.$refs.container.toggleClass('faces-select', true);

        var imgSelect = $image.imgAreaSelect({
            handles: false,
            autoHide: true,
            show: true,
            instance: true,
            parent: $image.parents('.fancybox-content'),
            imageHeight: $image.naturalHeight(),
            imageWidth: $image.naturalWidth(),
            onSelectEnd: function(img, selection) {
                instance.$refs.container.toggleClass('faces-select', false);

                var $dialog = $($('#faces-attach-modal-template').html());

                $dialog.on('shown.bs.modal', function() {
                    $image.mapster('tooltip');
                    $('.modal-backdrop').before($dialog);
                });

                $dialog.on('hidden.bs.modal', function() {
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
                    var $pid = $dialog.find('#faces-attach-pid').find(":selected");
                    if ($pid.length && $pid.val()) {
                        facesAttach(facesMid, {
                            pid: $pid.val(),
                            coords: [
                                selection.x1,
                                selection.y1,
                                selection.x2,
                                selection.y2,
                            ],
                        }, !$pid.is('[data-select2-tag="true"]'));
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

$.colorbox.remove();
$('body').off('click', 'a.gallery');

$.fancybox.defaults.btnTpl.fzoom = $.fancybox.defaults.btnTpl.zoom.replace(/zoom/g, 'fzoom');
$.fancybox.defaults.btnTpl.fadd = $.fancybox.defaults.btnTpl.close.replace(/close/g, 'fadd').replace("{{CLOSE}}", 'Add');

$().fancybox({
    selector: 'a[type^=image].gallery',
    baseClass: 'fancybox-faces-layout',
    infobar: false,
    protect: false,
    touch: {
        vertical: false,
    },
    buttons: [
        'close',
        'slideShow',
        'fzoom',
        'fadd',
    ],
    animationEffect: 'fade',
    transitionEffect: 'fade',
    preventCaptionOverlap: false,
    idleTime: false,
    gutter: 0,
    clickOutside: false,
    clickSlide: false,
    trapFocus: false, //TODO: Check this.
    clickContent: false, //Disable fancybox zoom
    wheel: false, //Disable mouse wheel for next/prev
    beforeShow: function() {
        facesClean();
    },
    afterShow: function(instance, current) {
        var mid = new RegExp('[\?&]xref=([^&#]*)').exec(current.src)[1];

        current.opts.caption = '<div class="fancybox-loading"></div>';
        instance.updateControls();

        facesIndex(mid);
    },
    beforeClose: function() {
        facesClean();
    },
});