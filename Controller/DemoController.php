<?php

/*
 * This file is part of the DemoBundle for Kimai 2.
 * All rights reserved by Kevin Papst (www.kevinpapst.de).
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\DemoBundle\Controller;

use App\Controller\AbstractController;
use KimaiPlugin\DemoBundle\Configuration\DemoConfiguration;
use KimaiPlugin\DemoBundle\Entity\DemoEntity;
use KimaiPlugin\DemoBundle\Form\DemoType;
use KimaiPlugin\DemoBundle\Repository\DemoRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use App\Project\ProjectStatisticService;
use App\Reporting\ProjectView\ProjectViewForm;
use App\Reporting\ProjectView\ProjectViewQuery;

/**
 * @Route(path="/admin/budget")
 * @Security("is_granted('demo')")
 */
final class DemoController extends AbstractController
{
    /**
     * @var DemoRepository
     */
    private $repository;
    /**
     * @var DemoConfiguration
     */
    private $configuration;

    public function __construct(DemoRepository $repository, DemoConfiguration $configuration)
    {
        $this->repository = $repository;
        $this->configuration = $configuration;
    }

    /**
     * @Route(path="", name="demo", methods={"GET", "POST"})

     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, ProjectStatisticService $service)
    {
        $dateFactory = $this->getDateTimeFactory();
        $user = $this->getUser();

        $query = new ProjectViewQuery($dateFactory->createDateTime(), $user);
        $form = $this->createForm(ProjectViewForm::class, $query);
        $form->submit($request->query->all(), false);

        $projects = $service->findProjectsForView($query);
        $entries = $service->getProjectView($user, $projects, $query->getToday());

        $byCustomer = [];
        foreach ($entries as $entry) {
            $customer = $entry->getProject()->getCustomer();
            if (!isset($byCustomer[$customer->getId()])) {
                $byCustomer[$customer->getId()] = ['customer' => $customer, 'projects' => []];
            }
            $byCustomer[$customer->getId()]['projects'][] = $entry;
        }

        return $this->render('@Demo/budget_view.html.twig', [
            'entries' => $byCustomer,
            'form' => $form->createView(),
            'title' => 'report_project_view',
            'tableName' => 'project_view_reporting',
            'now' => $this->getDateTimeFactory()->createDateTime(),
        ]);
    }
}