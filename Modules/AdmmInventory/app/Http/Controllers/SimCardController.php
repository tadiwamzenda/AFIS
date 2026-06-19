<?php

namespace Modules\AdmmInventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\AdmmInventory\Models\SimCard;

class SimCardController extends Controller
{
    public function index()
    {
        return view('admminventory::sim-cards.index');
    }

    public function create()
    {
        return view('admminventory::sim-cards.create');
    }

    public function edit(SimCard $simCard)
    {
        return view('admminventory::sim-cards.edit', compact('simCard'));
    }
}