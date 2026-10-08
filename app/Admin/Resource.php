<?php

namespace App\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A back-office master-data screen (list + create + edit + delete) described declaratively.
 * One generic controller and one set of views render every resource, so all of them behave
 * and look the same and are protected by the same permissions: "{slug}.view|create|edit|delete".
 */
abstract class Resource
{
    /** @var class-string<Model> */
    public static string $model;

    public static string $slug;

    public static string $label;

    public static string $singular;

    public static string $icon = 'bi-circle';

    /** Sidebar group. */
    public static string $group = 'Setup';

    /** Column used for the default ordering (falls back to the primary key). */
    public static ?string $orderBy = null;

    public static string $orderDirection = 'desc';

    /** @return list<Field> */
    abstract public function fields(): array;

    /** Fields searched by the list's search box. */
    public function searchable(): array
    {
        return collect($this->fields())->filter(fn (Field $f) => in_array($f->type, ['text', 'email', 'textarea'], true))->pluck('name')->all();
    }

    /** Relations to eager load on the list. */
    public function with(): array
    {
        return [];
    }

    public function query(Builder $query): Builder
    {
        return $query;
    }

    /** Last chance to alter validated data before it is saved. */
    public function beforeSave(array $data, ?Model $model): array
    {
        return $data;
    }

    public function afterSave(Model $model, array $data, bool $created): void {}

    /** Return a message to block deletion, or null to allow it. */
    public function deleteBlockedReason(Model $model): ?string
    {
        return null;
    }

    /**
     * Extra buttons on each row: [['label' => 'Hire', 'icon' => 'bi-person-plus', 'url' => '…', 'method' => 'post'|'get', 'permission' => '…'?]].
     *
     * @return list<array{label:string,icon?:string,url:string,method?:string,permission?:string}>
     */
    public function rowActions(Model $row): array
    {
        return [];
    }

    /**
     * Show the list as one row per parent with its items inside (instead of one row per record).
     * Return null for the normal table, or:
     *   'key'     => column the rows are grouped by,
     *   'parent'  => fn (mixed $key): string          label of the parent,
     *   'heading' => 'Room type',                       column title,
     *   'item'    => fn (Model $row): array{text:string,sub?:string,image?:string,url?:string},
     *   'manage'  => fn (mixed $key): ?string          link to edit the whole group (optional),
     *   'manage_label' => 'Edit room type',
     *   'add'     => bool                                show the "Add" button (default true).
     *
     * @return array<string,mixed>|null
     */
    public function grouped(): ?array
    {
        return null;
    }

    public function modelInstance(): Model
    {
        return new (static::$model);
    }

    /** @return list<string> */
    public static function permissions(): array
    {
        return array_map(fn ($a) => static::$slug.'.'.$a, ['view', 'create', 'edit', 'delete']);
    }
}
