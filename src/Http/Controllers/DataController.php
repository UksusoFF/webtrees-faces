<?php

namespace UksusoFF\WebtreesModules\Faces\Http\Controllers;

use Exception;
use Fisharebest\Webtrees\Exceptions\HttpNotFoundException;
use Fisharebest\Webtrees\Fact;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Webtrees;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use UksusoFF\WebtreesModules\Faces\Helpers\ExifHelper;
use UksusoFF\WebtreesModules\Faces\Modules\FacesModule;

class DataController implements RequestHandlerInterface
{
    public const ROUTE_PREFIX = 'faces-data';

    protected $module;

    public function __construct(FacesModule $module)
    {
        $this->module = $module;
    }

    public function handle(Request $request): Response
    {
        try {
            $tree = $this->getTree($request);
            $media = $this->getMedia($request, $tree);

            switch ($request->getAttribute('action')) {
                case 'index':
                    return $this->index($request, $tree, $media);
                case 'attach':
                    return $this->attach($request, $tree, $media);
                case 'detach':
                    return $this->detach($request, $tree, $media);
                default:
                    throw new HttpNotFoundException();
            }
        } catch (Exception $e) {
            return response([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function index(Request $request, Tree $tree, Media $media): Response
    {
        if (!$media->canShow()) {
            throw new HttpNotFoundException();
        }

        return response([
            'success' => true,
            'title' => $this->getMediaTitle($media),
            'meta' => $this->module->settingEnabled(FacesModule::SETTING_META_NAME)
                ? $this->getMediaMeta($media)
                : [],
            'map' => $this->getMediaMapForTree($tree, $media),
            'edit' => $media->canEdit(),
        ]);
    }

    private function attach(Request $request, Tree $tree, Media $media): Response
    {
        if (!$media->canEdit()) {
            throw new HttpNotFoundException();
        }

        $pid = $request->getParsedBody()['pid'] ?? null;
        $coords = $request->getParsedBody()['coords'] ?? null;

        if ($pid === null || $coords === null) {
            throw new HttpNotFoundException();
        }

        $map = $this->getMediaMap($tree, $media);

        $map[] = (object)[
            'pid' => $pid,
            'coords' => $coords,
        ];

        $this->setMediaMap($media, $map);

        return response([
            'success' => true,
            'linker' => $this->module->settingEnabled(FacesModule::SETTING_LINKING_NAME)
                ? [
                    'url' => route('update-fact', [
                        'tree' => $tree->name(),
                        'xref' => $pid,
                    ]),
                    'data' => [
                        'glevels' => [1],
                        'islink' => [1],
                        'tag' => ['OBJE'],
                        'text' => [$media->xref()],
                    ],
                ]
                : null,
        ]);
    }

    private function detach(Request $request, Tree $tree, Media $media): Response
    {
        if (!$media->canEdit()) {
            throw new HttpNotFoundException();
        }

        $pid = $request->getParsedBody()['pid'] ?? null;

        if ($pid === null) {
            throw new HttpNotFoundException();
        }

        $map = array_filter($this->getMediaMap($tree, $media), function($area) use ($pid) {
            return !empty($area['pid']) && $area['pid'] !== $pid;
        });

        $this->setMediaMap($media, $map);

        return response([
            'success' => true,
        ]);
    }

    private function getTree(Request $request): Tree
    {
        $tree = $request->getAttribute('tree');

        if (!($tree instanceof Tree)) {
            throw new HttpNotFoundException();
        }

        return $tree;
    }

    private function getMedia(Request $request, Tree $tree): Media
    {
        $mid = $request->getQueryParams()['mid'] ?? $request->getParsedBody()['mid'];

        if ($mid === null) {
            throw new HttpNotFoundException();
        }

        return Media::getInstance($mid, $tree);
    }

    private function getMediaMapForTree(Tree $tree, Media $media): array
    {
        $result = [];
        $pids = [];
        $areas = $this->getMediaMap($tree, $media);

        foreach ($areas as $area) {
            $pid = (string)$area['pid'];
            $result[$pid] = [
                'link' => null,
                'pid' => $pid,
                'name' => $pid,
                'life' => '',
                'coords' => $area['coords'],
            ];
            $pids[] = $pid;
        }

        if (!empty($result)) {
            foreach ($this->module->query->getIndividualsDataByTreeAndPids($tree->id(), $pids) as $row) {
                $person = Individual::getInstance($row->xref, $tree, $row->gedcom);
                if ($person === null) {
                    continue;
                }

                $public = $person->canShowName();

                $result[$row->xref] = array_merge($result[$row->xref], [
                    'link' => $public
                        ? $person->url()
                        : null,
                    'name' => $public
                        ? strip_tags($person->fullName())
                        : I18N::translate('Private'),
                    'life' => $public
                        ? strip_tags($person->lifespan())
                        : '',
                ]);
            }
            usort($result, function($compa, $compb) {
                return $compa['coords'][0] - $compb['coords'][0];
            });
        }

        return $result;
    }

    private function getMediaTitle(Media $media): string
    {
        if (($file = $media->firstImageFile()) === null) {
            throw new HttpNotFoundException();
        }

        return !empty($file->title())
            ? $file->title()
            : $file->filename();
    }

    private function getMediaMeta(Media $media): array
    {
        return $media->linkedIndividuals('OBJE')
            ->flatMap(function(Individual $individual) use ($media) {
                return $individual
                    ->facts()
                    ->filter(function(Fact $fact) use ($media) {
                        return collect($fact->getMedia())->filter(function(Media $m) use ($media) {
                            return $media->xref() === $m->xref();
                        })->isNotEmpty();
                    });
            })
            ->map(function(Fact $fact) {
                return array_filter([
                    $fact->attribute('PLAC'),
                    $fact->attribute('DATE'),
                ]);
            })
            ->toArray();
    }

    private function getMediaMap(Tree $tree, Media $media): array
    {
        if (($map = $this->module->query->getMediaMap($media->xref())) !== null) {
            return json_decode($map, true);
        }

        if ($this->module->settingEnabled(FacesModule::SETTING_EXIF_NAME)) {
            if (($file = $media->firstImageFile()) === null) {
                throw new HttpNotFoundException();
            }

            $path = Webtrees::DATA_DIR . $tree->getPreference('MEDIA_DIRECTORY') . $file->filename();

            return (new ExifHelper())->getMediaMap($path) ?: [];
        }

        return [];
    }

    private function setMediaMap(Media $media, array $map = []): void
    {
        if (($file = $media->firstImageFile()) === null) {
            throw new HttpNotFoundException();
        }

        $this->module->query->setMediaMap($media->xref(), $file->filename(), empty($map) ? null : json_encode($map));
    }
}