# Photo Note With Image Map for webtrees

[![Latest Release](https://img.shields.io/github/release/UksusoFF/photo_note_with_image_map.svg)](https://github.com/UksusoFF/photo_note_with_image_map/releases/latest)
[![Code Climate](https://lima.codeclimate.com/github/UksusoFF/photo_note_with_image_map/badges/gpa.svg)](https://lima.codeclimate.com/github/UksusoFF/photo_note_with_image_map) [![Support Thread](https://img.shields.io/badge/support-forum-brightgreen.svg)](https://www.webtrees.net/index.php/en/forum/2-open-discussion/30219-how-to-mark-individuals-on-group-photo)

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

![pnwim](https://cloud.githubusercontent.com/assets/1931442/23299146/d33eb9d0-fa99-11e6-96f1-d07c89fc6f0f.png)

## Todo
* Clean up removed media from settings
* Save original photo title
* Create/delete relation to individuals
* Import/export maps
* Admin interface with settings listings
