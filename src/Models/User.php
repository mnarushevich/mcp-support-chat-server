<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    protected $primaryKey = 'id';
    
    protected $table = 'users';
    
    protected $fillable = [
        'email',
        'first_name',
        'last_name',
        'phone',
        'status',
    ];

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }
}
