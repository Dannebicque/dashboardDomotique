<?php

namespace App\Controller;

use App\Application\Dashboard\DashboardProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DashboardController
{
    public function __construct(private DashboardProvider $dashboard) {}

    #[Route('/api/dashboard', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse($this->dashboard->data());
    }
}
