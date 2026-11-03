<?php

namespace App\Http\Requests\Workspaces;

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInvitationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => Str::lower(trim((string) $this->input('email')))]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Workspace $workspace */
        $workspace = $this->route('workspace');

        return [
            'email' => ['required', 'email', 'max:255'],
            'workspace_role' => ['required', Rule::in([WorkspaceRole::Admin->value, WorkspaceRole::Member->value])],
            'blog_id' => ['nullable', 'uuid', Rule::exists('blogs', 'id')->where('workspace_id', $workspace->id)->whereNull('deleted_at')],
            'role_id' => [
                'nullable', 'uuid', 'required_with:blog_id',
                Rule::exists('roles', 'id')->whereNull('workspace_id')->whereNot('key', 'owner'),
            ],
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            /** @var Workspace $workspace */
            $workspace = $this->route('workspace');

            $alreadyMember = DB::table('workspace_members')
                ->join('users', 'users.id', '=', 'workspace_members.user_id')
                ->where('workspace_members.workspace_id', $workspace->id)
                ->whereRaw('lower(users.email) = ?', [$this->input('email')])
                ->exists();

            if ($alreadyMember) {
                $validator->errors()->add('email', __('This person is already a member of the workspace.'));
            }
        }];
    }
}
