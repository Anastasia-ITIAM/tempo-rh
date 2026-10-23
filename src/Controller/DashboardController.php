<?php

namespace App\Controller;

use App\Controller\Admin\AppSettingCrudController;
use App\Controller\Admin\ExpenseReport\ExpenseReportCrudController;
use App\Controller\Admin\LeaveRequest\LeaveRequestCrudController;
use App\Controller\Admin\TimeSheet\EasyAdminController\ClientCrudController;
use App\Controller\Admin\TimeSheet\EasyAdminController\ProjectCrudController;
use App\Controller\Admin\TimeSheet\EasyAdminController\TaskCrudController;
use App\Controller\Admin\UserCrudController;
use App\Controller\Common\MyProfileCrudController;
use App\Controller\User\ExpenseReport\UserExpenseReportCrudController;
use App\Controller\User\LeaveRequest\UserLeaveRequestCrudController;
use App\Service\Admin\LeaveRequest\LeaveTypeRenewalService;
use App\Service\Branding\BrandingService;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminDashboard(routePath: '/', routeName: 'admin')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly BrandingService $branding,
        private readonly LeaveTypeRenewalService $leaveTypeRenewalService,
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function index(): Response
    {
        $this->leaveTypeRenewalService->renewIfExpired();

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_leave_request_index');
        }

        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('admin_user_leave_request_index');
        }

        return $this->redirectToRoute('app_login');
    }

    public function configureDashboard(): Dashboard
    {
        $logoPath = $this->branding->getLogoUrl();

        return Dashboard::new()
            ->setTitle('
                    <div style="width:100%; text-align:center;">
                        <img src="' . $logoPath . '" alt="TempoRH" height="80">
                    </div>
                ')
            ->setFaviconPath($logoPath);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('Administration')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(LeaveRequestCrudController::class, 'Gestion des demandes de congés', 'fa fa-calendar')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(ExpenseReportCrudController::class, 'Gestion des notes de frais', 'fa fa-file')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(UserCrudController::class, 'Utilisateurs', 'fa fa-user-circle')->setPermission('ROLE_ADMIN');



        yield MenuItem::section('Mes demandes');
        yield MenuItem::linkTo(UserLeaveRequestCrudController::class, 'Mes congés', 'fa fa-calendar');
        yield MenuItem::linkTo(UserExpenseReportCrudController::class, 'Mes notes de frais', 'fa fa-file');

        yield MenuItem::section('TimeSheet');
        yield MenuItem::subMenu('Admin', 'fa fa-user-shield')->setSubItems([
            MenuItem::linkToUrl('Dashboard', 'fa fa-table', '/admin/timesheet')->setPermission('ROLE_ADMIN'),
            MenuItem::linkTo(ClientCrudController::class, 'Clients', 'fa fa-user')->setAction('index')->setPermission('ROLE_ADMIN'),
            MenuItem::linkTo(ProjectCrudController::class, 'Projets', 'fa fa-folder')->setAction('index')->setPermission('ROLE_ADMIN'),
            MenuItem::linkTo(TaskCrudController::class, 'Tâches', 'fa fa-tasks')->setAction('index')->setPermission('ROLE_ADMIN'),
        ]);
        yield MenuItem::linkToUrl('Mes Timesheets', 'fa fa-clock', '/timesheet/user/timesheet');




        yield MenuItem::section('Paramètres')->setPermission('ROLE_ADMIN');
        yield MenuItem::linkTo(AppSettingCrudController::class, 'Personnalisation', 'fa fa-palette')->setPermission('ROLE_ADMIN');

        yield MenuItem::section('Mon compte');
        yield MenuItem::linkTo(MyProfileCrudController::class, 'Mon profil', 'fa fa-id-card');
        yield MenuItem::linkToLogout('Deconnexion', 'fa fa-sign-out-alt');
    }
}
