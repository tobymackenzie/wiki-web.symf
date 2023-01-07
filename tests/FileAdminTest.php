<?php
namespace TJM\WikiWeb\Tests;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use TJM\Wiki\File;
use TJM\WikiWeb\Kernel;
use TJM\WikiWeb\WikiWeb;

class FileAdminTest extends WebTestCase{
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

	public function testCreateNewFile(){
		$client = static::createClient();

		//--create
		$client->request('GET', '/_edit');
		$this->assertResponseIsSuccessful();
		$client->submitForm('Submit', [
			'file[path]'=> 'foo/bar.md',
			'file[content]'=> 'hello world',
		]);
		$client->followRedirect();
		$response = $client->getResponse();
		$this->assertMatchesRegularExpression('/hello world/', $response->getContent());
		$this->assertTrue(file_exists(self::WIKI_DIR . '/foo/bar.md'));

		//--edit
		$client->request('GET', '/foo/bar/_edit');
		$this->assertResponseIsSuccessful();
		$client->submitForm('Submit', [
			'file[content]'=> '123 test',
		]);
		$client->followRedirect();
		$response = $client->getResponse();
		$this->assertMatchesRegularExpression('/^<\!doctype html>/i', $response->getContent());
		$this->assertMatchesRegularExpression('/123 test/', $response->getContent());
	}
	public function testCreateNamedFile(){
		$client = static::createClient();

		//--create
		$client->request('GET', '/foo/_edit');
		$this->assertResponseIsSuccessful();
		$client->submitForm('Submit', [
			'file[content]'=> 'hello world',
		]);
		$client->followRedirect();
		$response = $client->getResponse();
		$this->assertMatchesRegularExpression('/^<\!doctype html>/i', $response->getContent());
		$this->assertMatchesRegularExpression('/hello world/', $response->getContent());
		$this->assertTrue(file_exists(self::WIKI_DIR . '/foo.md'));

		//--edit
		$client->request('GET', '/foo/_edit');
		$this->assertResponseIsSuccessful();
		$client->submitForm('Submit', [
			'file[content]'=> '123 test',
		]);
		$client->followRedirect();
		$response = $client->getResponse();
		$this->assertMatchesRegularExpression('/^<\!doctype html>/i', $response->getContent());
		$this->assertMatchesRegularExpression('/123 test/', $response->getContent());
	}
	public function testRemoveFile(){
		$client = static::createClient();
		static::getContainer()->get(WikiWeb::class)->writeFile(new File([
			'content'=> 'hello world',
			'path'=> '/foo.md',
		]));
		$client->request('GET', '/foo/_edit');
		$this->assertResponseIsSuccessful();
		$client->clickLink('Remove');
		$this->assertResponseRedirects();
		$client->request('GET', '/foo');
		$this->assertResponseStatusCodeSame(404);
	}
}
