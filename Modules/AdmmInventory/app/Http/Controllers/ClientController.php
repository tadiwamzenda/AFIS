<?php

namespace Modules\AdmmInventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\AdmmInventory\Models\Client;

class ClientController extends Controller
{
    public function index()
    {
        return view('admminventory::clients.index');
    }

    public function create()
    {
        return view('admminventory::clients.create');
    }

    public function edit(Client $client)
    {
        return view('admminventory::clients.edit', compact('client'));
    }
}