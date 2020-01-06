(function($) {

    var m = $.mapster,
        u = m.utils;

    function calcToolTipPosForRect(target, image, container, tooltip) {
        var coords = u.split(target.coords, parseInt);

        var startLeft = coords[0];
        var startTop = coords[1];

        var endLeft = coords[2];
        var endTop = coords[3];

        var targetW = endLeft - startLeft;

        var tooltipW = tooltip.outerWidth(true);
        var tooltipH = tooltip.outerHeight(true);

        var horizontalCenter = startLeft + targetW / 2;

        var tooltipLeft = 0;

        if ((horizontalCenter - tooltipW / 2) > 0) {
            tooltipLeft = horizontalCenter - tooltipW / 2;
        }

        if ((horizontalCenter + tooltipW / 2) > image.width) {
            tooltipLeft = image.width - tooltipW;
        }

        var tooltipTop = endTop;

        if ((tooltipTop + tooltipH) > image.height) {
            tooltipTop = startTop - tooltipH;
        }

        return [tooltipLeft, tooltipTop];
    }

    function createToolTip(html, template, css) {
        var tooltip;

        if (template) {
            tooltip = typeof template === 'string' ?
                $(template) :
                $(template).clone();

            tooltip.append(html);
        } else {
            tooltip = $(html);
        }

        tooltip.css($.extend((css || {}), {
            'display': 'block',
            'position': 'absolute',
            'opacity': 0
        }));

        $('.faces-mapster-wrapper').append(tooltip);

        return tooltip;
    }

    function showToolTip(tooltip, target, image, container, options) {
        if ($.isArray(target)) {
            target = target[0];
        }
        if (target && target.nodeName === 'AREA' && target.shape === 'rect') {
            var corners = calcToolTipPosForRect(
                target,
                image,
                $('.faces-mapster-wrapper'),
                tooltip
            );

            return tooltip.css({
                'left': corners[0] + 'px',
                'top': corners[1] + 'px',
                'opacity': 1
            });
        } else {
            console.error('Unexpected behavior!');
        }
    }

    function bindToolTipClose(options, bindOption, event, target, beforeClose, onClose) {
        var event_name = event + '.mapster-tooltip';

        if ($.inArray(bindOption, options) >= 0) {
            target.unbind(event_name)
                .bind(event_name, function(e) {
                    if (!beforeClose || beforeClose.call(this, e)) {
                        target.unbind('.mapster-tooltip');
                        if (onClose) {
                            onClose.call(this);
                        }
                    }
                });

            return {
                object: target,
                event: event_name
            };
        }
    }

    $.mapster.AreaData.prototype.showToolTip = function(content, options) {
        var tooltip, closeOpts, target, tipClosed, template,
            ttopts = {},
            ad = this,
            md = ad.owner,
            areaOpts = ad.effectiveOptions();

        options = options ? $.extend({}, options) : {};

        content = content || areaOpts.toolTip;
        closeOpts = options.closeEvents || areaOpts.toolTipClose || md.options.toolTipClose || 'tooltip-click';

        template = typeof options.template !== 'undefined' ? options.template : md.options.toolTipContainer;

        options.closeEvents = typeof closeOpts === 'string' ? closeOpts = u.split(closeOpts) : closeOpts;

        options.fadeDuration = options.fadeDuration || (md.options.toolTipFade ? (md.options.fadeDuration || areaOpts.fadeDuration) : 0);

        target = ad.area ?
            ad.area :
            $.map(ad.areas(),
                function(e) {
                    return e.area;
                });

        if (md.activeToolTipID === ad.areaId) {
            return;
        }

        md.clearToolTip();

        md.activeToolTip = tooltip = createToolTip(
            content,
            template,
            options.css
        );

        md.activeToolTipID = ad.areaId;

        tipClosed = function() {
            md.clearToolTip();
        };

        bindToolTipClose(closeOpts, 'area-click', 'click', $(md.map), null, tipClosed);
        bindToolTipClose(closeOpts, 'tooltip-click', 'click', tooltip, null, tipClosed);
        bindToolTipClose(closeOpts, 'image-mouseout', 'mouseout', $(md.image), function(e) {
            return (e.relatedTarget && e.relatedTarget.nodeName !== 'AREA' && e.relatedTarget !== ad.area);
        }, tipClosed);


        showToolTip(
            tooltip,
            target,
            md.image,
            options.container,
            template,
            options
        );

        u.ifFunction(md.options.onShowToolTip, ad.area, {
            toolTip: tooltip,
            options: ttopts,
            areaOptions: areaOpts,
            key: ad.key,
            selected: ad.isSelected()
        });

        return tooltip;
    };
}(jQuery));
