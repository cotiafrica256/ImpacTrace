<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class PublicUser extends Authenticatable {
 use HasApiTokens, Notifiable;
 protected $fillable=['name','email','phone','password','is_active'];
 protected $hidden=['password','remember_token'];
 protected function casts(): array { return ['password'=>'hashed','is_active'=>'boolean']; }
 public function payments(){return $this->hasMany(Payment::class);}
 public function accesses(){return $this->hasMany(ReadingAccess::class);}
}