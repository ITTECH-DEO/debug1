<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\OptionsResolver\OptionsResolver;
use AppBundle\Entity\Ticket;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Title cannot be blank']),
                ],
            ])
            ->add('content', TextareaType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Content cannot be blank']),
                ],
            ])
            ->add('priority', ChoiceType::class, [
                'choices' => [
                    'Low' => 'low',
                    'Medium' => 'medium',
                    'High' => 'high',
                ],
                'placeholder' => 'Select a priority',
                'required' => true,
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Bug' => 'Bug',
                    'Feature' => 'Feature',
                    'Task' => 'Task',
                ],
                'placeholder' => 'Select a type',
                'required' => true,
            ])
            ->add('department', ChoiceType::class, [
                'choices' => [
                    'HR' => 'HR',
                    'IT' => 'IT',
                    'Finance' => 'Finance',
                    'Marketing' => 'Marketing',
                    'Sales' => 'Sales',
                    'Customer Service' => 'Customer Service',
                    'Operations' => 'Operations',
                    'Legal' => 'Legal',
                ],
                'placeholder' => 'Select a department',
                'required' => true,
            ])
            ->add('image', FileType::class, [
                'label' => 'Upload Image (JPEG, PNG, GIF)',
                'required' => false,
                'mapped' => false, // Ensure this field is not mapped to the entity
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, GIF)',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
            'csrf_protection' => false,  // Enable CSRF protection
            'csrf_field_name' => '_token',  // Hidden field name for CSRF
            'csrf_token_id'   => 'ticket_item', // CSRF token ID
        ]);
    }
}

?>