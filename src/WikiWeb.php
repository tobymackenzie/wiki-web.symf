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
use TJM\WikiWeb\FormatConverter\ConverterInterface;

class WikiWeb{
	const DIR = __DIR__ . '/..';
	const CONFIG_DIR = self::DIR . '/config';
	const WEB_DIR = self::DIR . '/web';
	protected $converters = [];
	protected $mimeTypes;
	protected $shell = 'shell.html.twig';
	protected $twig;
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
		if(substr($pagePath, -1) === '/'){
			$pagePath = substr($pagePath, 0, -1);
		}
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
			}elseif(substr($path, -1) === '/'){
				return new RedirectResponse($pagePath, 302);
			}
			$response = new Response();
			try{
				if(strlen($extension) === 0){
					$content = $this->convertFile($file, 'html');
					if($this->twig){
						$content = $this->twig->render($this->shell, [
							'name'=> $file->getPath(),
							'content'=> $content,
						]);
					}else{
						$content = '<!doctype html>' . $content;
					}
					$response->setContent($content);
				}elseif($extension === $file->getExtension()){
					$response->setContent($file->getContent());
					$response->headers->set('Content-Type', $this->getMimeType($extension));
				}else{
					$response->setContent($this->convertFile($file, $extension));
					$response->headers->set('Content-Type', $this->getMimeType($extension));
				}
			}catch(Exception $e){
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
	==converters
	=====*/
	public function addConverter(ConverterInterface $converter){
		$this->converters[] = $converter;
	}
	protected function convertFile(File $file, $to){
		foreach($this->converters as $converter){
			if($converter->supports($file->getExtension(), $to)){
				return $converter->convert($file->getContent(), $file->getExtension(), $to);
			}
		}
		throw new Exception("No converter found to convert from {$file->getExtension()} to {$to}");
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
