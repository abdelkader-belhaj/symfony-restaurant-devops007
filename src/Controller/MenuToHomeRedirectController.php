<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

if (!class_exists(__NAMESPACE__ . '\\MenuToHomeRedirectController', false)) {
    class MenuToHomeRedirectController extends AbstractController
    {
        public function __invoke(Request $request): Response
        {
            return $this->redirectToRoute('app_menus_home', [], 301);
        }
    }
}
