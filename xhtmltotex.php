<?php 

class Xhtmltotex{

	public $mapping = array("br.b"=>"\\\\",
						"br.a"=>"",
						"em.b"=>"\\textit{",
						"em.a"=>"}",
						"strong.b"=>"\\textbf{",
						"strong.a"=>"}"
					);

	public $attrMapping = array(
						"text-center.b" => "\\begin{center}\n",
						"text-center.a" => "\\end{center}\n",
						"title.b" => "\\bookTitle{",
						"title.a" => "}\n",
						"titleauthor.b" => "\\titleauthor{",
						"titleauthor.a" => "}",
						"text-right.b" => "\\begin{flushright}\n",
						"text-right.a" => "\\end{flushright}\n",
						"text-left.b" => "\\begin{flushleft}\n",
						"text-left.a" => "\\end{flushleft}\n",
						"level1-title.b" => "\\chapter{",
						"level1-title.a" => "}",
						"level2-title.b" => "\\section{",
						"level2-title.a" => "}",
						"level3-title.b" => "\\subsection{",
						"level3-title.a" => "}",
						"level4-title.b" => "\\subsubsection{",
						"level4-title.a" => "}",						
						"level5-title.b" => "\\paragraph{",
						"level5-title.a" => "}",
					);

	public function __construct() {

	}

	public function getXhtmlFiles() {

		$allFiles = [];
		
		$folderPath = '/home/sriranga/projects/Nagpur_Ashram_ebook/unicode-src/H002/';
		
	    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folderPath));

	    foreach($iterator as $file => $object) {
	    	
	    	if(preg_match('/.*\/\d+.*\.xhtml$/',$file)) array_push($allFiles, $file);
	    }

	    sort($allFiles);

		return $allFiles;
	}	

	public function processFiles($xhtmlFiles){

		foreach ($xhtmlFiles as $xhtmlFile) {		

			// $texFile = basename($xhtmlFile);
			// $texFile = str_replace('.xhtml', '.tex', $texFile);
			// echo $texFile . "\n";

			$this->xhtmlToTeX($xhtmlFile);

		}
	}

	public function xhtmlToTeX($xhtmlFile){

		// echo $xhtmlFile . "\n";
		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHTMLFile($xhtmlFile);
		// libxml_clear_errors();

		$bodyTag = $dom->getElementsByTagName('body');
		// var_dump($bodyTag);

		if (!is_null($bodyTag)) {
		  foreach ($bodyTag as $element) {
		    // echo "<br/>". $element->nodeName. ": ";

		    $nodes = $element->childNodes;
		    foreach ($nodes as $node) {

		      if($node->nodeName == 'section')
		      	$this->parseSectionElement($node);
		    }
		  }
		}
	}

	public function parseSectionElement($section){

 	  	$nodes = $section->childNodes;

		if (!is_null($nodes)) {
		  foreach ($nodes as $node) {

		  	if(preg_match('/h1|h2|h3|h4|h5|h6/',$node->nodeName))
		  		$this->parseHeadingElement($node);
		  	elseif($node->nodeName == 'p')
		  		$this->parseParaElement($node);
		  	elseif($node->nodeName == 'img')
		  		$this->parseImgElement($node);
		  }
		}		
	}

	public function parseHeadingElement($headingNode){

		echo $headingNode->nodeName . "\n";
		$attributes = $this->getAttributesForElement($headingNode);

		foreach ($attributes as $key => $value) {
			
			if(in_array('hide', $value)) return ;			
		}


		$nodes = $headingNode->childNodes;
		$line = '';

		if (!is_null($nodes)) {

		  foreach ($nodes as $node) {

		  	if($node->nodeName != '#text')
				$line .= $this->mapping[$node->nodeName . ".b"] . $this->parseInlineElement($node) . $this->mapping[$node->nodeName . ".a"];
			else
				$line .= $node->nodeValue;				
			  	// echo "\t --> " . $node->nodeName . " ";
		    }
		}  
	
		if($attributes){

		  	foreach ($attributes as $key => $values) {

		  		foreach($values as $value)
			  		$line = $this->attrMapping[$value . ".b"] . $line . $this->attrMapping[$value . ".a"];
		  	}
		}

		  $line = $this->generalReplacements($line);
		  echo "\t --> " . $line . "\n";
	}

	public function parseParaElement($paragraph){


		echo $paragraph->nodeName . "\n";

		$attributes = $this->getAttributesForElement($paragraph);

		$nodes = $paragraph->childNodes;
		$line = '';

		if (!is_null($nodes)) {

		  foreach ($nodes as $node) {

		  	if($node->nodeName != '#text')
				$line .= $this->mapping[$node->nodeName . ".b"] . $this->parseInlineElement($node) . $this->mapping[$node->nodeName . ".a"];
			else
				$line .= $node->nodeValue;				
			  	// echo "\t --> " . $node->nodeName . " ";
		  }

		  if($attributes){

		  	foreach ($attributes as $key => $values) {

		  		foreach($values as $value)
			  		$line = $this->attrMapping[$value . ".b"] . $line . $this->attrMapping[$value . ".a"];
			}
		}

		  $line = $this->generalReplacements($line);
		  echo "\t --> " . $line . "\n";
		}
	}

	public function parseBlockElement($node){

		echo $paragraph->nodeName . "\n";

		$attributes = $this->getAttributesForElement($paragraph);

		$nodes = $paragraph->childNodes;
		$line = '';

		if (!is_null($nodes)) {

		  foreach ($nodes as $node) {

		  	if($node->nodeName != '#text')
				$line .= $this->mapping[$node->nodeName . ".b"] . $this->parseInlineElement($node) . $this->mapping[$node->nodeName . ".a"];
			else
				$line .= $node->nodeValue;				
			  	// echo "\t --> " . $node->nodeName . " ";
		  }

		  if($attributes){

		  	foreach ($attributes as $key => $values) {

		  		foreach($values as $value)
			  		$line = $this->attrMapping[$value . ".b"] . $line . $this->attrMapping[$value . ".a"];
			}
		}

		  $line = $this->generalReplacements($line);
		  echo "\t --> " . $line . "\n";
		}
	}

	public function parseImgElement($node){

		echo 'Img' . "\n";	
	} 

	public function parseInlineElement($inlineNode){

		$inlineNodeName = $inlineNode->nodeName;
		$tmpString = '';

		$nodes = $inlineNode->childNodes;
		$flagText = 0;

		if (!is_null($nodes)) {
		  foreach ($nodes as $node) {

		  	if($node->nodeName != '#text'){
				// echo $node->nodeName . "\n";
				$tmpString .= $this->mapping[$node->nodeName . ".b"] . $this->parseInlineElement($node) . $this->mapping[$node->nodeName . ".a"];
		  	}
			else{

				$tmpString .= $node->nodeValue;
			}
		  }
		}

		return $tmpString;
	}

	public function getAttributesForElement($element){

		$length = $element->attributes->length;
		$attrs = array();

		for ($i = 0; $i < $length; ++$i) {

		    $name = $element->attributes->item($i)->name;
		    
		    if($name != 'epub:type'){
			 
			    $value = $element->getAttribute($name);
			    $attrs[$name] = preg_split('/ /', $value);
			}
		}

		$attrs = array_filter($attrs);
		return $attrs;
	}

	public function generalReplacements($data){


		return $data;
	}

	public function debugAttributes(){

		return;
	}
}
?>