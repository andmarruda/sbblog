<?php

use App\Http\Controllers\Apps\AppController;
use App\Http\Controllers\Apps\AppInstallationController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Blogs\BlogDashboardController;
use App\Http\Controllers\Blogs\BlogMemberController;
use App\Http\Controllers\Blogs\BlogSettingsController;
use App\Http\Controllers\Themes\BlogThemeController;
use App\Http\Controllers\Workspaces\InvitationController;
use App\Http\Controllers\Workspaces\MemberController;
use App\Http\Controllers\Workspaces\RoleController;
use App\Http\Controllers\Workspaces\WorkspaceBlogController;
use App\Http\Controllers\Workspaces\WorkspaceSettingsController;
use App\Http\Middleware\EnsureTenant;
use App\Models\Blog;
use App\Models\Workspace;
use Illuminate\Support\Facades\Route;

/*
| /{workspace}/settings/…      workspace pages
| /{workspace}/{blog}/…        blog pages
|
| Registered last: the workspace slug is a catch-all first segment, and reserved slugs keep it
| from shadowing top-level routes. {blog:slug} is scoped to the workspace automatically (custom
| key); {member} and {invitation} are checked against the workspace in the controllers.
*/

// Reserved slugs never match, so /api/…, /login… and friends keep working.
$workspaceSlug = '(?!(?:'.implode('|', array_map('preg_quote', config('sbblog.reserved_slugs'))).')(?![a-z0-9-]))[a-z0-9-]+';

Route::middleware(['auth', 'auth.session', 'verified', EnsureTenant::class])
    ->prefix('{workspace:slug}')
    ->where(['workspace' => $workspaceSlug, 'blog' => '[a-z0-9-]+'])
    ->group(function () {
        Route::prefix('settings')->name('workspaces.settings.')->group(function () {
            Route::get('/', fn (Workspace $workspace) => to_route('workspaces.settings.blogs', $workspace->slug))->name('index');

            Route::get('general', [WorkspaceSettingsController::class, 'edit'])
                ->middleware('can:workspace.settings,workspace')
                ->name('general');
            Route::patch('general', [WorkspaceSettingsController::class, 'update'])
                ->middleware('can:workspace.settings,workspace')
                ->name('update');
            Route::delete('/', [WorkspaceSettingsController::class, 'destroy'])
                ->middleware('throttle:6,1')
                ->name('destroy');

            Route::get('blogs', [WorkspaceBlogController::class, 'index'])->name('blogs');
            Route::post('blogs', [WorkspaceBlogController::class, 'store'])
                ->middleware('can:blogs.create,workspace')
                ->name('blogs.store');
            Route::post('blogs/{blogId}/restore', [WorkspaceBlogController::class, 'restore'])
                ->middleware('can:blogs.delete,workspace')
                ->whereUuid('blogId')
                ->name('blogs.restore');

            Route::middleware('can:members.manage,workspace')->group(function () {
                Route::get('members', [MemberController::class, 'index'])->name('members');
                Route::patch('members/{member}', [MemberController::class, 'update'])->name('members.update');
                Route::delete('members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

                Route::post('invitations', [InvitationController::class, 'store'])
                    ->middleware('throttle:20,1')
                    ->name('invitations.store');
                Route::post('invitations/{invitation}/resend', [InvitationController::class, 'resend'])
                    ->middleware('throttle:6,1')
                    ->name('invitations.resend');
                Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');

                Route::get('roles', [RoleController::class, 'index'])->name('roles');
            });

            Route::middleware('can:apps.manage,workspace')->group(function () {
                Route::get('apps', [AppController::class, 'index'])->name('apps');
                Route::post('apps', [AppController::class, 'store'])->name('apps.store');
                Route::patch('apps/{app}', [AppController::class, 'update'])->name('apps.update');
                Route::post('apps/{app}/secret', [AppController::class, 'rotateSecret'])->middleware('throttle:10,1')->name('apps.secret');
                Route::delete('apps/{app}', [AppController::class, 'destroy'])->name('apps.destroy');
            });

            Route::post('members/{member}/transfer-ownership', [MemberController::class, 'transferOwnership'])
                ->middleware('throttle:6,1')
                ->name('members.transfer');

            Route::get('audit', [AuditLogController::class, 'workspace'])
                ->middleware('can:workspace.settings,workspace')
                ->name('audit');
        });

        Route::prefix('{blog:slug}')->group(function () {
            Route::get('/', BlogDashboardController::class)->name('blogs.dashboard');

            require __DIR__.'/content.php';

            Route::prefix('settings')->name('blogs.settings.')->group(function () {
                Route::get('/', fn (Workspace $workspace, Blog $blog) => to_route('blogs.settings.general', [$workspace->slug, $blog->slug]))->name('index');

                Route::get('general', [BlogSettingsController::class, 'edit'])
                    ->middleware('can:blog.settings,blog')
                    ->name('general');
                Route::patch('general', [BlogSettingsController::class, 'update'])
                    ->middleware('can:blog.settings,blog')
                    ->name('update');
                Route::delete('/', [BlogSettingsController::class, 'destroy'])
                    ->middleware('can:blogs.delete,workspace')
                    ->name('destroy');

                Route::middleware('can:members.manage,workspace')->group(function () {
                    Route::get('members', [BlogMemberController::class, 'index'])->name('members');
                    Route::put('members/{member}', [BlogMemberController::class, 'update'])->name('members.update');
                    Route::delete('members/{member}', [BlogMemberController::class, 'destroy'])->name('members.destroy');
                });

                Route::get('audit', [AuditLogController::class, 'blog'])
                    ->middleware('can:audit.view,blog')
                    ->name('audit');

                Route::middleware('can:themes.configure,blog')->prefix('themes')->name('themes')->group(function () {
                    Route::get('/', [BlogThemeController::class, 'index']);
                    Route::post('/', [BlogThemeController::class, 'store'])->name('.store');
                    Route::put('{theme:id}', [BlogThemeController::class, 'update'])->name('.update');
                    Route::post('{theme:id}/activate', [BlogThemeController::class, 'activate'])->name('.activate');
                    Route::post('{theme:id}/upgrade', [BlogThemeController::class, 'upgrade'])->name('.upgrade');
                    Route::delete('{theme:id}', [BlogThemeController::class, 'destroy'])->name('.destroy');
                });

                Route::get('apps', [AppInstallationController::class, 'index'])->middleware('can:apps.install,blog')->name('apps');
                Route::delete('apps/{appInstallation:id}', [AppInstallationController::class, 'destroy'])->middleware('can:apps.install,blog')->name('apps.destroy');
            });
        });
    });
