<?php

namespace App\Http\Controllers\ContentStudio;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\ContentNode;
use App\Models\ContentRelease;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $canReview = Gate::allows('content-studio.review');
        $canPublish = Gate::allows('content-studio.publish');

        return view('content-studio.dashboard', [
            'canReview' => $canReview,
            'canPublish' => $canPublish,
            'pendingReviewCount' => $canReview
                ? ContentNode::query()->where('status', ContentStatus::InReview->value)->count()
                : null,
            'approvedContentCount' => ContentNode::query()
                ->where('status', ContentStatus::Approved->value)
                ->count(),
            'draftReleaseCount' => ContentRelease::query()
                ->where('status', 'draft')
                ->count(),
        ]);
    }
}
