<?php

namespace Modules\AfisPipeline\Http\Controllers;

use App\Http\Controllers\Controller;

class PipelineController extends Controller
{
    public function index()
    {
        return view('afispipeline::pipeline.index');
    }
}