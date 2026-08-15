<?php

declare(strict_types=1);

namespace App\Workshop\Infrastructure\Security;

use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Enum\Role;
use App\Workshop\Domain\Entity\Workshop;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, Workshop> */
final class WorkshopVoter extends Voter
{
    public const MANAGE = 'WORKSHOP_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::MANAGE === $attribute && $subject instanceof Workshop;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return \in_array(Role::Admin->value, $user->getRoles(), true) || $subject->getOwner() === $user;
    }
}
