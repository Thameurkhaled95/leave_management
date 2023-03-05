<?php

namespace App\Service;

use App\Entity\Conge;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Workflow\Registry;

class CongeWorkflow
{
    private $workflows;
    private $security;

    public function __construct(Registry $workflows, Security $security)
    {
        $this->workflows = $workflows;
        $this->security = $security;
    }

    /**
     * test if can pass to transiction
     *
     * @param [Conge] $conge
     * @param [string] $transition_name
     * @return boolean
     */
    public function can($transition_name, Conge $conge = new Conge())
    {
        $stateMachine = $this->workflows->get($conge, 'leave_request');
        return $stateMachine->can($conge, $transition_name);
    }

    private function validate($conge)
    {
        $date_debut = $conge->getDateDebut();
        $date_fin = $conge->getDateFin();
        $now = new \DateTime("now");

        if ($date_debut < $now)
            return [false, "date de debut infériuer a date d'aujord'hui"];

        if ($date_debut > $date_fin)
            return [false, "date de debut superier a date de fin de congé"];

        $day_of_the_week = date('w', strtotime($date_debut->format('Y-m-d')));
        if ($day_of_the_week == 0 || $day_of_the_week == 6)
            return [false, "les weekends sont des jours de congé, veuiilez choist un autre date debut"];

        return [true, 'demande envoyé'];
    }

    public function on_create(Conge $conge)
    {
        [$is_valide, $message] = $this->validate($conge);

        if (!$is_valide)
            return [$is_valide, $message];

        $stateMachine = $this->workflows->get($conge, 'leave_request');
        // returns User object or null if not authenticated
        $user = $this->security->getUser();
        // affect to authenticated user
        $init = $stateMachine->getMarking($conge)->getPlaces();
        foreach ($init as $key => $val)
            $conge->setStatus($key);
        $conge->setEmploye($user);
        return [$is_valide, $message];
    }

    public function changeStats(Conge $conge, $accept)
    {
        ($accept) ? $this->to_accept($conge) : $this->to_reject($conge);
    }

    private function to_accept(Conge $conge)
    {
        // apply workfolw 'to_accept'
        $this->doTransition('to_accept', $conge);
    }

    private function to_reject(Conge $conge)
    {
        // apply workfolw 'to_reject'
        $this->doTransition('to_reject', $conge);
    }

    private function doTransition(string $transition, Conge $order): void
    {
        $stateMachine = $this->workflows->get($order, 'leave_request');

        $stateMachine->apply($order, $transition);
    }
}
