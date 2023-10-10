<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_identifier',
        'type_id',
        'priority_id',
        'department_id',
        'category_id',
        'customer_user_id',
        'technical_support_user_id',
        'high_technical_support_user_id',
        'title',
        'description',
        'ne_product',
        'sw_version',
        'work_order',
        'sub_work_order',
        'attachments',
        'status',
        'handler',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function type()
    {
        return $this->belongsTo('App\Models\Type');
    }

    public function priority()
    {
        return $this->belongsTo('App\Models\Priority');
    }

    public function department()
    {
        return $this->belongsTo('App\Models\Department');
    }

    public function category()
    {
        return $this->belongsTo('App\Models\Category');
    }

    public function customer()
    {
        return $this->belongsTo('App\Models\User', 'customer_user_id');
    }

    public function technicalSupport()
    {
        return $this->belongsTo('App\Models\User', 'technical_support_user_id');
    }

    public function highTechnicalSupport()
    {
        return $this->belongsTo('App\Models\User', 'high_technical_support_user_id');
    }

    public function ticketHistory()
    {
        return $this->hasMany('App\Models\TicketHistory');
    }
}
