<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AppExtension extends AbstractExtension 
{

    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }
    
    public function getFunctions()
    {
        return 
        [

            new TwigFunction('route', [$this, 'getRoute']),
            new TwigFunction('getFieldType', [$this, 'fieldType']),
            new TwigFunction('colorClass', [$this, 'alertMsgColorClass']),

        ];
    }

    public function getRoute(string $routeName):string 
    {
        return $this->urlGenerator->generate($routeName);
    }

    public function fieldType(array $atributes):string 
    {
        if(array_key_exists('type', $atributes)) 
        {

            return $atributes['type'];
        
        } else return "0";
    }

    public function alertMsgColorClass(string $state):string 
    {

        switch($state)
        {
            case 'success': return "alert-success";
            case 'failure': return "alert-danger";
            case 'info'   : return "alert-primary";
            default: return ""; 
        }

    }

}
