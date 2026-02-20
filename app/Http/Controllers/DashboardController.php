<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render("Dashboard/Index", [
            "stats" => [
                "total"     => Post::count(),
                "published" => Post::published()->count(),
                "drafts"    => Post::draft()->count(),
            ],
        ]);
    }
}
