<?php

namespace App\Form;

use App\Entity\Reference;
use App\Entity\Upload;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ImportCsvType extends AbstractType
{
    private array $options;

    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->options = $resolver->resolve();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'Импорт CSV файла',
                'mapped' => false,
                'constraints' => [
                    new File([
//                        'maxSize' => '1k',
//                        'mimeTypes' => [
//                            'application/pdf',
//                            'application/x-pdf',
//                        ],
                        'mimeTypesMessage' => 'Неверный тип файла',
                        'maxSizeMessage' => 'Превышен допустимый размер файла',
                        'disallowEmptyMessage' => 'Файл без имени не разрешён'
                    ])
                ],
                'attr' => [
                    'class' => 'd-block mx-auto my-2',
                ],
                'label_attr' => [ 'class' => 'custom-file-label']
            ])
            ->add('uniqId', TextType::class, [
                'required'   => false,
                'label' => 'Перезаписать файл по коду'
            ])
            ->add('upload', SubmitType::class, [
                'label' => 'Загрузить',
                'attr' => [
                    'class' => 'btn btn-success d-block my-2 mx-auto',
                ]
            ])
        ;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options){
            $form = $event->getForm();
            if (!$options['reference']){
                $form->remove('uniqId');
                $this->options = ['data_class'=>$options['data_class']];
            }
        });

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'reference' => true
        ]);

    }
}
