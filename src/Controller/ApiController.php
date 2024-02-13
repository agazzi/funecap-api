<?php

namespace App\Controller;

use App\Service\RedisService;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class ApiController extends AbstractController
{
    #[Route('/generate/{token}', name: 'generate')]
    public function generate(string $token, ParameterBagInterface $bag): Response
    {
        $writer = new PngWriter();
        $route = $this->generateUrl('app_token', [
            'token' => $token
        ]);

        $qrCode = QrCode::create(sprintf('%s/%s', $bag->get('app')['endpoint'], $route))
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::Low)
            ->setSize(500)
            ->setMargin(10)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->setForegroundColor(new Color(0, 0, 0))
            ->setBackgroundColor(new Color(255, 255, 255));

        $result = $writer->write($qrCode);

        return new Response($result->getString(), Response::HTTP_OK, [
            'Content-Type' => $result->getMimeType(),
        ]);
    }

    #[Route('/debug/{token}', name: 'debug', methods: [Request::METHOD_POST, Request::METHOD_GET])]
    public function debug(string $token, Request $request, RedisService $redis): Response
    {
        $data = $redis->get($token);
        $data = str_replace('"', '', $data);
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data));

        $response = new Response($imageData);

        // Définissez l'en-tête Content-Type sur image/png
        $response->headers->set('Content-Type', 'image/png');

        return $response;
    }

    #[Route('/sign/{token}', name: 'sign', methods: [Request::METHOD_POST])]
    public function sign(string $token, Request $request, RedisService $redis): Response
    {
        $redis->set($token, $request->getContent());

        return $this->json([
            'result' => true
        ]);
    }

    #[Route('/signature/{token}', name: 'signature', methods: [Request::METHOD_GET])]
    public function signature(string $token, RedisService $redis): Response
    {
        if (!$redis->has($token)) {
            throw $this->createNotFoundException();
        }

        $data = $redis->get($token);
        $data = str_replace('"', '', $data);
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data));

        $response = new Response($imageData);

        $response->headers->set('Content-Type', 'image/png');

        return $response;
    }
}
