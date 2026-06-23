<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}
