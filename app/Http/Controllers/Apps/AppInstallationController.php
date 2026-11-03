<?php

namespace App\Http\Controllers\Apps;

use App\Actions\Apps\InstallApp;
use App\Http\Controllers\Controller;
use App\Models\AppInstallation;
use App\Models\Blog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppInstallationController extends Controller
{
    public function index(Workspace $workspace, Blog $blog): Response
    {
        return Inertia::render('blog-settings/apps', [
            'installations' => $blog->appInstallations()->with(['app:id,name,is_first_party', 'installer:id,name'])->whereNull('revoked_at')->latest()->get()
                ->map(fn (AppInstallation $installation) => [
                    'id' => $installation->id,
                    'app' => $installation->app?->name,
                    'first_party' => (bool) $installation->app?->is_first_party,
                    'scopes' => $installation->granted_scopes,
                    'installed_by' => $installation->installer?->name,
                    'created_at' => $installation->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function destroy(Request $request, Workspace $workspace, Blog $blog, AppInstallation $appInstallation, InstallApp $install): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $install->revoke($appInstallation, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('App removed from this blog.')]);

        return back();
    }
}
