<?php

namespace App\Http\Controllers\Themes;

use App\Actions\Themes\BlogThemes;
use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogTheme;
use App\Models\Theme;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BlogThemeController extends Controller
{
    public function __construct(private readonly BlogThemes $themes) {}

    public function index(Workspace $workspace, Blog $blog): Response
    {
        $installed = $blog->themes()->with('theme')->get();

        return Inertia::render('blog-settings/themes', [
            'catalog' => Theme::query()->where('is_premium', false)->orderBy('name')->get()->map(fn (Theme $theme) => [
                'id' => $theme->id,
                'slug' => $theme->slug,
                'name' => $theme->name,
                'description' => $theme->description,
                'preview_url' => $theme->preview_url,
                'package_name' => $theme->package_name,
                'latest_version' => $theme->latest_version,
                'installed' => $installed->contains('theme_id', $theme->id),
            ]),
            'installed' => $installed->sortByDesc('is_active')->values()->map(fn (BlogTheme $item) => [
                'id' => $item->id,
                'name' => $item->theme->name,
                'version' => $item->version,
                'latest_version' => $item->theme->latest_version,
                'is_active' => $item->is_active,
                'fields' => $item->theme->fields(),
                'config' => (object) ($item->config ?? []),
            ]),
        ]);
    }

    public function store(Request $request, Workspace $workspace, Blog $blog): RedirectResponse
    {
        $validated = $request->validate([
            'theme_id' => ['required', 'uuid', Rule::exists('themes', 'id')],
        ]);

        $this->themes->install($blog, Theme::query()->whereKey($validated['theme_id'])->firstOrFail());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Theme installed.')]);

        return back();
    }

    public function activate(Workspace $workspace, Blog $blog, BlogTheme $theme): RedirectResponse
    {
        $this->themes->activate($blog, $theme);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Theme activated.')]);

        return back();
    }

    public function update(Request $request, Workspace $workspace, Blog $blog, BlogTheme $theme): RedirectResponse
    {
        $validated = $request->validate(['config' => ['present', 'array']]);

        $this->themes->configure($blog, $theme, (array) $validated['config']);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Theme settings saved.')]);

        return back();
    }

    public function upgrade(Workspace $workspace, Blog $blog, BlogTheme $theme): RedirectResponse
    {
        $this->themes->upgrade($blog, $theme);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Theme updated to the latest version.')]);

        return back();
    }

    public function destroy(Workspace $workspace, Blog $blog, BlogTheme $theme): RedirectResponse
    {
        $this->themes->uninstall($blog, $theme);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Theme removed.')]);

        return back();
    }
}
