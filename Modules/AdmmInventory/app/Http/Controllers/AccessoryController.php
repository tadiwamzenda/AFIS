<?php

namespace Modules\AdmmInventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\AdmmInventory\Models\Accessory;

class AccessoryController extends Controller
{
    public function index()  { return view('admminventory::accessories.index'); }
    public function create() { return view('admminventory::accessories.create'); }

    public function edit(Accessory $accessory)
    {
        return view('admminventory::accessories.edit', compact('accessory'));
    }
}