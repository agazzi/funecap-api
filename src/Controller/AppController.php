<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'app_')]
class AppController extends AbstractController
{
    #[Route('/token/{token}', name: 'token')]
    public function token(Request $request, string $token): JsonResponse
    {
        dd('foo');


        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ApiController.php',
        ]);
    }
}
