<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ShowsController extends Controller
{
    
    public function list()
    {
        return view('shows.index');
    }

}
