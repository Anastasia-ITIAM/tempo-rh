<?php

namespace App\Service\EasyAdmin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Controller\DashboardControllerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Factory\AdminContextFactory;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class EasyAdminContextService
{
    public function __construct(
        private RequestStack $requestStack,
        private AdminContextFactory $adminContextFactory,
    ) {
    }

    public function setEasyAdminContext(DashboardControllerInterface $dashboard): void
    {
        $context = $this->adminContextFactory->create(
            $this->requestStack->getCurrentRequest(),
            $dashboard,  // instance du dashboard
            null,
        );
        $this->requestStack->getCurrentRequest()->attributes->set(EA::CONTEXT_REQUEST_ATTRIBUTE, $context);
    }
}
