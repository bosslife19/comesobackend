<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        
    ];
    

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'data_restriction_pin',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'balance'=>'integer',
            'password' => 'hashed',
            'company_certification_documents'=>'array'
        ];
    }
    protected $appends = [];

    public function transactions(){
        return $this->hasMany(Transaction::class, 'user_id');
    }
    public function beneficiaries(){
        return $this->hasMany(Beneficiary::class);
    }
    public function paymentRequests(){
        return $this->hasMany(PaymentRequest::class, 'user_id');
    }
    public function notifications(){
        return $this->hasMany(Notification::class, 'user_id');
    }
}
