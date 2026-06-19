<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HelpController extends Controller
{
    public function howItWorks(): View
    {
        return view('help.how-it-works');
    }

    public function index(): View
    {
        return view('help.index');
    }

    public function __invoke(): View
    {
        return $this->howItWorks();
    }
}
