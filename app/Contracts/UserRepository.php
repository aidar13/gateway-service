<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\OtpDestination;
use App\DTO\RegisterUserData;
use App\Enums\OtpChannel;
use App\Models\User;

interface UserRepository
{
    public function create(RegisterUserData $data): User;

    public function find(int $id): ?User;

    public function findByDestination(OtpDestination $destination): ?User;

    public function createFromDestination(OtpDestination $destination): User;

    public function isVerified(User $user, OtpChannel $channel): bool;

    /**
     * Hands the account to the proven owner of the contact: marks it verified, removes the password
     * and every other unverified contact (whoever set them has proved nothing).
     */
    public function claim(User $user, OtpChannel $channel): void;
}
