<?php

namespace App\Http\Controllers;

use App\Enums\ComponentType;
use App\Enums\LifecycleStage;
use App\Models\Component;
use App\Models\Tag;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalComponents = Component::query()->count();

        $countsByType = collect(ComponentType::cases())->mapWithKeys(
            fn ($type) => [$type->value => Component::query()->where('type', $type->value)->count()]
        );

        $countsByLifecycle = collect(LifecycleStage::cases())->mapWithKeys(
            fn ($stage) => [$stage->value => Component::query()->where('lifecycle_stage', $stage->value)->count()]
        );

        $recentComponents = Component::query()
            ->with('tags')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $topTags = Tag::query()
            ->withCount('components')
            ->orderByDesc('components_count')
            ->limit(10)
            ->get();

        $types = ComponentType::cases();
        $lifecycleStages = LifecycleStage::cases();

        return view('dashboard.index', compact(
            'totalComponents',
            'countsByType',
            'countsByLifecycle',
            'recentComponents',
            'topTags',
            'types',
            'lifecycleStages',
        ));
    }
}
