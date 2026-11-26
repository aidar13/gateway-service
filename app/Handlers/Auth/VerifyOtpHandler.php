<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use App\Auth\OtpSettings;
use App\Auth\TokenRevoker;
use App\Commands\Auth\VerifyOtpCommand;
use App\Contracts\OtpRepository;
use App\Contracts\UserRepository;
use App\DTO\OtpDestinationDTO;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;

/**
 * Exchanges (hash, code) for a user. The account is created only now, after the person
 * has proved they own the phone/e-mail — requesting a code never creates anything.
 */
final readonly class VerifyOtpHandler
{
    public function __construct(
        private OtpRepository $codes,
        private UserRepository $users,
        private Hasher $hasher,
        private OtpSettings $settings,
        private TokenRevoker $tokens,
    ) {
    }

    /** @return User|null null when the code is wrong, expired, used up or the account is disabled */
    public function handle(VerifyOtpCommand $command): ?User
    {
        $hash = $command->hash;
        $code = $command->code;

        $otp = $this->codes->findByHash($hash);

        if ($otp === null || !$otp->isUsable($this->settings->maxAttempts)) {
            return null;
        }

        // Count the attempt before checking, so parallel guesses cannot exceed the budget.
        if (!$this->codes->registerAttempt($otp, $this->settings->maxAttempts)) {
            return null;
        }

        if (!$this->hasher->check($code, $otp->code_hash) || !$this->codes->consume($otp)) {
            return null;
        }

        $destination = new OtpDestinationDTO($otp->channel, $otp->destination);
        $user        = $this->users->findByDestination($destination);

        if ($user === null) {
            $user = $this->users->createFromDestination($destination);
            $user->assignRole('client');

            return $user;
        }

        if (!$user->is_active) {
            return null;
        }

        // Account pre-hijacking guard: somebody may have registered this contact without owning it.
        if (!$this->users->isVerified($user, $destination->channel)) {
            $this->tokens->revokeAll($user);
            $this->users->claim($user, $destination->channel);
        }

        return $user;
    }
}
