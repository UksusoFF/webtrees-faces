# photo_note_with_image_map

This module integrate [ImageMapster](http://www.outsharked.com/imagemapster/) library with [webtrees](https://www.webtrees.net/).

Tested with 1.7.9 version and WebtreesTheme, [JustLightTheme](http://www.justcarmen.nl/themes/justlight-theme/).

And provide way to mark peoples on group photo by placing image map in photo note.

For create image map you can use [Paint.NET MeasureSelection Plug-in](http://comsquare.dynvpn.de/forums/viewtopic.php?f=40&t=107&sid=e4a24015e6636865ba2bbf49ba1b3c40).

You can use person id that exist in tree or just any string for others mark.

This is image map example:

```
<map name="map">
    <area shape="rect" coords="818, 416, 1661, 1541" href="#" data-pid="I123"/>
    <area shape="rect" coords="3191, 664, 4034, 1789" href="#" data-pid="Person Name"/>
</map>
```
