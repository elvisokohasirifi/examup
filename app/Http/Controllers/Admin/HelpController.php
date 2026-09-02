<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class HelpController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.help');
    }
}
