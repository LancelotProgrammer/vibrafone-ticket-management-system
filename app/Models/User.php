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
        'department_id',
        'country_id',
        'blocked_at',
        'approved_at',
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
        return is_null($this->blocked_at) && !is_null($this->approved_at);
    }

    public function country()
    {
        return $this->belongsTo('App\Models\Country');
    }

    public function department()
    {
        return $this->belongsTo('App\Models\Department');
    }

    public function customerTickets()
    {
        return $this->hasMany('App\Models\tickets', 'customer_user_id');
    }

    public function technicalSupportTickets()
    {
        return $this->hasMany('App\Models\tickets', 'technical_support_user_id');
    }

    public function highTechnicalSupportTickets()
    {
        return $this->hasMany('App\Models\tickets', 'high_technical_support_user_id');
    }
}
