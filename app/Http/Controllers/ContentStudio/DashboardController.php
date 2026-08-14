<?php

namespace App\Http\Controllers\ContentStudio;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('content-studio.dashboard');
    }
}
