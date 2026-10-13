<?php

namespace App\Enum\Notification;

enum NotificationFlag: string
{
    // --- Accounts ---
    case AccountCreationToAdmin = 'account_created_to_admin';
    case AccountCreationToUser = 'account_created_to_user';

    // --- Leave Requests ---
    case LeaveRequestCreatedByUserToAdmin = 'leave_request_created_by_user_to_admin';
    case LeaveRequestCreatedByUserToUser = 'leave_request_created_by_user_to_user';
    case LeaveRequestCreatedByAdminToAdmin = 'leave_request_created_by_admin_to_admin';
    case LeaveRequestCreatedByAdminToUser = 'leave_request_created_by_admin_to_user';
    case LeaveRequestConfirmedToUser = 'leave_request_confirmed_to_user';
    case LeaveRequestConfirmedToAdmin = 'leave_request_confirmed_to_admin';
    case LeaveRequestCancellationRequestedToUser = 'leave_request_cancellation_requested_to_user';
    case LeaveRequestCancellationRequestedToAdmin = 'leave_request_cancellation_requested_to_admin';
    case LeaveRequestCancelledToUser = 'leave_request_cancelled_to_user';
    case LeaveRequestCancelledToAdmin = 'leave_request_cancelled_to_admin';
    case LeaveRequestDeniedToUser = 'leave_request_denied_to_user';
    case LeaveRequestDeniedToAdmin = 'leave_request_denied_to_admin';
    case LeaveRequestInvalidToUser = 'leave_request_invalid_to_user';
    case LeaveRequestInvalidToAdmin = 'leave_request_invalid_to_admin';

    // --- Expense Reports ---
    case ExpenseReportCreatedByUserToAdmin = 'expense_report_created_by_user_to_admin';
    case ExpenseReportCreatedByUserToUser = 'expense_report_created_by_user_to_user';
    case ExpenseReportCreatedByAdminToAdmin = 'expense_report_created_by_admin_to_admin';
    case ExpenseReportCreatedByAdminToUser = 'expense_report_created_by_admin_to_user';
    case ExpenseReportInvalidToAdmin = 'expense_report_invalid_to_admin';
    case ExpenseReportInvalidToUser = 'expense_report_invalid_to_user';
    case ExpenseReportRefundedToAdmin = 'expense_report_refunded_to_admin';
    case ExpenseReportRefundedToUser = 'expense_report_refunded_to_user';

    // --- TimeSheets ---
    case TimeSheetWeekSubmittedByUserToUser = 'time_sheet_week_submitted_to_user';

    case TimeSheetWeekSubmittedByUserToAdmin = 'time_sheet_week_submitted_to_admin';

    case TimeSheetWeekApprovedByAdminToUser = 'time_sheet_week_approved_to_user';

    case TimeSheetWeekApprovedByAdminToAdmin = 'time_sheet_week_approved_to_admin';
    case TimeSheetWeekRejectedByAdminToUser = 'time_sheet_week_rejected_to_user';
    case TimeSheetWeekRejectedByAdminToAdmin = 'time_sheet_week_rejected_to_admin';

    public function getLabel(): string
    {
        return match($this) {
            self::AccountCreationToAdmin => 'Nouvel utilisateur créé',
            self::AccountCreationToUser => 'Création de votre compte',
            self::LeaveRequestCreatedByUserToAdmin => 'Nouvelle demande de congé',
            self::LeaveRequestCreatedByUserToUser => 'Confirmation de création de demande de congé',
            self::LeaveRequestCreatedByAdminToAdmin => 'Nouvelle demande de congé créée par l’admin',
            self::LeaveRequestCreatedByAdminToUser => 'Confirmation de la création demande de congé par l’admin',
            self::LeaveRequestConfirmedToAdmin => "Demande est acceptée",
            self::LeaveRequestConfirmedToUser => "Confirmation de l'acception de votre demande",
            self::LeaveRequestCancellationRequestedToAdmin => "Nouvelle demande d'annulation",
            self::LeaveRequestCancellationRequestedToUser => "Confirmation de la demande d'annulation",
            self::LeaveRequestCancelledToAdmin => "Demande est annulée",
            self::LeaveRequestCancelledToUser => "Confirmation de l'annulation de votre demande",
            self::LeaveRequestDeniedToAdmin => "Demande est refusée",
            self::LeaveRequestDeniedToUser => "Confirmation du refus de votre demande",
            self::LeaveRequestInvalidToAdmin => "Demande est invalide",
            self::LeaveRequestInvalidToUser => "Votre demande de congé est invalide",
            self::ExpenseReportCreatedByUserToAdmin => "Nouvelle note de frais",
            self::ExpenseReportCreatedByUserToUser => "Confirmation de création d'une note de frais",
            self::ExpenseReportCreatedByAdminToAdmin => "Nouvelle note de frais créée par l’admin",
            self::ExpenseReportCreatedByAdminToUser => "Confirmation de la création d'une note de frais par l'admin",
            self::ExpenseReportInvalidToAdmin => "Note de frais est invalide",
            self::ExpenseReportInvalidToUser => "Votre note de frais est invalide",
            self::ExpenseReportRefundedToAdmin => "Note de frais est remboursée",
            self::ExpenseReportRefundedToUser => "Votre note de frais a été remboursée",
            self::TimeSheetWeekSubmittedByUserToUser => "Votre feuille de temps a été soumise",
            self::TimeSheetWeekSubmittedByUserToAdmin => "Nouvelle feuille de temps a été soumise",
            self::TimeSheetWeekApprovedByAdminToUser => "Votre feuille de temps a été validée",
            self::TimeSheetWeekApprovedByAdminToAdmin => "La feuille de temps a été validée",
            self::TimeSheetWeekRejectedByAdminToUser => "Votre feuille de temps a été rejetée",
            self::TimeSheetWeekRejectedByAdminToAdmin => "La feuille de temps a été rejetée",

        };
    }

