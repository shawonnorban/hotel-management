<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class BookedDetails extends Model
{
    protected $table = 'booked_details';

    protected $primaryKey = 'book_detailsid';

    public $timestamps = false;

    protected $guarded = [];
}
