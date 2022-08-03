<?php
namespace TJM\WikiWeb\Tests;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use TJM\Wiki\File;
use TJM\WikiWeb\Kernel;
use TJM\WikiWeb\WikiWeb;

class KernelTest extends WebTestCase{
	const WIKI_DIR = __DIR__ . '/tmp';

	static public function setUpBeforeClass(): void{
		mkdir(self::WIKI_DIR);
	}
	protected function tearDown(): void{
		shell_exec("rm -rf " . self::WIKI_DIR . "/.git && rm -rf " . self::WIKI_DIR . "/*");
		parent::tearDown();
	}
	static public function tearDownAfterClass(): void{
		rmdir(self::WIKI_DIR);
	}
	protected static function createKernel(array $options = []): KernelInterface{
		return new Kernel([
			'config'=> __DIR__ . '/resources/config.yml',
			'debug'=> $options['debug'] ?? false,
			'environment'=> $options['environment'] ?? 'test',
		]);
	}

	public function testNotFoundViewFile(){
		$client = static::createClient();

		//--hide error, -@ <https://github.com/symfony/symfony/issues/28023#issuecomment-406850193>
		$client->catchExceptions(false);
		$this->expectException(NotFoundHttpException::class);

		static::getContainer()->get(WikiWeb::class)->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$client->request('GET', '/bar');
		$this->assertResponseStatusCodeSame(404);
	}
	public function testFoundViewFile(){
		$client = static::createClient();
		static::getContainer()->get(WikiWeb::class)->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$client->request('GET', '/foo');
		$this->assertResponseIsSuccessful();
		$response = $client->getResponse();
		$this->assertMatchesRegularExpression('/^<\!doctype html>/', $response->getContent());
		$this->assertMatchesRegularExpression('/hello world/', $response->getContent());
	}
	public function testRedirectHTMLExtension(){
		$client = static::createClient();
		static::getContainer()->get(WikiWeb::class)->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$client->followRedirects(false);
		$client->request('GET', '/foo.html');
		$this->assertResponseRedirects('/foo');
	}
}
