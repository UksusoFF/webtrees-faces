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
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers\DatabaseHelper as DB;
use UksusoFF\WebtreesModules\PhotoNoteWithImageMap\Helpers\JsonResponseHelper as Response;

class PhotoNoteWithImageMap extends AbstractModule implements ModuleMenuInterface, ModuleConfigInterface
{
    const CUSTOM_VERSION			 = '2.0';
    const CUSTOM_WEBSITE			 = 'https://github.com/UksusoFF/photo_note_with_image_map';

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

    /** {@inheritdoc} */
    public function modAction($mod_action)
    {
        global $WT_TREE;
        if (empty($WT_TREE)) {
            http_response_code(404);
        }
        switch ($mod_action) {
            case 'admin_config':
                //TODO: Implement all image maps listing.
                break;
            case 'map':
                if (Filter::post('_method') == 'save' && Filter::post('mid') !== null && Filter::post('map') !== null) {
                    $mid = Filter::post('mid');
                    $this->setSetting('PNWIM_' . Filter::post('mid'), json_encode(Filter::post('map')));
                } elseif (Filter::get('_method') == 'get' && Filter::get('mid') !== null) {
                    $mid = Filter::get('mid');
                } else {
                    http_response_code(404);
                    break;
                }
                $media = Media::getInstance($mid, $WT_TREE);
                $can_edit = $media->canEdit();
                if (!empty($media) && (
                        Filter::get('_method') == 'get' && $media->canShow() ||
                        Filter::post('_method') == 'save' && $can_edit
                    )
                ) {
                    $result = [];
                    $map = json_decode($this->getSetting('PNWIM_' . $mid, '[]'), true);
                    foreach ($map as $area) {
                        $result[$area['pid']] = [
                            'found' => false,
                            'pid' => $area['pid'],
                            'name' => $area['pid'],
                            'life' => '',
                            'coords' => $area['coords'],
                        ];
                    }
                    if (!empty($result)) {
                        foreach (DB::getIndividualsDataByTreeAndPids($WT_TREE, array_keys($result)) as $row) {
                            $person = Individual::getInstance($row->xref, $WT_TREE, $row->gedcom);
                            if ($person->canShowName()) {
                                $result[$row->xref] = array_merge($result[$row->xref], [
                                    'found' => true,
                                    'name' => strip_tags($person->getFullName()),
                                    'life' => strip_tags($person->getLifeSpan()),
                                ]);
                            }
                        }
                    }
                    Response::success([
                        'map' => $result,
                        'edit' => $can_edit,
                    ]);
                } else {
                    Response::fail([
                        'map' => null,
                    ]);
                }
                break;
            default:
                http_response_code(404);
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
                ->addExternalJavascript($module_dir . '/_js/lib/jquery.imagemapster.min.js')
                ->addExternalJavascript($module_dir . '/_js/lib/jquery.imgareaselect.min.js')
                ->addExternalJavascript($module_dir . '/_js/lib/jquery.naturalprops.js')
                ->addExternalJavascript($module_dir . '/_js/module.js')
                ->addExternalJavascript($module_dir . '/_js/module.hidemap.js');
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