<?php

namespace UksusoFF\WebtreesModules\PhotoNoteWithImageMap;

use Composer\Autoload\ClassLoader;
use Fisharebest\Webtrees\Controller\BaseController;
use Fisharebest\Webtrees\Database;
use Fisharebest\Webtrees\Filter;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleMenuInterface;
use Fisharebest\Webtrees\Theme;

class PhotoNoteWithImageMap extends AbstractModule implements ModuleMenuInterface
{
    /** @var string location of the fancy treeview module files */
    var $directory;

    public function __construct()
    {
        parent::__construct('modulename');
        $this->directory = WT_MODULES_DIR . $this->getName();
        $this->action = Filter::get('mod_action');

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

    public function getTitle()
    {
        return "Photo Note With Image Map";
    }

    /** {@inheritdoc} */
    public function getDescription()
    {
        return "This module integrate ImageMapster library with colorbox. " .
        "And provide way to mark peoples on group photo by placing image map in photo note. " .
        "For create image map you can use " .
        "<a href=\"http://comsquare.dynvpn.de/forums/viewtopic.php?f=40&t=107&sid=e4a24015e6636865ba2bbf49ba1b3c40\">Paint.NET MeasureSelection Plug-in</a>.";
    }

    /** {@inheritdoc} */
    public function modAction($mod_action)
    {
        switch ($mod_action) {
            case 'search_pids':
                global $WT_TREE;
                if (!$WT_TREE) {
                    echo json_encode([]);
                    break;
                }
                $pids = Filter::get('pids');
                foreach ($pids as $pid) {
                    $result[$pid] = [
                        'found' => false,
                        'pid' => $pid,
                        'name' => $pid,
                        'life' => '',
                    ];
                }
                $rows = Database::prepare(
                    "SELECT i_id AS xref, i_gedcom AS gedcom, n_full" .
                    " FROM `##individuals`" .
                    " JOIN `##name` ON i_id = n_id AND i_file = n_file" .
                    " WHERE i_id IN (" . implode(',', array_fill(0, count($pids), '?')) . ") AND i_file = ?" .
                    " AND n_type='NAME'" .
                    " ORDER BY n_full COLLATE ?"
                )->execute(array_merge($pids, [
                    $WT_TREE->getTreeId(),
                    I18N::collation(),
                ]))->fetchAll();
                foreach ($rows as $row) {
                    $person = Individual::getInstance($row->xref, $WT_TREE, $row->gedcom);
                    if ($person->canShowName()) {
                        $result[$row->xref] = [
                            'found' => true,
                            'pid' => $row->xref,
                            'name' => strip_tags($person->getFullName()),
                            'life' => strip_tags($person->getLifeSpan()),
                        ];
                    }
                }
                header('Content-type: application/json');
                echo json_encode($result);

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
				document.createStyleSheet("' . $module_dir . '/_css/common.css"); // For Internet Explorer
			} else {
				jQuery("head").append(\'<link rel="stylesheet" href="' . $module_dir . '/_css/common.css" type="text/css">\');
			}';
            $controller->addInlineJavascript($header, BaseController::JS_PRIORITY_LOW)
                ->addExternalJavascript($module_dir . '/_js/jquery.imagemapster.min.js')
                ->addExternalJavascript($module_dir . '/_js/jquery.imagemapster.init.js');
        }
        return null;
    }
}

return new PhotoNoteWithImageMap();