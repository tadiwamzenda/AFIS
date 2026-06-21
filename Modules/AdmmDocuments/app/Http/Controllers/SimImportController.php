<?php

namespace Modules\AdmmDocuments\Http\Controllers;

use App\Http\Controllers\Controller;

class SimImportController extends Controller
{
    public function index()
    {
        return view('admmdocuments::sim-import.index');
    }
}