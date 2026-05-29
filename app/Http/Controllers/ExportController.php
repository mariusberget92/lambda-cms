<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportDownloadRequest;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Template;
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
                'templates'  => Template::count(),
            ],
        ]);
    }

    public function download(ExportDownloadRequest $request)
    {
        $entities          = $request->validated('entities', []);
        $includeMediaFiles = $request->boolean('include_media_files');

        $tmpPath = (new ExportService())->generate($entities, $includeMediaFiles);
        $filename = 'lambda-cms-export-' . now()->format('Y-m-d-His') . '.zip';

        return response()
            ->download($tmpPath, $filename, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }
}
