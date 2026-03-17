<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(): View
    {
        $audits = Audit::query()
            ->with('user')
            ->latest()
            ->paginate(50);

        return view('activity.index', compact('audits'));
    }
}
