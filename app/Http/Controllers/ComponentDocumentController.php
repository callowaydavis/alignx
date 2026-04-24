<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreComponentDocumentRequest;
use App\Models\Component;
use App\Models\ComponentDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ComponentDocumentController extends Controller
{
    public function store(StoreComponentDocumentRequest $request, Component $component): RedirectResponse
    {
        $this->authorize('update', $component);

        $file = $request->file('document');
        $originalFilename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Store file with unique name in component directory
        $storedPath = $file->store("documents/{$component->id}", 'local');

        // Create document record
        $component->documents()->create([
            'original_filename' => $originalFilename,
            'name' => $request->input('name'),
            'tag' => $request->input('tag'),
            'stored_path' => $storedPath,
            'disk' => 'local',
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'uploaded_by' => Auth::id(),
        ]);

        // Record audit event
        $component->recordAudit('document_uploaded', [], [
            'filename' => $originalFilename,
            'name' => $request->input('name'),
            'tag' => $request->input('tag'),
        ]);

        return redirect()->route('components.show', $component)
            ->with('success', "Document '{$originalFilename}' uploaded successfully.")
            ->withFragment('documents');
    }

    public function show(Component $component, ComponentDocument $document): Response
    {
        $this->authorize('view', $component);

        // Record audit event for viewing/downloading
        $component->recordAudit('document_viewed', [], [
            'filename' => $document->original_filename,
        ]);

        return Storage::disk('local')->download(
            $document->stored_path,
            $document->original_filename
        );
    }

    public function destroy(Component $component, ComponentDocument $document): RedirectResponse
    {
        $this->authorize('update', $component);

        // Record who deleted it
        $document->update(['deleted_by' => Auth::id()]);

        // Record audit event
        $component->recordAudit('document_removed', [
            'filename' => $document->original_filename,
        ], []);

        // Soft delete
        $document->delete();

        return redirect()->route('components.show', $component)
            ->with('success', 'Document removed.')
            ->withFragment('documents');
    }
}
