<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class PublicPageController extends Controller
{
    public function home(): View
    {
        return view('public.home');
    }

    public function features(): View
    {
        return view('public.features');
    }

    public function about(): View
    {
        return view('public.about');
    }

    public function privacyPolicy(): View
    {
        return view('public.privacy-policy');
    }

    public function dataDeletion(): View
    {
        return view('public.data-deletion');
    }

    public function contact(): View
    {
        return view('public.contact');
    }
}
