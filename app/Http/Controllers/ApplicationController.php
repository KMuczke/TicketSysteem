<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    /**
     * Display a listing of the applications.
     */
    public function index()
    {
        $applications = Application::orderBy('naam')->get();

        return view('applications.index', compact('applications'));
    }

    /**
     * Show the form for creating a new application.
     */
    public function create()
    {
        return view('applications.create');
    }

    /**
     * Store a newly created application in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'naam' => ['required', 'string', 'max:255', Rule::unique('applications', 'naam')],
            'url' => ['nullable', 'url', 'max:255'],
        ]);

        $validated['is_active'] = true;

        Application::create($validated);

        return redirect()
            ->route('applications.index')
            ->with('success', 'Applicatie aangemaakt.');
    }

    /**
     * Show the form for editing the specified application.
     */
    public function edit(Application $application)
    {
        return view('applications.edit', compact('application'));
    }

    /**
     * Update the specified application in storage.
     */
    public function update(Request $request, Application $application)
    {
        $validated = $request->validate([
            'naam' => [
                'required',
                'string',
                'max:255',
                Rule::unique('applications', 'naam')->ignore($application->id),
            ],
            'url' => ['nullable', 'url', 'max:255'],
        ]);

        $application->update($validated);

        return redirect()
            ->route('applications.index')
            ->with('success', 'Applicatie bijgewerkt.');
    }

    /**
     * Activate or deactivate the specified application.
     */
    public function toggleActive(Application $application)
    {
        $application->update(['is_active' => ! $application->is_active]);

        return back()->with(
            'success',
            $application->is_active ? 'Applicatie geactiveerd.' : 'Applicatie gedeactiveerd.',
        );
    }
}
