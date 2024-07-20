<?php

declare(strict_types=1);

namespace App\Security\Voter;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class SwitchToCustomerVoter.
 * @extends Voter<'CAN_SWITCH_USER',UserInterface>
 */
class SwitchToCustomerVoter extends Voter
{
    public function __construct(
        private Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, ['CAN_SWITCH_USER'], true)
            && $subject instanceof UserInterface;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous or if the subject is not a user, do not grant access
        if (! $user instanceof UserInterface || ! $subject instanceof UserInterface) {
            return false;
        }

        // you can still check for ROLE_ALLOWED_TO_SWITCH
        if ($this->security->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            return true;
        }

        return false;
    }
}
