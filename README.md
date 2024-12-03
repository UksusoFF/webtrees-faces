# Faces for [webtrees](https://www.webtrees.net/)

[![Latest Release](https://img.shields.io/github/release/UksusoFF/webtrees-faces.svg)](https://github.com/UksusoFF/webtrees-faces/releases/latest)
[![Support Thread](https://img.shields.io/badge/support-forum-brightgreen.svg)](https://www.webtrees.net/index.php/en/forum/2-open-discussion/30219-how-to-mark-individuals-on-group-photo)

This module provides an easy way to mark people on a group photo.

## Warning

All data is stored in the database and can't be exported as part of GEDCOM files.

## System requirements
Same as [webtrees#system-requirements](https://github.com/fisharebest/webtrees#system-requirements).

Works with 2.1.x and 2.2.x version and bundled themes.

## Installation
1. Make a backup of the database
1. Download the [latest release](https://github.com/UksusoFF/webtrees-faces/releases/latest)
1. Upload the downloaded file to your web server
1. Unzip the package into your `webtrees/modules_v4` directory
1. Rename the folder to `faces`

### Old version
For webtrees 1.7.x, you can use [version 2.2.1](https://github.com/UksusoFF/webtrees-faces/releases/tag/v2.2.1).

For webtrees 2.0.x, you can use [version 2.6.8](https://github.com/UksusoFF/webtrees-faces/releases/tag/v2.6.8).

## Usage

To mark people on an image, click the plus (+) button, select area around their face, then enter either their ID for those on the tree, or their name if they are not on the tree.

### Google Picasa

Module can show Google Picasa face tags.

This feature is disabled by default. To enable go to the module settings and check "Read XMP data".

Read more:
* [fisharebest/webtrees/issues/744](https://github.com/fisharebest/webtrees/issues/744)
* [AvPicFaceXmpTagger](http://www.anvo-it.de/wiki/avpicfacexmptagger:main)
* [XMP, IPTC/IIM, or Exif; which is preferred?](https://www.carlseibert.com/xmp-iptciim-or-exif-which-is-preferred/)

### MyHeritage Family Tree Builder

If you wish import data from [MyHeritage Family Tree Builder](https://www.myheritage.com/family-tree-builder) please check out this additional script [miqrogroove/face-tag-import](https://github.com/miqrogroove/face-tag-import). 

## Result

![faces_screenshot](/SCREENSHOT.png?raw=true)

## Todo
* Delete relation to individuals
* Import/export module data
* Import from MyHeritage Family Tree Builder ([#1714](https://github.com/fisharebest/webtrees/issues/1714), [#2358](https://github.com/fisharebest/webtrees/issues/2358))

## Vendor dependencies
* [ImageMapster](https://github.com/jamietre/imagemapster) 
* [imgAreaSelect](https://github.com/odyniec/imgareaselect)
* [mobile-detect.js](https://github.com/hgoebl/mobile-detect.js)
