<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Team::class);

        $teams = Team::query()->withCount('users')->orderBy('name')->paginate(20);

        return view('teams.index', compact('teams'));
    }

    public function create(): View
    {
        $this->authorize('create', Team::class);

        return view('teams.create');
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $this->authorize('create', Team::class);

        $team = Team::query()->create($request->validated());

        return redirect()->route('teams.show', $team)
            ->with('success', 'Team created successfully.');
    }

    public function show(Team $team): View
    {
        $this->authorize('view', $team);

        $team->load('users');
        $existingUserIds = $team->users->pluck('id');
        $availableUsers = User::query()
            ->where('is_active', true)
            ->whereNotIn('id', $existingUserIds)
            ->orderBy('name')
            ->get();

        return view('teams.show', compact('team', 'availableUsers'));
    }

    public function edit(Team $team): View
    {
        $this->authorize('update', $team);

        return view('teams.edit', compact('team'));
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $this->authorize('update', $team);

        $team->update($request->validated());

        return redirect()->route('teams.show', $team)
            ->with('success', 'Team updated successfully.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->authorize('delete', $team);

        $team->delete();

        return redirect()->route('teams.index')
            ->with('success', 'Team deleted successfully.');
    }

    public function addMember(Request $request, Team $team): RedirectResponse
    {
        $this->authorize('update', $team);

        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $team->users()->syncWithoutDetaching([$request->integer('user_id')]);

        return redirect()->route('teams.show', $team)
            ->with('success', 'Member added successfully.');
    }

    public function removeMember(Team $team, User $user): RedirectResponse
    {
        $this->authorize('update', $team);

        $team->users()->detach($user->id);

        return redirect()->route('teams.show', $team)
            ->with('success', 'Member removed successfully.');
    }
}
