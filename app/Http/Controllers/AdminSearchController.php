<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $like = '%' . $q . '%';

        $posts = Post::where('title', 'like', $like)
            ->orWhere('excerpt', 'like', $like)
            ->limit(5)
            ->get(['id', 'title', 'status', 'slug'])
            ->map(fn ($p) => [
                'type'  => 'post',
                'id'    => $p->id,
                'label' => $p->title,
                'meta'  => $p->status,
                'url'   => route('posts.edit', $p->id),
            ]);

        $pages = Page::where('title', 'like', $like)
            ->limit(5)
            ->get(['id', 'title', 'status'])
            ->map(fn ($p) => [
                'type'  => 'page',
                'id'    => $p->id,
                'label' => $p->title,
                'meta'  => $p->status,
                'url'   => route('pages.edit', $p->id),
            ]);

        $media = Media::where('original_filename', 'like', $like)
            ->orWhere('alt', 'like', $like)
            ->limit(4)
            ->get(['id', 'original_filename', 'path', 'disk'])
            ->map(fn ($m) => [
                'type'  => 'media',
                'id'    => $m->id,
                'label' => $m->original_filename,
                'meta'  => null,
                'url'   => route('media.index'),
            ]);

        $users = User::where('name', 'like', $like)
            ->orWhere('email', 'like', $like)
            ->limit(4)
            ->get(['id', 'name', 'email'])
            ->map(fn ($u) => [
                'type'  => 'user',
                'id'    => $u->id,
                'label' => $u->name,
                'meta'  => $u->email,
                'url'   => route('users.edit', $u->id),
            ]);

        return response()->json([
            'posts' => $posts,
            'pages' => $pages,
            'media' => $media,
            'users' => $users,
        ]);
    }
}
