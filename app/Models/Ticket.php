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
        'company',
        'ne_product',
        'sw_version',
        'work_order',
        'sub_work_order',
        'attachments',
        'status',
        'handler',
        'start_at',
        'end_at',
        'cancel_at',
    ];

    protected $casts = [
        'attachments' => 'array',
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

    public function externalTechnicalSupport()
    {
        return $this->belongsToMany('App\Models\User', 'ticket_external_technical_support', 'ticket_id', 'user_id');
    }

    public function ticketHistory()
    {
        return $this->hasMany('App\Models\TicketHistory');
    }

    public function getMedia() : array
    {
        $media = [];
        foreach ($this->ticketHistory as $history) {
            if (is_null($history->getMedia())) {
                continue;
            }
            $media = array_merge($media, $history->getMedia());
        }
        if (is_null($this->attachments)) {
            return $media;
        } else {
            return array_merge($media, $this->attachments);
        }
    }
}
