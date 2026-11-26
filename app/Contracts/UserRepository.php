<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\OtpDestinationDTO;
use App\DTO\RegisterUserDataDTO;
use App\Enums\OtpChannel;
use App\Models\User;

interface UserRepository
{
    public function create(RegisterUserDataDTO $data): User;

    public function find(int $id): ?User;

    public function countActiveWithRole(string $role): int;

    public function findByDestination(OtpDestinationDTO $destination): ?User;

    public function createFromDestination(OtpDestinationDTO $destination): User;

    public function isVerified(User $user, OtpChannel $channel): bool;

    /**
     * Hands the account to the proven owner of the contact: marks it verified, removes the password
     * and every other unverified contact (whoever set them has proved nothing).
     */
    public function claim(User $user, OtpChannel $channel): void;
}
