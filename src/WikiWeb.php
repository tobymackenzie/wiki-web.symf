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
use Symfony\Component\Routing\RouterInterface;
use TJM\Wiki\Wiki;
use TJM\Wiki\File;
use TJM\WikiWeb\FormatConverter\ConverterInterface;

class WikiWeb{
	const DIR = __DIR__ . '/..';
	const CONFIG_DIR = self::DIR . '/config';
	const WEB_DIR = self::DIR . '/web';
	protected $admin;
	protected $converters = [];
	protected $homePage = '/_index';
	protected $mimeTypes;
	protected $name = 'TJM Wiki';
	protected $router;
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

	public function getName(){
		return $this->name;
	}

	/*=====
	==admin
	=====*/
	protected function isLoggedIn(){
		return $this->admin;
	}

	/*=====
	==controller
	=====*/

	public function viewFileAction($path){
		if(substr($path, 0, 1) !== '/'){
			$path = '/' . $path;
		}
		if($path === $this->homePage){
			return new RedirectResponse($this->getRoute('tjm_wiki', ['path'=> '/']), 302);
		}
		if($path === '/'){
			$path = $this->homePage;
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
				return new RedirectResponse($this->getRoute('tjm_wiki', ['path'=> $pagePath]), 302);
			}elseif(substr($path, -1) === '/'){
				return new RedirectResponse($this->getRoute('tjm_wiki', ['path'=> $pagePath]), 302);
			}
			$response = new Response();
			try{
				$format = $extension ?: 'html';
				if($format === 'html' || $format === 'xhtml'){
					$content = $this->convertFile($file, $format);
					if($this->twig){
						if($path === $this->homePage){
							$name = $this->name;
						}else{
							//--use path as name
							$name = $file->getPath();
							//---without extension
							$extension = pathinfo($file->getPath(), PATHINFO_EXTENSION);
							if($extension){
								$name = substr($name, 0, -1 * (strlen($extension) + 1));
							}
							//---switch '/' to '-', reverse
							$name = implode(' - ', array_reverse(explode('/', $name)));
							//---title case
							$name = ucwords($name);
						}
						$data = [
							'format'=> $format,
							'name'=> $name,
							'content'=> $content,
							'isLoggedIn'=> $this->isLoggedIn(),
							'path'=> substr($path, 1),
						];
						$content = $this->twig->render('@TJMWikiWeb/view.html.twig', $data);
					}else{
						$content = '<!doctype html>' . $content;
					}
					$response->headers->set('Content-Type', $this->getMimeType($format));
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
		if($this->isLoggedIn() && $this->router){
			return new RedirectResponse($this->router->generate('tjm_wiki_edit_file', ['path'=> substr($path, 1)]));
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
	==routing
	=====*/
	//-! maybe we should just move actions to a regular controller so we don't need this
	protected function getRoute($name, $opts, $abs = false){
		if($this->router){
			return $this->router->generate($name, $opts, $abs);
		}elseif($name === 'tjm_wiki' && isset($opts['path'])){
			return $opts['path'];
		}
	}
	public function setRouter(RouterInterface $router){
		$this->router = $router;
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
