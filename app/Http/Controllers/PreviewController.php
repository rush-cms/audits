<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BuildPreviewAuditAction;
use App\Data\PreviewParametersData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class PreviewController extends Controller
{
    public function __invoke(Request $request, BuildPreviewAuditAction $action): View
    {
        $params = PreviewParametersData::fromRequest($request->query());

        app()->setLocale($params->lang);

        return view('reports.audit-preview', [
            'audit' => $action->execute($params),
            'currentLang' => $params->lang,
        ]);
    }
}
