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
                    'class' => 'd-block mx-auto my-3 form-control-lg',
                ],
                'label_attr' => ['class' => 'custom-file-label fs-1 fw-bold form-label']
            ])
            ->add('uniqId', TextType::class, [
                'required' => false,
                'label' => 'Перезаписать файл по коду',
                'attr' => [
                    'class' => 'd-block mx-auto my-4',
                ]
            ])
            ->add('upload', SubmitType::class, [
                'label' => 'Загрузить',
                'attr' => [
                    'class' => 'btn btn-success d-block my-2 mx-auto',
                ]
            ]);
//        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
//        });
    }
    
//    public function configureOptions(OptionsResolver $resolver): void
//    {
//        $resolver->setDefaults([]);
//    }
}
