<?php

namespace App\Models;

use App\Enums\UserType;
use App\Enums\UserStatus;
use App\Traits\Relationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * App\Models\User
 *
 * @property int $id
 * @property string $cpf
 * @property string $email
 * @property string $password
 * @property bool $password_reset_required
 * @property string $recovery_token
 * @property string $type
 * @property string $status
 * @property string $full_name
 * @property string $phone
 * @property string $birth_date
 * @property string $zip_code
 * @property string $address
 * @property string $address_number
 * @property string $complement
 * @property string $neighborhood
 * @property string $city
 * @property string $state
 * @property string $last_access_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasApiTokens, SoftDeletes, Relationships;

    protected $fillable = [
        'cpf',
        'email',
        'password',
        'password_reset_required',
        'type',
        'status',
        'full_name',
        'phone',
        'birth_date',
        'zip_code',
        'address',
        'address_number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'last_access_at',
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

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date',
        'last_access_at' => 'datetime',
        'type' => UserType::class,
        'status' => UserStatus::class,
    ];


    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withPivot('enabled');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function hasPermission(string $action, string $resource): bool
    {
        return $this->permissions()
            ->wherePivot('enabled', true)
            ->whereAction($action)
            ->whereResource($resource)
            ->exists();
    }

    public function generateToken(): string
    {
        $this->tokens()->where('expires_at', '<', now())->delete();
        $tokenExpire = now()->addDay();

        return $this->createToken('api-token', [], $tokenExpire)->plainTextToken;
    }

    public function revokeTokens(): void
    {
        $this->tokens()->delete();
    }

    public function updateLastAccess(): void
    {
        $this->update(['last_access_at' => now()]);
    }


    public function loadUserData(): User
    {
        $this->load(['permissions' => function ($query) {
            $query->wherePivot('enabled', true);
        }]);

        return $this;
    }
}
