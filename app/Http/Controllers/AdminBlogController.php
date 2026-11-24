<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminBlogController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Blog/Index', [
            'posts' => BlogPost::latest('updated_at')->orderByDesc('id')
                ->paginate(20, ['id', 'title', 'slug', 'published_at', 'updated_at']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Blog/Editor', ['post' => null]);
    }

    public function edit(BlogPost $post)
    {
        return Inertia::render('Admin/Blog/Editor', [
            'post' => $post->only('id', 'title', 'slug', 'summary', 'body', 'published_at'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $post = DB::transaction(function () use ($data) {
            DB::select("SELECT pg_advisory_xact_lock(hashtext('eventjunction.blog_posts.create'))");
            $base = rtrim(Str::limit(Str::slug($data['title']), 160, ''), '-') ?: 'post';
            $slug = $base;
            for ($suffix = 2; BlogPost::where('slug', $slug)->exists(); $suffix++) {
                $slug = $base.'-'.$suffix;
            }
            $post = new BlogPost(['slug' => $slug]);
            $this->save($post, $data);

            return $post;
        });

        return to_route('admin.blog.edit', $post)->with('success', $this->message($post));
    }

    public function update(Request $request, BlogPost $post)
    {
        $data = $this->validated($request);
        $post = DB::transaction(function () use ($post, $data) {
            $locked = BlogPost::whereKey($post->id)->lockForUpdate()->firstOrFail();
            $this->save($locked, $data);

            return $locked;
        });

        return to_route('admin.blog.edit', $post)->with('success', $this->message($post));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'summary' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:50000'],
            'status' => ['required', 'in:draft,published'],
        ]);
    }

    private function save(BlogPost $post, array $data): void
    {
        $post->fill([
            'title' => $data['title'],
            'summary' => $data['summary'],
            'body' => $data['body'],
            'published_at' => $data['status'] === 'published' ? ($post->published_at ?? now()) : null,
        ])->save();
    }

    private function message(BlogPost $post): string
    {
        return $post->published_at ? 'Post published.' : 'Draft saved.';
    }
}
