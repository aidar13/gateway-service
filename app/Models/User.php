<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string|null $username
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $password
 * @property bool $is_active
 */
class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;

    protected string $guard_name = 'api';

    protected $fillable = ['name', 'username', 'email', 'phone', 'password', 'is_active'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_active' => 'boolean'];
    }

    /** Passport password grant: the "login" may be a username, e-mail or phone. */
    public function findForPassport(string $login): ?self
    {
        return self::query()
            ->where(fn ($q) => $q->where('username', $login)->orWhere('email', $login)->orWhere('phone', $login))
            ->first();
    }

    public function validateForPassportPasswordGrant(string $password): bool
    {
        return $this->is_active
            && $this->password !== null
            && Hash::check($password, $this->password);
    }
}
