<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="default")
     */
    public function index(): Response
    {
        $revision = file_exists(__DIR__.'/../../REVISION') ? trim(file_get_contents(__DIR__.'/../../REVISION')) : null;

        return $this->render(
            'index.html.twig',
            [
                'revision' => $revision,
            ]
        );
    }
}
