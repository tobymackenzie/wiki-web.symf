<?php
namespace TJM\WikiWeb\FormatConverter;
use ParsedownExtra;

class MarkdownConverter implements ConverterInterface{
	protected $parsedown;
	public function __construct(ParsedownExtra $parsedown = null){
		$this->parsedown = $parsedown ?? new ParsedownExtra();
	}
	public function supports(string $from, string $to){
		return $from === 'md' && in_array($to, ['html', 'xhtml']);
	}
	public function convert(string $content, string $from = null, string $to = null){
		return $this->parsedown->text($content);
	}
}
