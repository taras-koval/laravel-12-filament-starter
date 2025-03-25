<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Tests\Feature\IndexControllerTest;

/**
 * Tests
 * @see IndexControllerTest
 */
class IndexController extends Controller
{
    public function index(): View
    {
        return view('index');
    }
}
