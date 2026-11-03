<?php

namespace App\Http\Controllers;

use App\Actions\Blogs\CreateBlog;
use App\Actions\Workspaces\CreateWorkspace;
use App\Http\Requests\Workspaces\OnboardingRequest;
use App\Models\Language;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->workspaces()->exists()) {
            return to_route('dashboard');
        }

        return Inertia::render('onboarding', [
            'languages' => Language::query()->orderBy('label')->get(['id', 'code', 'label']),
            'defaultLanguageId' => Language::query()->where('code', App::getLocale())->value('id'),
        ]);
    }

    public function store(OnboardingRequest $request, CreateWorkspace $createWorkspace, CreateBlog $createBlog): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $blog = DB::transaction(function () use ($user, $validated, $createWorkspace, $createBlog) {
            $workspace = $createWorkspace->handle($user, $validated['workspace_name']);

            return $createBlog->handle($workspace, $user, $validated['blog_name'], $validated['language_id']);
        });

        return to_route('blogs.dashboard', [$blog->workspace?->slug, $blog->slug]);
    }
}
