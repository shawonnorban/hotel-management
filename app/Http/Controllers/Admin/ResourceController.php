<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Field;
use App\Admin\Formatter;
use App\Admin\Resource;
use App\Admin\ResourceRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** One controller serves every declarative {@see Resource}. */
class ResourceController extends Controller
{
    public function index(Request $request, string $resource)
    {
        $res = $this->resolve($resource, 'view');
        $query = $this->baseQuery($res);

        if (($term = trim((string) $request->query('q'))) !== '') {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $columns = $res->searchable();
            $query->where(function ($q) use ($columns, $like, $res) {
                foreach ($columns as $column) {
                    $q->orWhere($res->modelInstance()->qualifyColumn($column), 'like', $like);
                }
                if (! $columns) {
                    $q->whereRaw('1 = 0');
                }
            });
        }

        $model = $res->modelInstance();
        $query->orderBy($res::$orderBy ?? $model->getKeyName(), $res::$orderDirection);

        if ($request->query('export') === 'csv') {
            return $this->exportCsv($res, $query);
        }

        return view('admin.resource.index', [
            'res' => $res,
            'rows' => $query->paginate(20)->withQueryString(),
            'listed' => collect($res->fields())->filter(fn (Field $f) => $f->listed)->values(),
            'term' => $term,
        ]);
    }

    public function create(string $resource)
    {
        $res = $this->resolve($resource, 'create');

        return view('admin.resource.form', ['res' => $res, 'record' => null, 'fields' => $this->formFields($res)]);
    }

    public function store(Request $request, string $resource)
    {
        $res = $this->resolve($resource, 'create');
        $model = $res->modelInstance();
        $data = $this->validated($request, $res, null);
        $data = $res->beforeSave($data, null);

        $model->fill($this->columns($res, $data))->save();
        $res->afterSave($model, $data, true);

        return redirect()->route('admin.resource.index', $resource)->with('status', $res::$singular.' created.');
    }

    public function edit(string $resource, int|string $id)
    {
        $res = $this->resolve($resource, 'edit');

        return view('admin.resource.form', ['res' => $res, 'record' => $this->find($res, $id), 'fields' => $this->formFields($res)]);
    }

    public function update(Request $request, string $resource, int|string $id)
    {
        $res = $this->resolve($resource, 'edit');
        $model = $this->find($res, $id);
        $data = $this->validated($request, $res, $model);
        $data = $res->beforeSave($data, $model);

        $model->fill($this->columns($res, $data))->save();
        $res->afterSave($model, $data, false);

        return redirect()->route('admin.resource.index', $resource)->with('status', $res::$singular.' updated.');
    }

    public function destroy(string $resource, int|string $id)
    {
        $res = $this->resolve($resource, 'delete');
        $model = $this->find($res, $id);

        if ($reason = $res->deleteBlockedReason($model)) {
            return back()->withErrors(['delete' => $reason]);
        }

        try {
            $model->delete();
        } catch (QueryException) {
            return back()->withErrors(['delete' => 'This '.strtolower($res::$singular).' is in use and cannot be deleted.']);
        }

        return redirect()->route('admin.resource.index', $resource)->with('status', $res::$singular.' deleted.');
    }

    /** Only the keys that are real columns (virtual fields go to afterSave only). */
    private function columns(Resource $res, array $data): array
    {
        $virtual = collect($res->fields())->filter(fn (Field $f) => $f->virtual)->pluck('name')->all();

        return array_diff_key($data, array_flip($virtual));
    }

    /** @return list<Field> */
    private function formFields(Resource $res): array
    {
        return array_values(array_filter($res->fields(), fn (Field $f) => ! $f->listOnly));
    }

    private function resolve(string $slug, string $ability): Resource
    {
        $res = ResourceRegistry::find($slug);
        abort_if($res === null, 404);
        $this->authorizeAdmin($slug.'.'.$ability);

        return $res;
    }

    private function baseQuery(Resource $res)
    {
        return $res->query($res::$model::query()->with($res->with()));
    }

    private function find(Resource $res, int|string $id)
    {
        return $this->baseQuery($res)->findOrFail($id);
    }

    private function validated(Request $request, Resource $res, $model): array
    {
        $table = $res->modelInstance()->getTable();
        $rules = [];
        foreach ($this->formFields($res) as $field) {
            $rules[$field->name] = $field->validationRules($model, $table);
            if ($field->type === 'multiselect') {
                $rules[$field->name.'.*'] = [Rule::in(array_keys($field->resolveOptions()))];
            }
            if ($field->type === 'password' && $model) {
                $rules[$field->name] = array_values(array_filter($rules[$field->name], fn ($r) => $r !== 'required'));
                array_unshift($rules[$field->name], 'nullable');
            }
        }

        $validated = $request->validate($rules);
        $data = [];

        foreach ($this->formFields($res) as $field) {
            $name = $field->name;

            if ($field->type === 'image') {
                if ($request->hasFile($name)) {
                    $this->deleteUpload($model?->getAttribute($name));
                    $data[$name] = 'storage/'.$request->file($name)->store('uploads/'.$res::$slug, 'public');
                }

                continue;
            }

            if ($field->type === 'password') {
                if (! empty($validated[$name])) {
                    $data[$name] = Hash::make($validated[$name]);
                }

                continue;
            }

            if ($field->type === 'toggle') {
                $data[$name] = $request->boolean($name) ? 1 : 0;

                continue;
            }

            $data[$name] = $field->type === 'multiselect' ? array_values($validated[$name] ?? []) : ($validated[$name] ?? null);
        }

        return $data;
    }

    private function deleteUpload(?string $path): void
    {
        if ($path && str_starts_with($path, 'storage/uploads/')) {
            Storage::disk('public')->delete(substr($path, strlen('storage/')));
        }
    }

    private function exportCsv(Resource $res, $query): StreamedResponse
    {
        $fields = collect($res->fields())->reject(fn (Field $f) => in_array($f->type, ['password', 'image'], true));

        return response()->streamDownload(function () use ($query, $fields) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $fields->pluck('label')->all());
            $query->clone()->chunk(500, function ($rows) use ($out, $fields) {
                foreach ($rows as $row) {
                    // Prefix formula-looking cells so spreadsheets do not execute them.
                    fputcsv($out, $fields->map(function (Field $f) use ($row) {
                        $v = Formatter::plain($f, $row);

                        return preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;
                    })->all());
                }
            });
            fclose($out);
        }, $res::$slug.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
