<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ZepherService
{

    private $session;
    private $containerBag;

    public function __construct(SessionInterface $session, ContainerBagInterface $containerBag)
    {
        $this->session = $session;
        $this->containerBag = $containerBag;
    }


    public static function getFeatureSubstr(): string
    {
        // This should be a common substring of your feature ids. (Zepher allows human-readable feature and permission ids.)
        return 'FEATURE_';
    }


    public function getCurrentAccountId()
    {
        return $this->session->get('account.id');
    }

    public function getCurrentAccountDomainId()
    {
        return $this->session->get('account.domain_id');
    }

    public function getZepherConfigDir()
    {
        return $this->containerBag->get('zepher.config_dir');
    }
}