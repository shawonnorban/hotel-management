<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /** Abort with 403 unless the signed-in staff member holds the permission. */
    protected function authorizeAdmin(string $ability): void
    {
        abort_unless(auth('admin')->user()?->can($ability), 403);
    }
}
