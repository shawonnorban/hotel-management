<?php

namespace App\Admin;

/** Central list of all generic resources. */
class ResourceRegistry
{
    /** @var array<string,class-string<Resource>>|null */
    private static ?array $map = null;

    /** @return array<string,class-string<Resource>> slug => resource class */
    public static function all(): array
    {
        if (self::$map === null) {
            self::$map = [];
            foreach (glob(app_path('Admin/Resources/*.php')) as $file) {
                $class = 'App\\Admin\\Resources\\'.basename($file, '.php');
                if (is_subclass_of($class, Resource::class) && ! (new \ReflectionClass($class))->isAbstract()) {
                    self::$map[$class::$slug] = $class;
                }
            }
            ksort(self::$map);
        }

        return self::$map;
    }

    public static function find(string $slug): ?Resource
    {
        $class = self::all()[$slug] ?? null;

        return $class ? new $class : null;
    }

    /** @return list<string> */
    public static function slugs(): array
    {
        return array_keys(self::all());
    }

    /** @return list<string> every permission needed by the resources */
    public static function permissions(): array
    {
        return collect(self::all())->flatMap(fn ($class) => $class::permissions())->values()->all();
    }
}
