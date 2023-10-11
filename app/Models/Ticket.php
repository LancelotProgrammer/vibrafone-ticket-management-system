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
        'level_id',
        'priority_id',
        'department_id',
        'category_id',
        'title',
        'description',
        'ne_product',
        'sw_version',
        'work_order',
        'sub_work_order',
        'customer_attachments',
        'technical_support_attachments',
        'high_technical_support_attachments',
        'status',
        'handler',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'customer_attachments' => 'array',
        'technical_support_attachments' => 'array',
        'high_technical_support_attachments' => 'array',
    ];

    public function type()
    {
        return $this->belongsTo('App\Models\Type');
    }

    public function level()
    {
        return $this->belongsTo('App\Models\Level');
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
        return $this->belongsToMany('App\Models\User', 'ticket_customer', 'ticket_id', 'user_id');
    }

    public function technicalSupport()
    {
        return $this->belongsToMany('App\Models\User', 'ticket_technical_support', 'ticket_id', 'user_id');
    }

    public function highTechnicalSupport()
    {
        return $this->belongsToMany('App\Models\User', 'ticket_high_technical_support', 'ticket_id', 'user_id');
    }

    public function ticketHistory()
    {
        return $this->hasMany('App\Models\TicketHistory');
    }
}
