<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExportController extends Controller
{
    public function index()
    {
        return Inertia::render('Export/Index', [
            'counts' => [
                'posts'      => Post::count(),
                'categories' => Category::count(),
                'tags'       => Tag::count(),
                'media'      => Media::count(),
            ],
        ]);
    }

    public function download(Request $request)
    {
        $request->validate([
            'entities'            => 'required|array|min:1',
            'entities.*'          => 'in:posts,categories,tags,media',
            'include_media_files' => 'nullable|boolean',
        ]);

        $entities           = $request->input('entities', []);
        $includeMediaFiles  = $request->boolean('include_media_files');

        $tmpPath = (new ExportService())->generate($entities, $includeMediaFiles);
        $filename = 'lambda-cms-export-' . now()->format('Y-m-d-His') . '.zip';

        return response()
            ->download($tmpPath, $filename, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }
}
