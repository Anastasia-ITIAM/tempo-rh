<?php

declare(strict_types=1);

namespace App\Tests\Integration\Entity;

use App\DataFixtures\AppFixtures;
use App\Entity\LeaveRequest\LeaveRequest;
use App\Entity\LeaveRequest\Period;
use App\Entity\LeaveRequest\Reason;
use App\Entity\User;
use App\Enum\LeaveRequest\Status;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[CoversClass(LeaveRequest::class)]
class LeaveRequestTest extends KernelTestCase
{
    protected ?AbstractDatabaseTool $databaseTool = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseTool = self::getContainer()
            ->get(DatabaseToolCollection::class)
            ->get();

        $this->databaseTool->loadFixtures([
            AppFixtures::class,
        ]);
    }

    public function testInsertLeaveRequest(): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'user1@temporh.example']);
        $reason = $entityManager->getRepository(Reason::class)->findOneBy(['type' => 'vacation']);

        $leaveRequest = new LeaveRequest();
        $leaveRequest->setRequestedBy($user);
        $leaveRequest->setReason($reason);
        $leaveRequest->setStatus(Status::PENDING);
        $leaveRequest->setCommentUser('Commentaire test');

        $period = new Period();
        $period->setStartedAt(new \DateTimeImmutable('2026-06-01 08:00'));
        $period->setEndedAt(new \DateTimeImmutable('2026-06-05 18:00'));
        $period->setLeaveRequest($leaveRequest);
        $entityManager->persist($period);

        $entityManager->persist($leaveRequest);
        $entityManager->flush();

        $this->assertNotNull($leaveRequest->getId());

        $repository = $entityManager->getRepository(LeaveRequest::class);
        $insertedRequest = $repository->find($leaveRequest->getId());

        $this->assertInstanceOf(LeaveRequest::class, $insertedRequest);
        $this->assertSame('user1@temporh.example', $insertedRequest->getRequestedBy()->getEmail());
        $this->assertSame('vacation', $insertedRequest->getReason()->getType());
        $this->assertSame('Commentaire test', $insertedRequest->getCommentUser());
        $this->assertSame(Status::PENDING, $insertedRequest->getStatus());
        $this->assertNotNull($insertedRequest->getPeriod());
        $this->assertSame('01/06/2026 - 05/06/2026', (string) $insertedRequest->getPeriod());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->databaseTool);
    }
}
