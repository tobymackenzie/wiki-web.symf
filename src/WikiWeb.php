<?php
namespace TJM\WikiWeb;
use BadMethodCallException;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Mime\MimeTypes;
use TJM\Wiki\Wiki;
use TJM\Wiki\File;

class WikiWeb{
	const DIR = __DIR__ . '/..';
	const CONFIG_DIR = self::DIR . '/config';
	const WEB_DIR = self::DIR . '/web';
	protected $mimeTypes;
	protected $wiki;

	public function __construct(Wiki $wiki, array $opts = []){
		$this->wiki = $wiki;
		if($opts && is_array($opts)){
			foreach($opts as $opt=> $value){
				$setter = 'set' . ucfirst($opt);
				if(method_exists($this, $setter)){
					$this->$setter($value);
				}else{
					$this->$opt = $value;
				}
			}
		}
	}

	/*--
	Method: __call
	Pass through to wiki
	*/
	public function __call(string $name, array $args = []){
		if(method_exists($this->wiki, $name)){
			return call_user_func_array([$this->wiki, $name], $args);
		}
		throw new BadMethodCallException();
	}

	/*=====
	==controller
	=====*/

	public function viewFileAction($path){
		if(substr($path, 0, 1) !== '/'){
			$path = '/' . $path;
		}
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		$pagePath = $extension ? substr($path, 0, -1 * (strlen($extension) + 1)) : $path;
		if($this->wiki->hasPage($pagePath)){
			$file = $this->wiki->getPage($pagePath);
		}else{
			if($this->wiki->hasFile($path)){
				$file = $this->wiki->getFile($path);
			}
		}
		if(isset($file)){
			if($extension === 'html'){
				return new RedirectResponse($pagePath, 302);
			}
			$response = new Response();
			if(strlen($extension) === 0){
				$response->setContent('<!doctype html><h1>' . $file->getPath() . '</h1>' . $file->getContent());
			}elseif($extension === $file->getExtension()){
				$response->setContent($file->getContent());
				$response->headers->set('Content-Type', $this->getMimeType($extension));
			}else{
				throw new NotFoundHttpException();
			}
			return $response;
		}
		throw new NotFoundHttpException();
	}
	public function handleException(ExceptionEvent $event){
		$exception = $event->getThrowable();
		if($exception instanceof HttpExceptionInterface){
			$status = $exception->getStatusCode();
			$response = new Response();
			$response->setStatusCode($status);
			$response->setContent(isset(Response::$statusTexts[$status]) ? Response::$statusTexts[$status] : 'Error');
			$response->headers->replace($exception->getHeaders());
			$event->setResponse($response);
		}
	}

	/*=====
	==util
	=====*/

	protected function getMimeType($extension){
		if(empty($this->mimeTypes)){
			$this->mimeTypes = new MimeTypes();
		}
		$types = $this->mimeTypes->getMimeTypes($extension);
		return $types ? $types[0] : 'text/plain';
	}
}
