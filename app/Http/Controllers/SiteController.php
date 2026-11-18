<?php

namespace App\Http\Controllers;

use App\Models\Plan;

/**
 * Site institucional do Provador: página inicial, termos e privacidade.
 */
class SiteController extends Controller
{
    public function home()
    {
        return view('site.home', ['plan' => Plan::default()]);
    }

    public function terms()
    {
        return view('site.terms');
    }

    public function privacy()
    {
        return view('site.privacy');
    }
}
