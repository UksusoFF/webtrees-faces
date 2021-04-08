var facesMid = null,
    facesFact = null,
    facesMode = 'mark',
    facesIsMobile = new MobileDetect(window.navigator.userAgent).mobile() !== null;

function facesRoute(controller, action) {
    return window.WT_FACES.routes[controller].replace('FACES_ACTION', action);
}

function facesIndex(mid, fact) {
    facesMid = mid;
    facesFact = fact;

    $.ajax({
        url: facesRoute('data', 'index'),
        type: 'GET',
        data: {
            mid: mid,
            fact: fact,
        }
    }).done(function(response) {
        facesRenderPeoples(response.map, response.edit, response.title, response.meta);
    });
}

function facesAttach(mid, fact, data, exists) {
    $.ajax({
        url: facesRoute('data', 'attach'),
        type: 'POST',
        data: {
            mid: mid,
            fact: fact,
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

function facesDetach(mid, fact, data) {
    $.ajax({
        url: facesRoute('data', 'detach'),
        type: 'POST',
        data: {
            mid: mid,
            fact: fact,
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

function facesClean(instance) {
    var $image = instance.$refs.stage.find('.fancybox-slide--current img.fancybox-image');

    if ($image.length) {
        $image.mapster('tooltip');
        $image.mapster('unbind');
        if ($image.parents('pinch-zoom').length) {
            $image.unwrap('pinch-zoom');
        }
    }

    $('#faces-map').remove();
}

function facesRenderPeoples(map, edit, title, meta) {
    var instance = $.fancybox.getInstance();

    var $caption = instance.$refs.caption.find('.fancybox-caption__body');
    var $image = instance.$refs.stage.find('.fancybox-slide--current img.fancybox-image');

    instance.$refs.container.toggleClass('faces-readonly', !(edit && !facesIsMobile));

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
            edit: edit && !facesIsMobile,
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
        toolTipContainer: '<div class="faces-tooltip"></div>',
        toolTipClose: facesIsMobile
            ? 'area-mouseout'
            : [
                'area-mouseout',
                'image-mouseout'
            ],
        onClick: facesIsMobile
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

    $.each(meta, function(_, items) {
        $.each(items, function(_, item) {
            $content.prepend('<div class="faces-subtitle">' + item + '</div>');
        });
    });

    $content.prepend('<div class="faces-title">' + title + '</div>');

    $caption.empty();
    $caption.append($content);

    facesBindCaptionActions($image, instance);
    facesBindToolbarActions($image, instance);
}

function facesRenderZoom() {
    var instance = $.fancybox.getInstance();

    var $image = instance.$refs.stage.find('.fancybox-slide--current img.fancybox-image');

    $image.wrap("<pinch-zoom></pinch-zoom>");

    facesBindToolbarActions($image, instance);
}

function facesBindCaptionActions($image, instance) {
    instance.$refs.caption.find('.faces-person-name').on('mouseenter', function(e) {
        var key = $(e.target).data('key');

        $image.mapster('highlight', key);
        $image.mapster('tooltip', key);
    });

    instance.$refs.caption.find('.faces-person-name').on('mouseleave', function() {
        $image.mapster('highlight', false);
        $image.mapster('tooltip', false);
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
                facesDetach(
                    facesMid,
                    facesFact,
                    {
                        pid: pid
                    }
                );
            }
            $dialog.modal('hide');
        });

        $('body').append($dialog);

        $dialog.modal('show');
    });
}

function facesBindToolbarActions($image, instance) {
    instance.$refs.toolbar.find('[data-fancybox-fzoom]').off('click').on('click', function() {
        facesMode = facesMode === 'mark' ? 'zoom' : 'mark';

        facesRefresh();
    });

    instance.$refs.toolbar.find('[data-fancybox-fconfig]').off('click').on('click', function() {
        var route = facesRoute('admin', 'config');

        var symbol = (route.indexOf('?') > 0) ? '&' : '?';

        window.location = route + symbol + 'mid=' + facesMid;
    });

    instance.$refs.toolbar.find('[data-fancybox-fadd]').off('click').on('click', function() {
        instance.$refs.container.toggleClass('faces-select', true);

        var imgSelect = $image.imgAreaSelect({
            handles: false,
            autoHide: true,
            show: true,
            instance: true,
            parent: $image.parents('.fancybox-content'),
            imageHeight: $image.get(0).naturalHeight,
            imageWidth: $image.get(0).naturalWidth,
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
                        facesAttach(
                            facesMid,
                            facesFact,
                            {
                                pid: $pid.val(),
                                coords: [
                                    selection.x1,
                                    selection.y1,
                                    selection.x2,
                                    selection.y2,
                                ],
                            },
                            !$pid.is('[data-select2-tag="true"]')
                        );
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
$.fancybox.defaults.btnTpl.fconfig = '<button data-fancybox-fconfig class="fancybox-button fancybox-button--fconfig" title="Settings" style="padding: 14px;">' +
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M507.73 109.1c-2.24-9.03-13.54-12.09-20.12-5.51l-74.36 74.36-67.88-11.31-11.31-67.88 74.36-74.36c6.62-6.62 3.43-17.9-5.66-20.16-47.38-11.74-99.55.91-136.58 37.93-39.64 39.64-50.55 97.1-34.05 147.2L18.74 402.76c-24.99 24.99-24.99 65.51 0 90.5 24.99 24.99 65.51 24.99 90.5 0l213.21-213.21c50.12 16.71 107.47 5.68 147.37-34.22 37.07-37.07 49.7-89.32 37.91-136.73zM64 472c-13.25 0-24-10.75-24-24 0-13.26 10.75-24 24-24s24 10.74 24 24c0 13.25-10.75 24-24 24z"></path></svg>' +
    '</button>';

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
        'fconfig',
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
    beforeLoad: function(instance) {
        instance.$refs.container.toggleClass('faces-zoom', facesMode !== 'mark');
    },
    beforeClose: function(instance) {
        facesClean(instance);
    },
    beforeShow: function(instance) {
        instance.$refs.container.toggleClass('faces-zoom', facesMode !== 'mark');

        facesClean(instance);
    },
    afterShow: function(instance, current) {
        var mid = new RegExp('[\?&]xref=([^&#]*)').exec(current.src)[1];
        var fact = new RegExp('[\?&]fact_id=([^&#]*)').exec(current.src)[1];

        if (facesMode === 'mark') {
            current.opts.caption = '<div class="fancybox-loading"></div>';
            instance.updateControls();

            facesIndex(mid, fact);
        } else {
            facesRenderZoom();
        }
    },
});