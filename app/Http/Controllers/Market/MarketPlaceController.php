<?php

namespace App\Http\Controllers\Market;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class MarketPlaceController extends Controller
{
    public function index()
    {
        $jsonFile = base_path('addons.json');
        $addonsData = File::get($jsonFile);
        $addons = json_decode($addonsData);
        return view('panel.admin.market.index', compact('addons'));
    }
}
