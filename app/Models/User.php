<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable  implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'company',
        'department_id',
        'level_id',
        'country_id',
        'blocked_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return is_null($this->blocked_at);
    }

    public function country()
    {
        return $this->belongsTo('App\Models\Country');
    }

    public function department()
    {
        return $this->belongsTo('App\Models\Department');
    }

    public function level()
    {
        return $this->belongsTo('App\Models\Level');
    }

    public function customerTickets()
    {
        return $this->belongsToMany('App\Models\Ticket', 'ticket_customer', 'user_id', 'ticket_id');
    }

    public function technicalSupportTickets()
    {
        return $this->belongsToMany('App\Models\Ticket', 'ticket_technical_support', 'user_id', 'ticket_id');
    }

    public function highTechnicalSupportTickets()
    {
        return $this->belongsToMany('App\Models\Ticket', 'ticket_high_technical_support', 'user_id', 'ticket_id');
    }
}
