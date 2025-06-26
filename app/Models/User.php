<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Cashier\Billable;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return an array with custom claims to be added to the JWT token.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function isSubscribed()
    {
        return $this->subscribed('default');
    }

    public function hasBasicSubscription()
    {
        return $this->subscribed('default', ['price_1Ra9wzDgYV6zJ17vI6UiuhLp', 'price_1Ra9xgDgYV6zJ17v4zCAQGLZ']);
    }

    public function hasPremiumSubscription()
    {
        return $this->subscribed('default', ['price_1Ra9ynDgYV6zJ17v2HMSLJpe', 'price_1Ra9zADgYV6zJ17v7B4zNE1r']);
    }

    public function getSubscriptionTier()
    {
        if (!$this->isSubscribed()) {
            return 'none';
        }

        if ($this->hasPremiumSubscription()) {
            return 'premium';
        }

        if ($this->hasBasicSubscription()) {
            return 'basic';
        }

        return 'unknown';
    }
}
