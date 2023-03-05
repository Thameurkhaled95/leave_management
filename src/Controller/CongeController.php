<?php

namespace App\Controller;

use App\Entity\Conge;
use App\Form\CongeType;
use App\Repository\CongeRepository;
use App\Service\CongeWorkflow;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/conges')]
class CongeController extends AbstractController
{
    private $workflows;
    private $congeRepository;
    private $security;

    public function __construct(CongeWorkflow $workflows, CongeRepository $congeRepository, Security $security)
    {
        $this->congeRepository = $congeRepository;
        $this->security = $security;
        $this->workflows = $workflows;
    }

    #[Route('/', name: 'app_conge_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $email = $request->query->get('email');
        if((!$this->security->isGranted('ROLE_ADMIN')))
        {
            $user = $this->getUser();
            $email = $user->getEmail();            
        }
        $conges = $this->congeRepository->getByEmail($email);
        return $this->render('conge/index.html.twig', [
            'conges' => $conges,
        ]);
    }

    #[Route('/new', name: 'app_conge_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $conge = new Conge();
        $form = $this->createForm(CongeType::class, $conge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            [$is_valide, $message] = $this->workflows->on_create($conge);
            if(!$is_valide)
            {
                $this->addFlash('error', $message);
                return $this->redirectToRoute('app_conge_index', [], Response::HTTP_SEE_OTHER);
            }
            $this->congeRepository->save($conge, true);
            $this->addFlash('info', "Votre demande à éte envoyé");
            return $this->redirectToRoute('app_conge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('conge/new.html.twig', [
            'conge' => $conge,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_conge_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Conge $conge): Response
    {
        // test if congé is mine or admin only
        $this->denyAccessUnlessGranted("view", $conge);
        return $this->render('conge/show.html.twig', [
            'conge' => $conge,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_conge_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Conge $conge): Response
    {
        // test if conge is mine
        $this->denyAccessUnlessGranted("edit", $conge);

        $form = $this->createForm(CongeType::class, $conge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->congeRepository->save($conge, true);

            return $this->redirectToRoute('app_conge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('conge/edit.html.twig', [
            'conge' => $conge,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/{accept}', name: 'app_conge_accept_reject', requirements: ['accept' => '\d+'], methods: ['POST'])]
    public function accept_reject(Request $request, Conge $conge, $accept): Response
    {
        $this->denyAccessUnlessGranted("stats", $conge);
        $message = ($accept) ? ["type" => "info", "text" => "Demande accepté"] : ["type" => "error", "text" => "Demande rejeté"];
        // change status of congé
        if ($this->isCsrfTokenValid('stats' . $conge->getId(), $request->request->get('_token'))) {

            $this->workflows->changeStats($conge, $accept);
            $this->congeRepository->save($conge, true);

            // $this->workflows->to_accept($conge);
            $this->addFlash($message['type'], $message['text']);
        }
        return $this->redirectToRoute('app_conge_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_conge_delete', methods: ['POST'])]
    public function delete(Request $request, Conge $conge): Response
    {
        $this->denyAccessUnlessGranted("edit", $conge);
        if ($this->isCsrfTokenValid('delete' . $conge->getId(), $request->request->get('_token'))) {
            $this->congeRepository->remove($conge, true);
            $this->addFlash('info', "Votre demande à éte supprimé");
        }
        return $this->redirectToRoute('app_conge_index', [], Response::HTTP_SEE_OTHER);
    }
}
