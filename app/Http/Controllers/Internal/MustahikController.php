<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;

class MustahikController extends Controller
{
    public function index()
    {
        return view('internal.mustahik.index');
    }
}
