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
    const CUSTOM_VERSION = '2.1.2';
    const CUSTOM_WEBSITE = 'https://github.com/UksusoFF/photo_note_with_image_map';

    var $directory;

    public function __construct()
    {
        parent::__construct('photo_note_with_image_map');

        $this->directory = WT_MODULES_DIR . $this->getName();

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
        foreach ($this->getMediaMap($media) as $area) {
            $result[$area['pid']] = [
                'found' => false,
                'pid' => $area['pid'],
                'name' => $area['pid'],
                'life' => '',
                'coords' => $area['coords'],
            ];
        }
        if (!empty($result)) {
            foreach (DB::getIndividualsDataByTreeAndPids($tree, array_keys($result)) as $row) {
                $person = Individual::getInstance($row->xref, $tree, $row->gedcom);
                if ($person->canShowName()) {
                    $result[$row->xref] = array_merge($result[$row->xref], [
                        'found' => true,
                        'name' => strip_tags($person->getFullName()),
                        'life' => strip_tags($person->getLifeSpan()),
                    ]);
                }
            }
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
        $parsed_file_name = pathinfo($media->getFilename());
        if (!empty($parsed_file_name['filename'])) {
            return $parsed_file_name['filename'];
        }
        return $media->getFilename();
    }

    /** {@inheritdoc} */
    public function modAction($mod_action)
    {
        global $WT_TREE;
        if (empty($WT_TREE)) {
            return http_response_code(404);
        }
        $mid = Filter::get('mid');
        if (!$mid) {
            $mid = Filter::post('mid');
        }
        $media = Media::getInstance($mid, $WT_TREE);
        switch ($mod_action) {
            case 'admin_config':
                //TODO: Implement all image maps listing.
                break;
            case 'map_delete':
                if ($media && $media->canEdit() && Filter::post('pid') !== null) {
                    $pid = Filter::post('pid');
                    $map = array_filter($this->getMediaMap($media), function ($area) use ($pid) {
                        return !empty($area['pid']) && $area['pid'] != $pid;
                    });
                    $this->setMediaMap($media, $map);
                    Response::success([
                        'title' => $this->presentMediaTitle($media),
                        'map' => $this->presentMediaMapForTree($media, $WT_TREE),
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
                        'map' => $this->presentMediaMapForTree($media, $WT_TREE),
                        'edit' => $media->canEdit(),
                    ]);
                }
                break;
            case 'map_get':
                if ($media && $media->canShow()) {
                    Response::success([
                        'title' => $this->presentMediaTitle($media),
                        'map' => $this->presentMediaMapForTree($media, $WT_TREE),
                        'edit' => $media->canEdit(),
                    ]);
                }
                break;
            case 'autocomplete':
                $data = [];
                foreach (DB::getIndividualsIdByTreeAndTerm($WT_TREE, Filter::get('term')) as $row) {
                    $person = Individual::getInstance($row->xref, $WT_TREE, $row->gedcom);
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
            $module_dir = WT_STATIC_URL . WT_MODULES_DIR . $this->getName();
            $header = 'if (document.createStyleSheet) {
				document.createStyleSheet("' . $module_dir . '/_css/module.css"); // For Internet Explorer
			} else {
				jQuery("head").append(\'<link rel="stylesheet" href="' . $module_dir . '/_css/module.css" type="text/css">\');
			}';
            $controller->addInlineJavascript($header, BaseController::JS_PRIORITY_LOW)
                ->addExternalJavascript('https://cdnjs.cloudflare.com/ajax/libs/mobile-detect/1.3.5/mobile-detect.min.js')
                ->addExternalJavascript($module_dir . '/_js/lib/jquery.imagemapster.min.js')
                ->addExternalJavascript($module_dir . '/_js/lib/jquery.imgareaselect.min.js')
                ->addExternalJavascript($module_dir . '/_js/lib/jquery.naturalprops.js')
                ->addExternalJavascript($module_dir . '/_js/module.js?v=' . self::CUSTOM_VERSION);
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