<?php

// src/AppBundle/Form/TicketFilterType.php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class TicketFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
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
                'required' => false,
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Bug' => 'Bug',
                    'Feature' => 'Feature',
                    'Task' => 'Task',
                ],
                'required' => false,
            ])
            ->add('priority', ChoiceType::class, [
                'choices' => [
                    'Low' => 'low',
                    'Medium' => 'medium',
                    'High' => 'high',
                ],
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Created' => 'Created',
                    'In Progress' => 'In Progress',
                    'Validated' => 'Validated',
                    'Rejected' => 'Rejected',
                ],
                'required' => false,
            ])
            ->add('search', SubmitType::class, ['label' => 'Search']);
    }
}

