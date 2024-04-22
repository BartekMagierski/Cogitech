<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Doctrine\ORM\EntityManagerInterface;
use App\Form\AddAccountType;
use App\Entity\Accounts;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class AccountsController extends AbstractController
{

    public function index(): Response 
    {

        return $this->render('accounts/index.html.twig', [
            'controller_name' => 'AccountsController',
        ]);

    }

    #[Route('/addAccount', name: 'addAccount')]
    public function add(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher) 
    {

        $form = $this->createForm(AddAccountType::class, new Accounts());
        $message = null;

        if ($request->isMethod('POST')) 
        {
            $form->handleRequest($request);
            
            if ($form->isSubmitted()) 
            {

                $accounts = new Accounts();

                $queryParameters = &$request->request->all()['add_account'];
                $repository = $em->getRepository(Accounts::class);
                $account = $repository->findOneBy(['username'=> $queryParameters['username']]);
                
                // No such account in DB, you can add this one
                if($account === null) 
                {
                    $accounts->setUsername($queryParameters['username']);
                    $accounts->setPassword($passwordHasher->hashPassword(
                        $accounts, $queryParameters['password']
                    ));
                    $accounts->setRoles([]);
                    $em->persist($accounts);
                    $em->flush();

                    $message = 
                    [    
                        'state' => 'success',
                        'txt' => 'Account was created!'
                    ];
                    
                } else
                {
                    $message = 
                    [
                        'state' => 'failure',
                        'txt' => 'This Username is already in use!'
                    ];
                }
                
            }
        
        } 
        
        return $this->render('forms/addAccount.html.twig', [
            'form' => $form->createView(),
            'message' => $message
        ]);

    }

}
