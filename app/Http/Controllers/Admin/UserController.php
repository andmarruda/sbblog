<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    private const SORTS = ['name', 'email', 'created_at', 'last_login_at'];

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $sort = in_array($request->query('sort'), self::SORTS, true) ? (string) $request->query('sort') : 'name';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $role = $request->query('role') === 'admin' ? 'admin' : null;

        return Inertia::render('admin/users', [
            'users' => User::query()
                ->select(['id', 'name', 'email', 'is_admin', 'email_verified_at', 'last_login_at', 'created_at'])
                ->withCount('workspaces')
                ->when($search !== '', function ($query) use ($search) {
                    $term = '%'.addcslashes($search, '%_\\').'%';
                    $query->where(fn ($query) => $query->where('name', 'ilike', $term)->orWhere('email', 'ilike', $term));
                })
                ->when($role === 'admin', fn ($query) => $query->where('is_admin', true))
                ->orderBy($sort, $direction)
                ->orderBy('id')
                ->paginate(25)
                ->withQueryString(),
            'query' => $request->only(['search', 'sort', 'direction', 'role']),
            'counts' => [
                'users' => User::query()->count(),
                'admins' => User::query()->where('is_admin', true)->count(),
            ],
        ]);
    }

    /**
     * Grant or revoke instance admin. Nobody changes their own flag, so an admin can't lock
     * themselves out and there is always at least one admin left.
     */
    public function updateAdmin(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate(['is_admin' => ['required', 'boolean']]);

        abort_if($user->is($request->user()), 403, __('You cannot change your own administrator access.'));

        $user->is_admin = (bool) $validated['is_admin'];

        if ($user->isDirty('is_admin')) {
            $user->save();
            $audit->record($user->is_admin ? 'admin.granted' : 'admin.revoked', $user);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $user->is_admin
            ? __(':name is now an administrator.', ['name' => $user->name])
            : __(':name is no longer an administrator.', ['name' => $user->name])]);

        return back();
    }
}
