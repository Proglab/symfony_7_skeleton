<?php
namespace App\Security\Voter;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class SwitchToCustomerVoter extends Voter
{
    public function __construct(
        private Security $security,
    ) {
    }

    protected function supports($attribute, $subject): bool
    {
        return in_array($attribute, ['CAN_SWITCH_USER'])
            && $subject instanceof UserInterface;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous or if the subject is not a user, do not grant access
        if (!$user instanceof UserInterface || !$subject instanceof UserInterface) {
            return false;
        }


        // you can still check for ROLE_ALLOWED_TO_SWITCH
        if ($this->security->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            return true;
        }

        return false;
    }
}