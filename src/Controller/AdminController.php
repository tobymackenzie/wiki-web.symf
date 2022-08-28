<?php
namespace TJM\WikiWeb\Controller;
use DateTime;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use TJM\Wiki\File;
use TJM\Wiki\Wiki;
use TJM\WikiWeb\Form\FileType;
use TJM\WikiWeb\WikiWeb;

class AdminController extends AbstractController{
	protected $wiki;
	public function __construct(WikiWeb $wiki){
		$this->wiki = $wiki;
	}
	public function adminAction(){
		return $this->render('@TJMWikiWeb/admin/admin.html.twig', [
			'name'=> 'Admin',
		]);
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
			$fullPath = $this->wiki->getFilePath($file);
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
		return $this->renderForm('@TJMWikiWeb/admin/editFile.html.twig', [
			'file'=> $file,
			'form'=> $form,
			'modifiedDate'=> isset($fullPath) && is_readable($fullPath) ? (new DateTime('@' . filemtime($fullPath)))->setTimezone(new DateTimeZone(date_default_timezone_get())) : null,
			'name'=> $file->getPath() ? "Edit File {$file->getPath()}" : 'Add file',
			'path'=> $path,
		]);
	}
	public function removeFileAction(Request $request, $path = null){
		if($path === null){
			throw $this->createNotFoundExcetion();
		}else{
			if(strpos($path, '.') === false){
				$file = $this->wiki->getPage($path);
			}else{
				$file = $this->wiki->getFile($path);
			}
		}
		if($file){
			$this->wiki->removeFile($file);
		}
		return $this->redirectToRoute('tjm_wiki_admin');
	}
}
