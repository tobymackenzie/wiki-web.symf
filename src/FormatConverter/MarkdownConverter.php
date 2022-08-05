<?php
namespace TJM\WikiWeb\FormatConverter;
use League\CommonMark\CommonMarkConverter;

class MarkdownConverter implements ConverterInterface{
	protected $converter;
	public function __construct(CommonMarkConverter $converter = null){
		$this->converter = $converter ?? new CommonMarkConverter();
	}
	public function supports(string $from, string $to){
		return $from === 'md' && in_array($to, ['html', 'xhtml']);
	}
	public function convert(string $content, string $from = null, string $to = null){
		return $this->converter->convert($content);
	}
}
