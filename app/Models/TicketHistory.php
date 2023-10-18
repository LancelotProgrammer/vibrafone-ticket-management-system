<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'owner',
        'title',
        'body',
        'attachments',
        'work_order',
        'sub_work_order',
        'status',
        'handler',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];
}
