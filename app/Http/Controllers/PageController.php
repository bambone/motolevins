<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home');
    }

    public function motorcycles(): View
    {
        return view('pages.motorcycles.index');
    }

    public function motorcycle(string $slug): View
    {
        return view('pages.motorcycles.show', ['motorcycle' => null]);
    }

    public function prices(): View
    {
        return view('pages.prices');
    }

    public function order(): View
    {
        return view('pages.order');
    }

    public function reviews(): View
    {
        return view('pages.reviews');
    }

    public function terms(): View
    {
        return view('pages.terms');
    }

    public function faq(): View
    {
        return view('pages.faq');
    }

    public function about(): View
    {
        return view('pages.about');
    }

    public function articles(): View
    {
        return view('pages.articles.index');
    }

    public function article(string $slug): View
    {
        return view('pages.articles.show', ['article' => null]);
    }

    public function deliveryAnapa(): View
    {
        return view('pages.delivery.anapa');
    }

    public function deliveryGelendzhik(): View
    {
        return view('pages.delivery.gelendzhik');
    }

    public function contacts(): View
    {
        return view('pages.contacts');
    }
}
