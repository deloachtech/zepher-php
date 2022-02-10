<?php


namespace DeLoachTech\AppCoreBundle\Security;

use App\Service\ZepherService;

// Your class that implements the Symfony\Component\Security\Core\User\UserInterface
use App\Entity\User;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AccessControlVoter extends Voter
{
    private $accessControlService;

    public function __construct(AccessControlService $accessControlService)
    {
        $this->accessControlService = $accessControlService;
    }


    protected function supports(string $attribute, $subject): bool
    {
        if (substr($attribute, 0, strlen(ZepherService::getFeatureSubstr())) != ZepherService::getFeatureSubstr()) {
            return false;
        }
        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return $this->accessControlService->userCanAccess($attribute, $subject);
    }
}