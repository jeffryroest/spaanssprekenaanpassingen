<?php

namespace App\Http\Controllers\ContentStudio;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\ContentNode;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $canReview = Gate::allows('content-studio.review');

        return view('content-studio.dashboard', [
            'canReview' => $canReview,
            'pendingReviewCount' => $canReview
                ? ContentNode::query()->where('status', ContentStatus::InReview->value)->count()
                : null,
        ]);
    }
}
