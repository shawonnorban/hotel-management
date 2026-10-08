<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Base for the small per-employee profile tables (documents, education, experience). */
abstract class HrEmployeeRecord extends Model
{
    protected $guarded = [];
}
