<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Tests\Feature\Profile\DashboardControllerTest;

/**
 * Tests @see DashboardControllerTest
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        return view('profile.dashboard');
    }
}
