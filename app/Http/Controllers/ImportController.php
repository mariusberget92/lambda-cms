<?php

namespace App\Http\Controllers;

use App\Services\ImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ImportController extends Controller
{
    public function index()
    {
        return Inertia::render('Import/Index', [
            'results'  => session('import_results'),
            'preview'  => session('import_preview'),
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:zip|max:204800',
        ]);

        $path = $request->file('file')->storeAs(
            'imports/tmp',
            Str::uuid() . '.zip',
            'local'
        );

        try {
            $preview = (new ImportService())->preview(Storage::disk('local')->path($path));
            $preview['tmp_path'] = $path;
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('import.index')
            ->with('import_preview', $preview);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tmp_path'          => 'required|string',
            'entities'          => 'required|array|min:1',
            'entities.*'        => 'in:posts,categories,tags,media,templates',
            'conflict_strategy' => 'required|in:skip,overwrite,duplicate',
        ]);

        $path = $request->input('tmp_path');

        if (!Storage::disk('local')->exists($path)) {
            return back()->with('error', 'Upload session expired. Please upload the file again.');
        }

        $fullPath = Storage::disk('local')->path($path);

        try {
            $results = (new ImportService())->import(
                $fullPath,
                $request->input('entities'),
                $request->input('conflict_strategy'),
                $request->user()->id
            );
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        Storage::disk('local')->delete($path);

        return redirect()
            ->route('import.index')
            ->with('import_results', $results);
    }
}
