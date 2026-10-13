<?php

namespace App\DataFixtures;

use App\Entity\ExpenseReport\ExpenseReport;
use App\Entity\LeaveRequest\LeaveRequest;
use App\Entity\LeaveRequest\LeaveType;
use App\Entity\LeaveRequest\Period;
use App\Entity\LeaveRequest\Reason;
use App\Entity\User;
use App\Enum\LeaveRequest\LeaveTypeCode;
use App\Enum\LeaveRequest\Status as LeaveStatus;
use App\Enum\ExpenseReport\Status as ExpenseStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // --- USERS ---
        $admin = new User();
        $admin->setEmail('admin@temporh.example');
        $admin->setFirstName('Admin');
        $admin->setLastName('System');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password'));
        $manager->persist($admin);

        $users = [];
        for ($i = 1; $i <= 5; ++$i) {
            $user = new User();
            $user->setEmail("user$i@temporh.example");
            $user->setFirstName("User$i");
            $user->setLastName("Test");
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $manager->persist($user);
            $users[] = $user;
        }
        $manager->flush();

        // --- REASONS ---
        $reasonsData = [
            ['type' => 'vacation', 'label' => 'Congés Payés'],
            ['type' => 'rtt', 'label' => 'RTT'],
            ['type' => 'sick', 'label' => 'Maladie'],
            ['type' => 'other', 'label' => 'Autre'],
        ];

        $reasons = [];
        foreach ($reasonsData as $data) {
            $reason = new Reason();
            $reason->setType($data['type']);
            $reason->setLabel($data['label']);
            $manager->persist($reason);
            $reasons[$data['type']] = $reason;
        }

        // --- LEAVE TYPES ---
        foreach (array_merge([$admin], $users) as $user) {
            $cp = new LeaveType();
            $cp->setUser($user);
            $cp->setCode(LeaveTypeCode::CP);
            $cp->setMaxInitialNumber(25);
            $cp->setCurrentNumber(25);
            $cp->setStartDate(new \DateTimeImmutable('first day of January this year'));
            $cp->setEndDate(new \DateTimeImmutable('last day of December this year'));
            $manager->persist($cp);

            $rtt = new LeaveType();
            $rtt->setUser($user);
            $rtt->setCode(LeaveTypeCode::RTT);
            $rtt->setMaxInitialNumber(10);
            $rtt->setCurrentNumber(10);
            $rtt->setStartDate(new \DateTimeImmutable('first day of January this year'));
            $rtt->setEndDate(new \DateTimeImmutable('last day of December this year'));
            $manager->persist($rtt);

            // --- LEAVE REQUESTS ---
            for ($j = 0; $j < 2; ++$j) {
                $leaveRequest = new LeaveRequest();
                $leaveRequest->setRequestedBy($user);
                $leaveRequest->setReason($reasons['vacation']);
                $leaveRequest->setStatus(LeaveStatus::CONFIRMED);
                $leaveRequest->setLeaveType($cp);
                $leaveRequest->setCommentUser("Demande de test $j");
                
                $period = new Period();
                $startDate = new \DateTimeImmutable("+$j month");
                $period->setStartedAt($startDate->setTime(9, 0));
                $period->setEndedAt($startDate->modify('+5 days')->setTime(18, 0));
                $period->setLeaveRequest($leaveRequest);
                
                $leaveRequest->setPeriod($period);
                
                $manager->persist($leaveRequest);
                $manager->persist($period);
            }

            // --- EXPENSE REPORTS ---
            for ($k = 0; $k < 3; ++$k) {
                $expense = new ExpenseReport();
                $expense->setTitle("Note de frais $k");
                $expense->setRequestedBy($user);
                $expense->setAmount(rand(1000, 50000)); // en centimes probablement
                $expense->setStatus(ExpenseStatus::PENDING);
                $expense->setBilledAt(new \DateTimeImmutable("-$k days"));
                $expense->setComment("Justificatif pour achat $k");
                $manager->persist($expense);
            }
        }

        $manager->flush();
    }
}
