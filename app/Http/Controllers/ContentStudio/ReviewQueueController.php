<?php

namespace App\Http\Controllers\ContentStudio;

use App\ContentStudio\ContentReviewPolicy;
use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\ContentNode;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReviewQueueController extends Controller
{
    public function __invoke(ContentReviewPolicy $reviewPolicy): View
    {
        Gate::authorize('content-studio.review');

        $contentNodes = ContentNode::query()
            ->with(['localizations', 'revisions', 'reviews.actor', 'creator'])
            ->where('status', ContentStatus::InReview->value)
            ->oldest('updated_at')
            ->paginate(20);

        return view('content-studio.reviews.index', compact('contentNodes', 'reviewPolicy'));
    }
}
