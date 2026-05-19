<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(): \Inertia\Response
    {
        return Inertia::render('Settings');
    }

    public function updateMessage(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['message' => 'required|string|max:2000']);
        $request->user()->update(['application_message' => $request->message]);
        return back()->with('success', 'Messaggio aggiornato.');
    }

    public function uploadCv(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'cv' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $user = $request->user();

        if ($user->cv_path) {
            Storage::disk('local')->delete($user->cv_path);
        }

        $filename = $request->file('cv')->getClientOriginalName();
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $path     = $request->file('cv')->storeAs("cvs/{$user->id}", $filename, 'local');

        $user->update(['cv_path' => $path]);
        return back()->with('success', 'CV caricato.');
    }

    public function deleteCv(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        if ($user->cv_path) {
            Storage::disk('local')->delete($user->cv_path);
            $user->update(['cv_path' => null]);
        }
        return back()->with('success', 'CV rimosso.');
    }

    public function downloadCv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = $request->user();
        abort_unless(
            $user->cv_path && Storage::disk('local')->exists($user->cv_path),
            404
        );
        return Storage::disk('local')->download($user->cv_path);
    }
}
