<?php
namespace TJM\WikiWeb\Tests;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TJM\Wiki\File;
use TJM\Wiki\Wiki;
use TJM\WikiWeb\FormatConverter\MarkdownConverter;
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
	protected function getWikiWeb(){
		return new WikiWeb(
			new Wiki([
				'path'=> self::WIKI_DIR,
			])
			,[
				'converters'=> [
					new MarkdownConverter(),
				],
			]
		);
	}

	public function testNotFoundViewFileAction(){
		$wweb = $this->getWikiWeb();
		$wweb->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$this->expectException(NotFoundHttpException::class);
		$wweb->viewFileAction('/bar');
	}
	public function testFoundViewFileAction(){
		$wweb = $this->getWikiWeb();
		$wweb->writeFile(new File([
			'path'=> '/foo.md',
			'content'=> 'hello world',
		]));
		$response = $wweb->viewFileAction('/foo');
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertMatchesRegularExpression('/^<\!doctype html>/', $response->getContent());
		$this->assertMatchesRegularExpression('/hello world/', $response->getContent());
	}
	public function testNoConverterFoundViewFileAction(){
		$wweb = $this->getWikiWeb();
		$wweb->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$this->expectException(NotFoundHttpException::class);
		$wweb->viewFileAction('/foo.asdf');
	}
	public function testRedirectHTMLExtension(){
		$wweb = $this->getWikiWeb();
		$wweb->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$response = $wweb->viewFileAction('/foo.html');
		$this->assertEquals(302, $response->getStatusCode());
	}
}
