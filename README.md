# photo_note_with_image_map

This module integrate [ImageMapster](http://www.outsharked.com/imagemapster/) library with [webtrees](https://www.webtrees.net/).

And provide way to mark peoples on group photo by placing image map in photo note.

Tested with 1.7.9 version and Webtrees Theme, [JustLight Theme](http://www.justcarmen.nl/themes/justlight-theme/), [JustBlack Theme](https://github.com/JustCarmen/justblack).

## Installation
1. Download the [latest release](https://github.com/UksusoFF/photo_note_with_image_map/releases/latest).
2. Upload the downloaded file to your webserver.
3. Unzip the package into your `webtrees/modules_v3` directory.
4. Rename the folder to `photo_note_with_image_map`.
5. Go to the control panel (admin section) => Module administration => Enable the `Photo Note With Image Map` module and save your settings.

## Usage 

For create image map you can use [Paint.NET MeasureSelection Plug-in](http://comsquare.dynvpn.de/forums/viewtopic.php?f=40&t=107&sid=e4a24015e6636865ba2bbf49ba1b3c40).

You can use person id that exist in tree or just any string for others mark.

This is image map example:

```
<map name="map">
    <area shape="rect" coords="450, 192, 516, 288" href="#" data-pid="I1"/>
    <area shape="rect" coords="566, 162, 667, 286" href="#" data-pid="Person Name"/>
</map>
```

![pnwim-sample](https://cloud.githubusercontent.com/assets/1931442/22397799/f9a4768a-e592-11e6-9d3b-2c4cd5dc43d1.png)
