<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\Component;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(): View
    {
        $audits = Audit::query()
            ->with('user')
            ->latest()
            ->paginate(50);

        // Eager-load the auditable Component including inactive ones, avoiding N+1
        $componentIds = $audits
            ->filter(fn ($a) => $a->auditable_type === Component::class)
            ->pluck('auditable_id')
            ->unique()
            ->values();

        if ($componentIds->isNotEmpty()) {
            $components = Component::withoutGlobalScope('active')
                ->whereIn('id', $componentIds)
                ->get()
                ->keyBy('id');

            $audits->each(function (Audit $audit) use ($components) {
                if ($audit->auditable_type === Component::class) {
                    $audit->setRelation('auditable', $components->get($audit->auditable_id));
                }
            });
        }

        return view('activity.index', compact('audits'));
    }
}
