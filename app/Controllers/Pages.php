<?php

namespace App\Controllers;

class Pages extends BaseController
{
    public function home()
    {

        return view('home');
    }

    public function about()
    {

        return view('about');
    }
}