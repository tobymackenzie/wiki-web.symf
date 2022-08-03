<?php
namespace TJM\WikiWeb\Tests;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TJM\Wiki\File;
use TJM\Wiki\Wiki;
use TJM\WikiWeb\WikiWeb;

class WikiWebTest extends TestCase{
	const WIKI_DIR = __DIR__ . '/tmp';

	static public function setUpBeforeClass(): void{
		mkdir(self::WIKI_DIR);
	}
	protected function tearDown(): void{
		shell_exec("rm -rf " . self::WIKI_DIR . "/.git && rm -rf " . self::WIKI_DIR . "/*");
	}
	static public function tearDownAfterClass(): void{
		rmdir(self::WIKI_DIR);
	}

	public function testNotFoundViewFileAction(){
		$wiki = new Wiki(self::WIKI_DIR);
		$wweb = new WikiWeb($wiki);
		$wiki->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$this->expectException(NotFoundHttpException::class);
		$wweb->viewFileAction('/bar');
	}
	public function testFoundViewFileAction(){
		$wiki = new Wiki(self::WIKI_DIR);
		$wweb = new WikiWeb($wiki);
		$wiki->writeFile(new File([
			'path'=> '/foo.md',
			'content'=> 'hello world',
		]));
		$response = $wweb->viewFileAction('/foo');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertMatchesRegularExpression('/^<\!doctype html>/', $response->getContent());
		$this->assertMatchesRegularExpression('/hello world/', $response->getContent());
	}
	public function testRedirectHTMLExtension(){
		$wiki = new Wiki(self::WIKI_DIR);
		$wweb = new WikiWeb($wiki);
		$wiki->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$response = $wweb->viewFileAction('/foo.html');
		$this->assertEquals(302, $response->getStatusCode());
	}
}
