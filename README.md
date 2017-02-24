# Photo Note With Image Map for webtrees

This module integrate [ImageMapster](http://www.outsharked.com/imagemapster/) and [imgAreaSelect](http://odyniec.net/projects/imgareaselect/) libraries with [webtrees](https://www.webtrees.net/).

And provide easy way to mark people on group photo.

Tested with 1.7.9 version and Webtrees Theme, [JustLight Theme](http://www.justcarmen.nl/themes/justlight-theme/), [JustBlack Theme](https://github.com/JustCarmen/justblack).

## Warning

All data stored in module settings and can't be exported as part of GEDCOM files.

## Installation
1. Download the [latest release](https://github.com/UksusoFF/photo_note_with_image_map/releases/latest).
2. Upload the downloaded file to your webserver.
3. Unzip the package into your `webtrees/modules_v3` directory.
4. Rename the folder to `photo_note_with_image_map`.
5. Go to the control panel (admin section) => Module administration => Enable the `Photo Note With Image Map` module and save your settings.

## Usage

For mark people on image you must click by (+) button, select area and enter something id.

As id you can enter person id that exist in tree (like I1) or just any string for others mark.

## Result

![pnwim](https://cloud.githubusercontent.com/assets/1931442/23298995/3db6376c-fa99-11e6-815a-b0ca855296c6.png)

## Todo
* &#10003; Sort individuals by coordinates
* Clean up removed media from settings
* Save original photo title
* Create/delete relation to individuals
* Import/export maps
