<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class TestController extends Controller
{
    public function index(): View
    {
        return view('test');
    }
}
