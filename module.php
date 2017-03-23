<?php

namespace UksusoFF\WebtreesModules\PhotoNoteWithImageMap;

use Composer\Autoload\ClassLoader;
use Fisharebest\Webtrees\Controller\BaseController;
use Fisharebest\Webtrees\Filter;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleMenuInterface;
use Fisharebest\Webtrees\Theme;
use Fisharebest\Webtrees\Tree;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers\DatabaseHelper as DB;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers\JsonResponseHelper as Response;

class PhotoNoteWithImageMap extends AbstractModule implements ModuleMenuInterface, ModuleConfigInterface
{
    const CUSTOM_VERSION = '2.1.8';
    const CUSTOM_WEBSITE = 'https://github.com/UksusoFF/photo_note_with_image_map';

    var $directory;
    var $path;

    public function __construct()
    {
        parent::__construct('photo_note_with_image_map');

        $this->directory = WT_MODULES_DIR . $this->getName();
        $this->path = WT_STATIC_URL . WT_MODULES_DIR . $this->getName();

        // register the namespaces
        $loader = new ClassLoader();
        $loader->addPsr4('UksusoFF\\WebtreesModules\\PhotoNoteWithImageMap\\', $this->directory);
        $loader->register();
    }

    /* ****************************
     * Module configuration
     * ****************************/

    /** {@inheritdoc} */
    public function getName()
    {
        // warning: Must match (case-sensitive!) the directory name!
        return "photo_note_with_image_map";
    }

    /** {@inheritdoc} */
    public function getTitle()
    {
        return "Photo Note With Image Map";
    }

    /** {@inheritdoc} */
    public function getDescription()
    {
        return "This module integrate ImageMapster and imgAreaSelect libraries with webtrees. " .
        "And provide easy way to mark people on group photo.";
    }

    /**
     * @param Media $media
     * @return mixed
     */
    private function getMediaMap(Media $media)
    {
        return json_decode($this->getSetting('PNWIM_' . $media->getXref(), '[]'), true);
    }

    /**
     * @param Media $media
     * @param $map
     */
    private function setMediaMap(Media $media, $map)
    {
        $this->setSetting('PNWIM_' . $media->getXref(), json_encode($map));
    }

    /**
     * @param Media $media
     * @param Tree $tree
     * @return array
     */
    private function presentMediaMapForTree(Media $media, Tree $tree)
    {
        $result = [];
        $pids = [];
        foreach ($this->getMediaMap($media) as $area) {
            $pid = (string)$area['pid'];
            $result[$pid] = [
                'found' => false,
                'pid' => $pid,
                'name' => $pid,
                'life' => '',
                'coords' => $area['coords'],
            ];
            $pids[] = $pid;
        }
        if (!empty($result)) {
            foreach (DB::getIndividualsDataByTreeAndPids($tree, $pids) as $row) {
                $person = Individual::getInstance($row->xref, $tree, $row->gedcom);
                if ($person->canShowName()) {
                    $result[$row->xref] = array_merge($result[$row->xref], [
                        'found' => true,
                        'name' => strip_tags($person->getFullName()),
                        'life' => strip_tags($person->getLifeSpan()),
                    ]);
                }
            }
            usort($result, function($compa, $compb) {
                return $compa['coords'][0] - $compb['coords'][0];
            });
        }
        return $result;
    }

    /**
     * @param Media $media
     * @return string
     */
    private function presentMediaTitle(Media $media)
    {
        if ($title = $media->getTitle()) {
            return $title;
        }
        $parsedFileName = pathinfo($media->getFilename());
        if (!empty($parsedFileName['filename'])) {
            return $parsedFileName['filename'];
        }
        return $media->getFilename();
    }

    /** {@inheritdoc} */
    public function modAction($modAction)
    {
        global $WT_TREE;
        $tree = $WT_TREE;
        if (empty($tree)) {
            return http_response_code(404);
        }
        $mid = Filter::get('mid');
        if (!$mid) {
            $mid = Filter::post('mid');
        }
        $media = Media::getInstance($mid, $tree);
        switch ($modAction) {
            case 'map_delete':
                if ($media && $media->canEdit() && Filter::post('pid') !== null) {
                    $pid = Filter::post('pid');
                    $map = array_filter($this->getMediaMap($media), function ($area) use ($pid) {
                        return !empty($area['pid']) && $area['pid'] != $pid;
                    });
                    $this->setMediaMap($media, $map);
                    Response::success([
                        'title' => $this->presentMediaTitle($media),
                        'map' => $this->presentMediaMapForTree($media, $tree),
                        'edit' => $media->canEdit(),
                    ]);
                }
                break;
            case 'map_add':
                if ($media && $media->canEdit() && Filter::post('pid') !== null && Filter::post('coords') !== null) {
                    $map = $this->getMediaMap($media);
                    $map[] = (object)[
                        'pid' => Filter::post('pid'),
                        'coords' => Filter::post('coords'),
                    ];
                    $this->setMediaMap($media, $map);
                    Response::success([
                        'title' => $this->presentMediaTitle($media),
                        'map' => $this->presentMediaMapForTree($media, $tree),
                        'edit' => $media->canEdit(),
                    ]);
                }
                break;
            case 'map_get':
                if ($media && $media->canShow()) {
                    Response::success([
                        'title' => $this->presentMediaTitle($media),
                        'map' => $this->presentMediaMapForTree($media, $tree),
                        'edit' => $media->canEdit(),
                    ]);
                }
                break;
            case 'autocomplete':
                $data = [];
                foreach (DB::getIndividualsIdByTreeAndTerm($tree, Filter::get('term')) as $row) {
                    $person = Individual::getInstance($row->xref, $tree, $row->gedcom);
                    if ($person->canShowName()) {
                        $data[] = [
                            'value' => $row->xref,
                            'label' => strip_tags($person->getFullName()),
                        ];
                    }
                }
                Response::success([
                    'data' => $data,
                ]);
                break;
            default:
                return http_response_code(404);
        }
    }

    /** {@inheritdoc} */
    public function defaultMenuOrder()
    {
        return 9999;
    }

    /** {@inheritdoc} */
    public function getMenu()
    {
        // We don't actually have a menu - this is just a convenient "hook" to execute
        // code at the right time during page execution
        global $controller;

        if (Theme::theme()->themeId() !== '_administration') {
            $header = 'if (document.createStyleSheet) {
				document.createStyleSheet("' . $this->path . '/_css/module.css"); // For Internet Explorer
			} else {
				jQuery("head").append(\'<link rel="stylesheet" href="' . $this->path . '/_css/module.css" type="text/css">\');
			}';
            $controller->addInlineJavascript($header, BaseController::JS_PRIORITY_LOW)
                ->addExternalJavascript('https://cdnjs.cloudflare.com/ajax/libs/mobile-detect/1.3.5/mobile-detect.min.js')
                ->addExternalJavascript($this->path . '/_js/lib/jquery.imagemapster.min.js')
                ->addExternalJavascript($this->path . '/_js/lib/jquery.imgareaselect.min.js')
                ->addExternalJavascript($this->path . '/_js/lib/jquery.naturalprops.js')
                ->addExternalJavascript($this->path . '/_js/lib/wheelzoom.js')
                ->addExternalJavascript($this->path . '/_js/module.js?v=' . self::CUSTOM_VERSION);
        }
        return null;
    }

    /** {@inheritdoc} */
    public function getConfigLink()
    {
        return '#';
    }
}

return new PhotoNoteWithImageMap();