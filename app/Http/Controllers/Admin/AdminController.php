<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Component;
use App\Models\ComponentType;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        abort_unless(Auth::user()->isAdmin(), 403);

        $totalComponents = Component::query()->count();
        $totalUsers = User::query()->count();
        $activeUsers = User::query()->where('is_active', true)->count();
        $totalTags = Tag::query()->count();
        $totalTypes = ComponentType::query()->count();

        $componentsByType = ComponentType::query()
            ->orderBy('name')
            ->withCount('components')
            ->get();

        return view('admin.index', compact(
            'totalComponents',
            'totalUsers',
            'activeUsers',
            'totalTags',
            'totalTypes',
            'componentsByType',
        ));
    }
}
