<?php

namespace App\Form;

use App\Entity\HotelPhoto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;


class HotelPhotoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder

            ->add('hotel_img', FileType::class, [
                'label' => 'Оруулах боломжтой файлын төрөл (JPG,JPEG,PNG) ',
//                'multiple' => true,
                'attr'     => [
                    'accept' => 'image/*',
//                    'multiple' => 'multiple',
                    'style' => 'display:block; margin-bottom: 20px;',
                ]])
            ->add('is_special', \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class, [
                'label' => 'Онцлох зураг',
                'required' => false,
                'attr'     => [
                    'style' => 'display:block;',
                ]])
            ->add('Save', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, 
                    ['attr' => [
                        'class' => 'btn btn-success',
                        'style' => 'margin-top: 20px;',
                        ],
                     ])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => HotelPhoto::class,
        ]);
    }
}
