<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $bikes = \App\Models\Bike::where('is_active', true)->get();
        $badges = [
            'Хит',
            'Новинка',
            null,
            'Лучший выбор',
            null,
            'Новинка',
            null,
            'Лучший выбор',
        ];
        return view('pages.home', compact('bikes', 'badges'));
    }
}
