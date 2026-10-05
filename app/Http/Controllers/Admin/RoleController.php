<?php

namespace App\Http\Controllers\Admin;

use App\Admin\ResourceRegistry;
use App\Http\Controllers\Controller;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    private const PROTECTED = 'Super Admin';

    public function index()
    {
        return view('admin.roles.index', ['roles' => Role::where('guard_name', 'admin')->withCount(['permissions', 'users'])->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.roles.form', ['role' => null] + $this->matrix());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'admin']);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', 'Role "'.$role->name.'" created.');
    }

    public function edit(Role $role)
    {
        abort_unless($role->guard_name === 'admin', 404);

        return view('admin.roles.form', ['role' => $role] + $this->matrix());
    }

    public function update(Request $request, Role $role)
    {
        abort_unless($role->guard_name === 'admin', 404);
        $data = $this->validated($request, $role);

        if ($role->name !== self::PROTECTED) {
            $role->update(['name' => $data['name']]);
            $role->syncPermissions($data['permissions'] ?? []);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('admin.roles.index')->with('status', 'Role "'.$role->name.'" saved.');
    }

    public function destroy(Role $role)
    {
        abort_unless($role->guard_name === 'admin', 404);
        if ($role->name === self::PROTECTED) {
            return back()->withErrors(['role' => 'The Super Admin role cannot be deleted.']);
        }
        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'This role is assigned to staff accounts. Reassign them first.']);
        }
        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', 'Role deleted.');
    }

    private function validated(Request $request, ?Role $role): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('roles', 'name')->where('guard_name', 'admin')->ignore($role?->id)],
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(Permissions::all())],
        ]);
    }

    /** Permissions grouped for the editor: one row per area with view/create/edit/delete columns. */
    private function matrix(): array
    {
        foreach (Permissions::all() as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $areas = collect(ResourceRegistry::all())->map(fn ($class, $slug) => ['label' => $class::$label, 'group' => $class::$group, 'slug' => $slug]);
        $extra = collect(Permissions::EXTRA);

        return ['areas' => $areas->sortBy([['group', 'asc'], ['label', 'asc']])->values(), 'extra' => $extra];
    }
}
