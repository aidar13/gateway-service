<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\UserRepository;
use App\DTO\OtpDestinationDTO;
use App\DTO\RegisterUserDataDTO;
use App\Enums\OtpChannel;
use App\Models\User;

final class EloquentUserRepository implements UserRepository
{
    public function create(RegisterUserDataDTO $data): User
    {
        return User::query()->create([
            'name'     => $data->name,
            'username' => $data->username,
            'email'    => $data->email,
            'phone'    => $data->phone,
            'password' => $data->password,
        ]);
    }

    public function find(int $id): ?User
    {
        return User::query()->find($id);
    }

    public function countActiveWithRole(string $role): int
    {
        return User::query()->role($role, 'api')->where('is_active', true)->count();
    }

    public function findByDestination(OtpDestinationDTO $destination): ?User
    {
        return User::query()->where($destination->channel->userColumn(), $destination->value)->first();
    }

    public function createFromDestination(OtpDestinationDTO $destination): User
    {
        $user = new User([
            'name'                              => $destination->value,
            $destination->channel->userColumn() => $destination->value,
            // Set explicitly: a database default would not be visible on the returned model.
            'is_active' => true,
        ]);
        $user->forceFill([$destination->channel->verifiedColumn() => now()])->save();

        return $user;
    }

    public function isVerified(User $user, OtpChannel $channel): bool
    {
        return $user->getAttribute($channel->verifiedColumn()) !== null;
    }

    public function claim(User $user, OtpChannel $channel): void
    {
        $changes = ['password' => null, $channel->verifiedColumn() => now()];

        foreach (OtpChannel::cases() as $other) {
            if ($other !== $channel && !$this->isVerified($user, $other)) {
                $changes[$other->userColumn()] = null;
            }
        }

        $user->forceFill($changes)->save();
    }
}
