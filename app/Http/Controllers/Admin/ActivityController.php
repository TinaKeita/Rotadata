<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

// aktivitāte tagad ir grupas iestatījumu lapas sadaļa – vecās saites ved turp
class ActivityController extends Controller
{
    public function index()
    {
        return redirect()->to(route('admin.group.settings').'#activity');
    }
}
