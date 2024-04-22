<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Posts;
use App\Entity\User;

class PostsController extends AbstractController
{

    public function __construct (
        private EntityManagerInterface $em
    ){ }

    #[Route('/lista', name: 'displayPosts')]
    public function index(Request $request, Security $security): Response
    {
        // Redirect to login or display list
        if(!$security->getUser()) 
        {
            $response = $this->forward('App\Controller\SecurityController::login');
            return $response;

        } else 
        {
            return $this->display($request);
        }
    }

    #[Route('/posts', name: 'dumpPosts')]
    public function dumpPosts(Request $request, Security $security): Response
    {
        $repository = $this->em->getRepository(Posts::class);

        $postsList = array();
        foreach($repository->findAll() as $postInstance)
        {
            $post = array (
                'id'     => $postInstance->getId(),
                'UserId' => $postInstance->getUserId(),
                'title'  => $postInstance->getTitle(),
                'body'   => $postInstance->getBody()
            );
            array_push($postsList, $post);
        }

        $response = new JsonResponse($postsList);
        
        return $response;

    }


    public function display(Request $request): Response
    {
        $repository = (object) array
        (
            'posts' =>  $this->em->getRepository(Posts::class)
        );

        // Display list or respond to xhr
        if($request->isXmlHttpRequest()) 
        {
            $respond = $this->handleRequest($request, $repository);
            return new Response($respond);

        } else 
        {
            $postsInstances = $this->makePostsInstances($repository);
            return $this->render('posts/site.html.twig', [
                'rows' => $postsInstances,
                'paginationNumber' => ceil(count($postsInstances) /10)
            ]);
        }

    }


    private function handleRequest(Request $request, object $repository):string 
    {
        $response = function(string $state, string $message):string {
            return $this->renderView('posts/alert.html.twig', [
                'state' => $state,
                'message' => $message
            ]);
        };
        
        if(array_intersect(['call','pID'], $request->query->keys()))
        {
            if($request->query->get('call') === 'removePost') 
            {
                $pID = $request->query->get("pID");
                $postToRemove = $repository->posts->findOneBy(['id' => $pID]);
                if($postToRemove !== null)
                {
                    $this->em->remove($postToRemove);
                    $this->em->flush();
                    return $response('success', "Row with ID $pID was successfully removed");

                } else return $response('info', "No such ID as $pID in database");
            } else { return $response('failure', 'Unsupported action'); }
        } else { return $response('failure', 'Bad request'); }
    }


    private function makePostsInstances(object $repository): array
    {
        $postsInstances = array();

        foreach($repository->posts->findAll() as $post)
        {
            $instance = 
            [
                'id' => $post->getId(),
                'creator' => $post->getUser()->getName(),
                'title' => $post->getTitle()
            ];
            array_push($postsInstances, $instance);
        }

        return $postsInstances;
    }

}
