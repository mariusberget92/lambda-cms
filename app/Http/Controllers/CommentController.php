<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommentController extends Controller
{
    /**
     * Admin moderation index — paginated, filterable by status.
     */
    public function index(Request $request): Response
    {
        $filter = $request->input('filter', 'pending');

        $comments = Comment::with(['post:id,title,slug', 'user:id,name', 'replies.user:id,name'])
            ->whereNull('parent_id')
            ->when($filter !== 'all', fn ($q) => $q->where('status', $filter))
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Comment $c) => [
                'id'           => $c->id,
                'author_name'  => $c->author_name,
                'author_email' => $c->author_email,
                'body'         => $c->body,
                'status'       => $c->status,
                'created_at'   => $c->created_at->diffForHumans(),
                'post'         => [
                    'title' => $c->post->title,
                    'slug'  => $c->post->slug,
                ],
                'replies' => $c->replies->map(fn ($r) => [
                    'id'          => $r->id,
                    'author_name' => $r->author_name,
                    'body'        => $r->body,
                    'created_at'  => $r->created_at->diffForHumans(),
                ])->values(),
            ]);

        return Inertia::render('Comments/Index', [
            'comments'     => $comments,
            'filter'       => $filter,
            'pendingCount' => Comment::pending()->whereNull('parent_id')->count(),
        ]);
    }

    /**
     * Public — store a new comment (pending).
     */
    public function store(Request $request, Post $post): RedirectResponse
    {
        if (! $post->commentsOpen()) {
            abort(403, 'Comments are disabled.');
        }

        // Honeypot — silently discard if filled
        if ($request->filled('website')) {
            return back()->with('status', 'Your comment has been submitted and is awaiting moderation.');
        }

        $validated = $request->validate([
            'author_name'  => ['required', 'string', 'max:100'],
            'author_email' => ['nullable', 'email', 'max:255'],
            'body'         => ['required', 'string', 'max:5000'],
        ]);

        $comment = $post->comments()->create([
            'user_id'      => $request->user()?->id,
            'author_name'  => $validated['author_name'],
            'author_email' => $validated['author_email'] ?? null,
            'body'         => $validated['body'],
            'status'       => 'pending',
        ]);

        dispatch(new \App\Jobs\SendNewCommentNotification($comment));

        return back()->with('status', 'Your comment has been submitted and is awaiting moderation.');
    }

    /**
     * Admin — approve a comment.
     */
    public function approve(Comment $comment): RedirectResponse
    {
        $comment->update(['status' => 'approved']);
        return back()->with('status', 'Comment approved.');
    }

    /**
     * Admin — reject a comment.
     */
    public function reject(Comment $comment): RedirectResponse
    {
        $comment->update(['status' => 'rejected']);
        return back()->with('status', 'Comment rejected.');
    }

    /**
     * Admin — hard delete a comment.
     */
    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->delete();
        return back()->with('status', 'Comment deleted.');
    }

    /**
     * Admin — reply to a comment, optionally notify the original commenter.
     */
    public function reply(Request $request, Comment $comment): RedirectResponse
    {
        abort_if($comment->parent_id !== null, 422, 'Cannot reply to a reply.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $reply = Comment::create([
            'post_id'      => $comment->post_id,
            'parent_id'    => $comment->id,
            'user_id'      => $request->user()->id,
            'author_name'  => $request->user()->name,
            'author_email' => $request->user()->email,
            'body'         => $validated['body'],
            'status'       => 'approved',
        ]);

        $comment->loadMissing('post');

        if ($comment->author_email) {
            \Mail::to($comment->author_email)
                ->queue(new \App\Mail\CommentReplyMail($comment, $reply));
        }

        return back()->with('status', 'Reply sent.');
    }

    /**
     * Admin — bulk approve / reject / delete.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject,delete'],
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'exists:comments,id'],
        ]);

        $comments = Comment::whereIn('id', $validated['ids']);

        match ($validated['action']) {
            'approve' => $comments->update(['status' => 'approved']),
            'reject'  => $comments->update(['status' => 'rejected']),
            'delete'  => $comments->delete(),
        };

        return back()->with('status', ucfirst($validated['action']) . 'd ' . count($validated['ids']) . ' comment(s).');
    }
}
