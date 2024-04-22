<?php

namespace App\Form;

use App\Entity\Accounts;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AddAccountType extends AbstractType
{

    private $urlGenerator;
    
  
    public function setUrlGenerator(UrlGeneratorInterface $urlGenerator): void
    {
        $this->urlGenerator = $urlGenerator;
    }

    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            /*
             * Field "Role" Causes error => An exception has been thrown during the rendering of a template ("Warning: Array to string conversion").
             * Have to specify the type to remove error, 
             * Because it have to be option type?
             * Field "Role" is not needed for this task, so i remove it
             */
            //->add('role')
            ->setMethod('POST')
            ->add('username', TextType::class, array
                (
                    'error_bubbling' => true,
                    'attr' => array
                    (
                        'required' => true,
                        'class' => 'col-8',
                        'type' => 'text',
                        'value' => ''
                    )
                )
            )
            ->add('password', PasswordType::class, array
                (
                    'attr' => array
                    (
                        'required' => true,
                        'class' => 'col-8',
                        'hash_property_path' => 'password',
                        'mapped' => false,
                        'type' => PasswordType::class
                    )
                )
            )
            ->add('addAccount', SubmitType::class, array
                (
                    'attr' => array
                    (
                        'class' => 'btn btn-success text-white mt-4',
                        'type' => 'submit'
                    )
                )
            )
            ->setAction($this->getRoute('addAccount'))
            ->setMethod('POST')  
        ;
        
    }


    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Accounts::class,
        ]);
    }


    public function getRoute(string $routeName):string 
    {
        return $this->urlGenerator->generate($routeName);
    }

}
