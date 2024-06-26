<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;






class MainController extends AbstractController
{
	
	#[Route('/', name: 'home.')]
	public function index(): Response
	{


		
		return $this->render('main/index.html.twig');

	}

	public function displayError(string $msg):Response {

		return $this->render('posts/error.html.twig', ['message'=>$msg]);

	}


}
