<?php
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use TJM\WikiWebBundle\TJMWikiWebBundle;
return [
	FrameworkBundle::class=> ['all'=> true],
	TwigBundle::class=> ['all'=> true],
	TJMWikiWebBundle::class=> ['all'=> true],
];
