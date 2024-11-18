<?php

namespace UksusoFF\WebtreesModules\Faces\Http\Controllers;

use Exception;
use Fisharebest\Webtrees\Fact;
use Fisharebest\Webtrees\Http\Exceptions\HttpNotFoundException;
use Fisharebest\Webtrees\Http\RequestHandlers\LinkMediaToRecordAction;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Media;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\LinkedRecordService;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Webtrees;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use UksusoFF\WebtreesModules\Faces\Helpers\ExifHelper;
use UksusoFF\WebtreesModules\Faces\Modules\FacesModule;
use UksusoFF\WebtreesModules\Faces\Wrappers\FactWrapper;

class DataController implements RequestHandlerInterface
{
    public const ROUTE_PREFIX = 'faces-data';

    protected FacesModule $module;

    protected LinkedRecordService $links;

    public function __construct(FacesModule $module)
    {
        $this->module = $module;

        $this->links = app(LinkedRecordService::class);
    }

    public function handle(Request $request): Response
    {
        try {
            $media = $this->getMedia($request);
            $fact = $this->getFact($request);

            switch ($request->getAttribute('action')) {
                case 'index':
                    return $this->index($request, $media, $fact);
                case 'attach':
                    return $this->attach($request, $media, $fact);
                case 'detach':
                    return $this->detach($request, $media, $fact);
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

    private function index(Request $request, Media $media, string $fact): Response
    {
        if (!$media->canShow()) {
            throw new HttpNotFoundException();
        }

        return response([
            'success' => true,
            'title' => $this->getMediaTitle($media, $fact),
            'meta' => $this->module->settingEnabled(FacesModule::SETTING_META_NAME)
                ? $this->getMediaMeta($media)
                : [],
            'map' => $this->getMediaMapForTree($media, $fact),
            'edit' => $media->canEdit(),
        ]);
    }

    private function attach(Request $request, Media $media, string $fact): Response
    {
        if (!$media->canEdit()) {
            throw new HttpNotFoundException();
        }

        $pid = $request->getParsedBody()['pid'] ?? null;
        $coords = $request->getParsedBody()['coords'] ?? null;

        if ($pid === null || $coords === null) {
            throw new HttpNotFoundException();
        }

        $map = $this->getMediaMap($media, $fact);

        $map[] = (object)[
            'pid' => $pid,
            'coords' => $coords,
        ];

        $this->setMediaMap($media, $fact, $map);

        $linked = $this->links->linkedIndividuals($media, 'OBJE')->first(function(Individual $individual) use ($pid) {
            return $individual->xref() === $pid;
        });

        return response([
            'success' => true,
            'linker' => $this->module->settingEnabled(FacesModule::SETTING_LINKING_NAME) && ($linked === null)
                ? [
                    'url' => route(LinkMediaToRecordAction::class, [
                        'tree' => $media->tree()->name(),
                        'xref' => $media->xref(),
                    ]),
                    'data' => [
                        'link' => $pid,
                    ],
                ]
                : null,
        ]);
    }

    private function detach(Request $request, Media $media, string $fact): Response
    {
        if (!$media->canEdit()) {
            throw new HttpNotFoundException();
        }

        $pid = $request->getParsedBody()['pid'] ?? null;

        if ($pid === null) {
            throw new HttpNotFoundException();
        }

        $map = array_filter($this->getMediaMap($media, $fact), function($area) use ($pid) {
            return !empty($area['pid']) && $area['pid'] !== $pid;
        });

        $this->setMediaMap($media, $fact, $map);

        return response([
            'success' => true,
        ]);
    }

    private function getMedia(Request $request): Media
    {
        $mid = $request->getQueryParams()['mid'] ?? $request->getParsedBody()['mid'];

        if ($mid === null) {
            throw new HttpNotFoundException();
        }

        $tree = $request->getAttribute('tree');

        if (!($tree instanceof Tree)) {
            throw new HttpNotFoundException();
        }

        return Registry::mediaFactory()->make($mid, $tree);
    }

    private function getFact(Request $request): string
    {
        $fact = $request->getQueryParams()['fact'] ?? $request->getParsedBody()['fact'];

        if ($fact === null) {
            throw new HttpNotFoundException();
        }

        return $fact;
    }

    private function getMediaMapForTree(Media $media, string $fact): array
    {
        $result = [];
        $pids = [];
        $areas = $this->getMediaMap($media, $fact);
        $priorFact = $this->getMediaFacts($media)->first();

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
            foreach ($this->module->query->getIndividualsDataByTreeAndPids($media->tree()->id(), $pids) as $row) {
                $person = Registry::individualFactory()->make($row->xref, $media->tree(), $row->gedcom);
                if ($person === null) {
                    continue;
                }

                $public = $person->canShowName();

                $result[$row->xref] = array_merge($result[$row->xref], [
                    'link' => $public
                        ? $person->url()
                        : null,
                    'name' => $public
                        ? $this->getPersonDisplayName($person, $priorFact)
                        : I18N::translate('Private'),
                    'age' => $public
                        ? $this->getPersonDisplayAgePhoto($person, $priorFact)
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

    private function getPersonDisplayName(Individual $person, ?Fact $fact): string
    {
        return html_entity_decode(strip_tags(str_replace([
            '<q class="wt-nickname">',
            '</q>',
        ], '"', $person->fullName())), ENT_QUOTES);
    }

    private function getPersonDisplayAgePhoto(Individual $person, ?Fact $fact): string
    {
        if (empty($fact)) {
            return I18N::translate('Missing fact date');
        }

        if (strlen(trim(strip_tags($person->lifespan()))) > 6) {
            $birthyear = substr(strip_tags($person->lifespan()),0,4) + 0;
        }
        else {
            return I18N::translate('Missing birth');
        }

        $Photoyear =  substr($fact->attribute('DATE'),-4) + 0; 
        $birthyear = $Photoyear - $birthyear;
        //TODO split this in day/month/year
        return I18N::translate('Age at', $birthyear);
    }

    private function getMediaTitle(Media $media, string $fact): string
    {
        [$file, $order] = $this->module->media->getMediaImageFileByFact($media, $fact);

        if ($file === null) {
            throw new HttpNotFoundException();
        }

        return !empty($file->title())
            ? $file->title()
            : $file->filename();
    }

    private function getMediaFacts(Media $media): Collection
    {
        return $this->links->linkedIndividuals($media, 'OBJE')
            ->flatMap(function(Individual $individual) use ($media) {
                return $individual
                    ->facts()
                    ->filter(function(Fact $fact) use ($media) {
                        return collect(FactWrapper::getMedia($fact))->filter(function(Media $m) use ($media) {
                            return $media->xref() === $m->xref();
                        })->isNotEmpty();
                    });
            })
            ->unique(function(Fact $fact) {
                return $fact->attribute('DATE');
            });
    }

    private function getMediaMeta(Media $media): array
    {
        return $this->getMediaFacts($media)
            ->map(function(Fact $fact) {
                return array_filter([
                    $fact->attribute('PLAC'),
                    $fact->attribute('DATE'),
                ]);
            })
            ->toArray();
    }

    private function getMediaMap(Media $media, string $fact): array
    {
        [$file, $order] = $this->module->media->getMediaImageFileByFact($media, $fact);

        if ($file === null) {
            throw new HttpNotFoundException();
        }

        if (($map = $this->module->query->getMediaMap(
            $media->tree()->id(),
            $media->xref(),
            $order
        )) !== null) {
            return json_decode($map, true);
        }

        if ($this->module->settingEnabled(FacesModule::SETTING_EXIF_NAME)) {
            $path = Webtrees::DATA_DIR . $media->tree()->getPreference('MEDIA_DIRECTORY') . $file->filename();

            return (new ExifHelper())->getMediaMap($path) ?: [];
        }

        return [];
    }

    private function setMediaMap(Media $media, string $fact, array $map = []): void
    {
        [$file, $order] = $this->module->media->getMediaImageFileByFact($media, $fact);

        if ($file === null) {
            throw new HttpNotFoundException();
        }

        $this->module->query->setMediaMap(
            $media->tree()->id(),
            $media->xref(),
            $order,
            $file->filename(),
            empty($map) ? null : json_encode($map)
        );
    }
}
