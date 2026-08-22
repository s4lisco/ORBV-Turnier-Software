<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Verein extends Authenticatable
{
    protected $table = 'vereine';

    protected $fillable = ['name', 'email', 'username', 'password'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function turniere(): HasMany
    {
        return $this->hasMany(Turnier::class);
    }
}
