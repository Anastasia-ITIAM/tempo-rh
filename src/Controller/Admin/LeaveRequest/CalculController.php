<?php

namespace App\Controller\Admin\LeaveRequest;

use App\Form\Calcul\CalculType;
use App\Repository\LeaveRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CalculController extends AbstractController
{
    #[Route('/calcul', name: 'calcul')]
    public function calcul(Request $request, LeaveRequestRepository $leaveRequestRepository): Response
    {
        $form = $this->createForm(CalculType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $startDate = $form->get('startDate')->getData();
            $endDate = $form->get('endDate')->getData();

            $leaveRequests = $leaveRequestRepository->findRequestsBetween($startDate, $endDate);

            if (empty($leaveRequests)) {
                $this->addFlash('warning', 'Les dates choisies ne correspondent à aucune demande.');
            }
        }

        return $this->render('admin/calcul/calcul.html.twig', [
            'form' => $form->createView(),
            'leaveRequests' => $leaveRequests ?? null,
        ]);
    }
}
