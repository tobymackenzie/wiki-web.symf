<?php
namespace TJM\WikiWeb\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use TJM\Wiki\File;

class FileType extends AbstractType{
	public function buildForm(FormBuilderInterface $builder, array $opts): void{
		if(empty($opts['file']) || $opts['file']->getPath() === null){
			$builder->add('path', TextType::class);
		}
		$builder->add('content', TextareaType::class);
	}
	public function configureOptions(OptionsResolver $resolver){
		$resolver->setDefaults([
			'file'=> null,
		]);
	}
}
