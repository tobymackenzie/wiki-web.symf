<?php
namespace TJM\WikiWeb;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TJM\Wiki\File;
use TJM\Wiki\Wiki;
use TJM\WikiWeb\Form\FileType;

class AdminController extends AbstractController{
	protected $wiki;
	public function __construct(WikiWeb $wiki){
		$this->wiki = $wiki;
	}
	public function editFileAction(Request $request, $path = null){
		if($path === null){
			$file = new File();
		}else{
			if(strpos($path, '.') === false){
				$file = $this->wiki->getPage($path);
			}else{
				$file = $this->wiki->getFile($path);
			}
		}
		$form = $this->createForm(FileType::class, $file, [
			'file'=> $file,
		]);
		$form->handleRequest($request);
		if($form->isSubmitted() && $form->isValid()){
			$submittedFile = $form->getData();
			$this->wiki->writeFile($submittedFile);
			return $this->redirectToRoute('tjm_wiki', [
				'path'=> $path ?? $submittedFile->getPath(),
			]);
		}
		return $this->renderForm('admin/editFile.html.twig', [
			'form'=> $form,
			'name'=> $file->getPath() ? "Edit File {$file->getPath()}" : 'Add file',
		]);
	}
}
