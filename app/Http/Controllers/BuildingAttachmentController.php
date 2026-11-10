<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\IncidentalCost;
use App\Services\PrivateImage;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BuildingAttachmentController extends Controller
{
    public function announcement(Announcement $announcement, PrivateImage $images): StreamedResponse
    {
        Gate::authorize('viewAttachment', $announcement->complex);

        return $images->response(Announcement::ATTACHMENT_DIRECTORY, $announcement->image_url);
    }

    public function incidentalCost(IncidentalCost $incidentalCost, PrivateImage $images): StreamedResponse
    {
        Gate::authorize('viewAttachment', $incidentalCost->complex);

        return $images->response(IncidentalCost::ATTACHMENT_DIRECTORY, $incidentalCost->cost_invoice);
    }
}
