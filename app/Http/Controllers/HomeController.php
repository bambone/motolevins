<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $bikes = \App\Models\Bike::where('is_active', true)->get();
        return view('pages.home', compact('bikes'));
    }
}
