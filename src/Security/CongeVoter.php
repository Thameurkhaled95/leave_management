<?php

namespace App\Security;

use App\Entity\Conge;
use App\Entity\Employe;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CongeVoter extends Voter
{
    // these strings are just invented: you can use anything
    const VIEW = 'view';
    const EDIT = 'edit';
    const STATS = 'stats';

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::STATS])) {
            return false;
        }

        // only vote on `conge` objects
        if (!$subject instanceof Conge) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof Employe) {
            // the user must be logged in; if not, deny access
            return false;
        }

        // you know $subject is a conge object, thanks to `supports()`
        /** @var Conge $conge */
        $conge = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($conge, $user),
            self::EDIT => $this->canEdit($conge, $user),
            self::STATS => $this->canStats($conge, $user),
            default => throw new \LogicException('This code should not be reached!')
        };
    }

    private function canView(Conge $conge, Employe $user): bool
    {
        // if they can edit, they can view
        if ($this->canEdit($conge, $user)) {
            return true;
        }

        // the conge object could have, for example, a method `isPrivate()`
        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function canEdit(Conge $conge, Employe $user): bool
    {
        // this assumes that the conge object has a `getOwner()` method
        return $user === $conge->getEmploye() && $conge->getStatus() ==='attente';
    }

    private function canStats(Conge $conge, Employe $user): bool
    {
        // this assumes that the conge object has a `getOwner()` method
        return  $this->security->isGranted('ROLE_ADMIN') && $user !== $conge->getEmploye();
    }
}