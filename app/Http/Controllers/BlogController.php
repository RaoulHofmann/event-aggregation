<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $posts = BlogPost::published()->latest('published_at')->orderByDesc('id')
            ->paginate(12, ['title', 'slug', 'summary', 'published_at']);
        abort_if($posts->currentPage() > $posts->lastPage(), 404);
        $metadata = config('public-site.pages')['blog.index'];
        $path = $posts->currentPage() === 1 ? '/blog' : '/blog?page='.$posts->currentPage();
        if ($posts->currentPage() > 1) {
            $metadata['title'] = 'Blog · Page '.$posts->currentPage().' — EventJunction';
        }

        return $this->render('blog.index', ['posts' => $posts], $metadata, $path);
    }

    public function show(string $slug)
    {
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();

        return $this->render('blog.post', ['post' => $post], [
            'title' => $post->title.' — EventJunction',
            'description' => $post->summary,
            'type' => 'article',
        ], route('blog.show', $post->slug, absolute: false));
    }

    private function render(string $view, array $data, array $metadata, string $canonicalPath)
    {
        return Inertia::render('Blog', [
            'title' => $metadata['title'],
            'blogHtml' => view($view, $data)->render(),
        ])->withViewData(['publicMetadata' => $metadata, 'canonicalPath' => $canonicalPath]);
    }
}
