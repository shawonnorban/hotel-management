<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class StaffResource extends Resource
{
    public static string $model = User::class;

    public static string $slug = 'staff';

    public static string $label = 'Staff accounts';

    public static string $singular = 'Staff account';

    public static string $icon = 'bi-person-badge';

    public static string $group = 'Administration';

    public static ?string $orderBy = 'id';

    public static string $orderDirection = 'asc';

    public function with(): array
    {
        return ['roles'];
    }

    public function query(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('usertype', 1);
    }

    public function searchable(): array
    {
        return ['firstname', 'lastname', 'email'];
    }

    public function fields(): array
    {
        return [
            Field::text('firstname', 'First name')->required()->rules('max:50')->listed(),
            Field::text('lastname', 'Last name')->rules('max:50')->listed(),
            Field::email('email', 'Email')->required()->rules('max:100')->unique()->listed(),
            Field::password('password', 'Password')->required()->help('At least 8 characters. Leave blank when editing to keep the current password.'),
            Field::multiselect('role_names', 'Roles', fn () => Role::where('guard_name', 'admin')->orderBy('name')->pluck('name', 'name')->all())->required()->virtual(fn ($user) => $user->roles->pluck('name')->all()),
            Field::toggle('status', 'Can sign in')->listed(),
            Field::datetime('last_login', 'Last sign-in')->listOnly(),
        ];
    }

    public function beforeSave(array $data, ?Model $model): array
    {
        $data['usertype'] = 1;
        $data['is_admin'] = in_array('Super Admin', $data['role_names'] ?? [], true) ? 1 : 0;

        // Never lock everyone out: the last active Super Admin keeps the role and stays enabled.
        if ($model && $model->hasRole('Super Admin') && (! in_array('Super Admin', $data['role_names'] ?? [], true) || ! $data['status'])) {
            $others = User::role('Super Admin')->where('status', 1)->whereKeyNot($model->getKey())->exists();
            if (! $others) {
                throw ValidationException::withMessages(['role_names' => 'There must always be at least one active Super Admin.']);
            }
        }

        return $data;
    }

    public function afterSave(Model $model, array $data, bool $created): void
    {
        $model->syncRoles($data['role_names'] ?? []);
    }

    public function deleteBlockedReason(Model $model): ?string
    {
        if ($model->getKey() === auth('admin')->id()) {
            return 'You cannot delete your own account.';
        }
        if ($model->hasRole('Super Admin') && ! User::role('Super Admin')->where('status', 1)->whereKeyNot($model->getKey())->exists()) {
            return 'This is the last active Super Admin.';
        }

        return null;
    }
}