    public function getTemplate(): string
    {
        return match($this) {
            self::AccountCreationToAdmin => 'notification/account/admin/account_creation.html.twig',
            self::AccountCreationToUser => 'notification/account/user/account_creation.html.twig',
            self::LeaveRequestCreatedByUserToAdmin => 'notification/leave_request/admin/created_by_user.html.twig',
            self::LeaveRequestCreatedByUserToUser => 'notification/leave_request/user/created_by_user.html.twig',
            self::LeaveRequestCreatedByAdminToAdmin => 'notification/leave_request/admin/created_by_admin.html.twig',
            self::LeaveRequestCreatedByAdminToUser => 'notification/leave_request/user/created_by_admin.html.twig',
            self::LeaveRequestConfirmedToAdmin => 'notification/leave_request/admin/confirmed.html.twig',
            self::LeaveRequestConfirmedToUser => 'notification/leave_request/user/confirmed.html.twig',
            self::LeaveRequestCancellationRequestedToAdmin => 'notification/leave_request/admin/cancellation_requested.html.twig',
            self::LeaveRequestCancellationRequestedToUser => 'notification/leave_request/user/cancellation_requested.html.twig',
            self::LeaveRequestCancelledToAdmin => 'notification/leave_request/admin/cancelled.html.twig',
            self::LeaveRequestCancelledToUser => 'notification/leave_request/user/cancelled.html.twig',
            self::LeaveRequestDeniedToAdmin => 'notification/leave_request/admin/denied.html.twig',
            self::LeaveRequestDeniedToUser => 'notification/leave_request/user/denied.html.twig',
            self::LeaveRequestInvalidToAdmin => 'notification/leave_request/admin/invalid.html.twig',
            self::LeaveRequestInvalidToUser => 'notification/leave_request/user/invalid.html.twig',
            self::ExpenseReportCreatedByUserToAdmin => 'notification/expense_report/admin/created_by_user.html.twig',
            self::ExpenseReportCreatedByUserToUser => 'notification/expense_report/user/created_by_user.html.twig',
            self::ExpenseReportCreatedByAdminToAdmin => 'notification/expense_report/admin/created_by_admin.html.twig',
            self::ExpenseReportCreatedByAdminToUser => 'notification/expense_report/user/created_by_admin.html.twig',
            self::ExpenseReportInvalidToAdmin => 'notification/expense_report/admin/invalid.html.twig',
            self::ExpenseReportInvalidToUser => 'notification/expense_report/user/invalid.html.twig',
            self::ExpenseReportRefundedToAdmin => 'notification/expense_report/admin/refunded.html.twig',
            self::ExpenseReportRefundedToUser => 'notification/expense_report/user/refunded.html.twig',
            self::TimeSheetWeekSubmittedByUserToUser => 'notification/timesheet/user/submitted.html.twig',
            self::TimeSheetWeekSubmittedByUserToAdmin => 'notification/timesheet/admin/submitted.html.twig',
            self::TimeSheetWeekApprovedByAdminToUser => 'notification/timesheet/user/approved.html.twig',
            self::TimeSheetWeekApprovedByAdminToAdmin => 'notification/timesheet/admin/approved.html.twig',
            self::TimeSheetWeekRejectedByAdminToUser => 'notification/timesheet/user/rejected.html.twig',
            self::TimeSheetWeekRejectedByAdminToAdmin => 'notification/timesheet/admin/rejected.html.twig',
        };
    }
}
