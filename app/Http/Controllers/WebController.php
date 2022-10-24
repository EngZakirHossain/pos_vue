<?php

namespace App\Http\Controllers;

class WebController extends Controller
{
    /**
     * Get the SPA view.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke()
    {
        return view('index');
    }
}
