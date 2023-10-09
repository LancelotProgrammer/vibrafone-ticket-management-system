<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TicketHistory extends Pivot
{
    protected $table = 'ticket_history';

    protected $fillable = [
        'ticket_id',
        'title',
        'body',
        'work_order',
        'sub_work_order',
        'created_at',
        'updated_at',
    ];
}
