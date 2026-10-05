<?php

namespace App\Admin;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * Declarative form/table field used by {@see Resource}.
 */
class Field
{
    public string $type = 'text';

    public string|array $rules = [];

    public array|Closure $options = [];

    public mixed $default = null;

    public ?string $help = null;

    public int $col = 6;

    public bool $listed = false;

    public bool $isUnique = false;

    /** Shown in the table only; never rendered in the form or saved. */
    public bool $listOnly = false;

    /** Not a column of the model: handed to Resource::afterSave() instead of being saved. */
    public bool $virtual = false;

    /** Computes the form value for an existing record (for virtual fields). */
    public ?Closure $valueFrom = null;

    public ?string $prefix = null;

    public ?string $placeholder = null;

    public array $attributes = [];

    private function __construct(public string $name, public string $label) {}

    public static function make(string $name, string $label, string $type = 'text'): static
    {
        $field = new static($name, $label);
        $field->type = $type;

        return $field;
    }

    public static function text(string $name, string $label): static
    {
        return static::make($name, $label);
    }

    public static function email(string $name, string $label): static
    {
        return static::make($name, $label, 'email');
    }

    public static function password(string $name, string $label): static
    {
        return static::make($name, $label, 'password');
    }

    public static function number(string $name, string $label): static
    {
        return static::make($name, $label, 'number');
    }

    public static function decimal(string $name, string $label): static
    {
        return static::make($name, $label, 'decimal');
    }

    public static function money(string $name, string $label): static
    {
        return static::make($name, $label, 'money');
    }

    public static function textarea(string $name, string $label): static
    {
        return static::make($name, $label, 'textarea')->col(12);
    }

    public static function date(string $name, string $label): static
    {
        return static::make($name, $label, 'date');
    }

    public static function time(string $name, string $label): static
    {
        return static::make($name, $label, 'time');
    }

    public static function datetime(string $name, string $label): static
    {
        return static::make($name, $label, 'datetime');
    }

    public static function toggle(string $name, string $label): static
    {
        return static::make($name, $label, 'toggle')->default(1);
    }

    /** @param array<int|string,string>|Closure():array<int|string,string> $options */
    public static function multiselect(string $name, string $label, array|Closure $options): static
    {
        $field = static::make($name, $label, 'multiselect');
        $field->options = $options;

        return $field;
    }

    public static function image(string $name, string $label): static
    {
        return static::make($name, $label, 'image');
    }

    /** @param array<int|string,string>|Closure():array<int|string,string> $options */
    public static function select(string $name, string $label, array|Closure $options): static
    {
        $field = static::make($name, $label, 'select');
        $field->options = $options;

        return $field;
    }

    /** Add validation rules (pipe-separated string or array); earlier rules such as "required" are kept. */
    public function rules(string|array $rules): static
    {
        $this->rules = array_values(array_unique(array_merge($this->ruleList(), is_string($rules) ? explode('|', $rules) : $rules)));

        return $this;
    }

    public function required(): static
    {
        $this->rules = array_values(array_unique(array_merge(['required'], $this->ruleList())));

        return $this;
    }

    public function isRequired(): bool
    {
        return in_array('required', $this->ruleList(), true);
    }

    /** @return list<mixed> */
    private function ruleList(): array
    {
        return is_string($this->rules) ? ($this->rules === '' ? [] : explode('|', $this->rules)) : $this->rules;
    }

    public function unique(): static
    {
        $this->isUnique = true;

        return $this;
    }

    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }

    public function help(string $text): static
    {
        $this->help = $text;

        return $this;
    }

    public function col(int $col): static
    {
        $this->col = $col;

        return $this;
    }

    public function listed(): static
    {
        $this->listed = true;

        return $this;
    }

    public function listOnly(): static
    {
        $this->listOnly = true;
        $this->listed = true;

        return $this;
    }

    public function virtual(?Closure $valueFrom = null): static
    {
        $this->virtual = true;
        $this->valueFrom = $valueFrom;

        return $this;
    }

    public function prefix(string $text): static
    {
        $this->prefix = $text;

        return $this;
    }

    public function placeholder(string $text): static
    {
        $this->placeholder = $text;

        return $this;
    }

    public function attr(string $name, string|int|float $value): static
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /** @return array<int|string,string> */
    public function resolveOptions(): array
    {
        return $this->options instanceof Closure ? ($this->options)() : $this->options;
    }

    /** Validation rules with sensible per-type defaults. */
    public function validationRules(?Model $model, string $table): array
    {
        $rules = $this->ruleList();
        $required = $this->isRequired();

        $base = match ($this->type) {
            'email' => ['email', 'max:255'],
            'number' => ['integer'],
            'decimal', 'money' => ['numeric'],
            'date' => ['date'],
            'time' => ['date_format:H:i'],
            'datetime' => ['date'],
            'toggle' => ['boolean'],
            'image' => ['image', 'max:4096'],
            'select' => [],
            'multiselect' => ['array'],
            default => ['string'],
        };

        if ($this->type === 'password') {
            $base = ['string', 'min:8', 'max:255'];
        }

        $out = array_merge($required ? [] : ['nullable'], $rules, $base);

        if ($this->isUnique) {
            $unique = Rule::unique($table, $this->name);
            if ($model) {
                $unique->ignore($model->getKey(), $model->getKeyName());
            }
            $out[] = $unique;
        }

        if ($this->type === 'select' && ! $this->options instanceof Closure) {
            $out[] = Rule::in(array_keys($this->options));
        }

        return array_values(array_unique($out, SORT_REGULAR));
    }
}
