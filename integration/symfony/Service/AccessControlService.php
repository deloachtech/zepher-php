<?php

namespace DeLoachTech\AppCoreBundle\Security;

use App\Service\ZepherService;
use App\Repository\AccessRepository;
use DeLoachTech\Zepher\Zepher;

class AccessControlService extends Zepher
{
    public function __construct(
        AccessRepository      $accessRepository,
        ZepherService $zepherService
    )
    {
        parent::__construct(

            $zepherService->getCurrentAccountId(),
            $zepherService->getCurrentAccountDomainId(),
            $accessRepository,
            $zepherService->getZepherConfigDir()
        );
    }


    // Add additional methods for more Zepher functionality...
}