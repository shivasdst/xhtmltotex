<?php 

class Xhtmltotex{

	public $mapping = array("br.b"=>"\\\\",
						"br.a"=>"",
						"em.b"=>"\\textit{",
						"em.a"=>"}",
						"strong.b"=>"\\textbf{",
						"strong.a"=>"}",						
						"span.b"=>"\\general{",
						"span.a"=>"}",						
						"sup.b"=>"\\supskpt{",
						"sup.a"=>"}"
					);

	public $attrMapping = array(
						"text-center.b" => "\n\\begin{center}\n",
						"text-center.a" => "\n\\end{center}\n",
						"title.b" => "\\bookTitle{",
						"title.a" => "}\n",
						"titleauthor.b" => "\\titleauthor{",
						"titleauthor.a" => "}",
						"text-right.b" => "\n\\begin{flushright}\n",
						"text-right.a" => "\n\\end{flushright}\n",
						"text-left.b" => "\n\\begin{flushleft}\n",
						"text-left.a" => "\n\\end{flushleft}\n",
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
						"en.b" => "\\eng{",
						"en.a" => "}",
					);

	public $footnotes = array();

	public function __construct() {

		$this->loadFootnotes();
		// var_dump($this->footnotes);
	}

	public function loadFootnotes(){

		$footNoteFile = $this->getFootNoteFile(); 

		if($footNoteFile == '') return;

		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHTMLFile($footNoteFile);

		$asideElements = $dom -> getElementsByTagName( "aside" );

		if (!is_null($asideElements)) {
		  foreach ($asideElements as $aside) {
		    // echo "\n". $aside->nodeName. ": ";

		  	$idValue = $aside->getAttribute('id'); 

		    $nodes = $aside->childNodes;
		    foreach ($nodes as $node) {
				 // echo "\n". $node->nodeName. ": ";
		    	if (preg_match('/p|div/', $node->nodeName))
			    	$node = $this->deleteFirstchildOrChar($node);
		      	if (preg_match('/p|div/', $node->nodeName)) 
		  			$this->footnotes[$idValue] = $this->parseParaElement($node); 
		    	}
		  	}
		}
	}

	public function deleteFirstchildOrChar($node){

		$firstChild = $node->childNodes->item(0);
		if($firstChild->nodeName == '#text'){

			$node->childNodes->item(0)->nodeValue = substr($firstChild->nodeValue, 1); 
		}
		else{

			$node->removeChild($firstChild); 
		}

		// echo "\t --> " . $node->nodeName . ' -> ' . $node->nodeValue . "\n";

		return $node; 
	}

	public function getFootNoteFile(){

		$xhtmlFiles = $this->getXhtmlFiles();
		$footNoteFile = preg_grep("/.*\/999-.*\.xhtml/", $xhtmlFiles);

		if(empty(array_filter($footNoteFile))) return '';
		else {
			
			foreach ($footNoteFile as $file) {
				return $file;
			}	
		}
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

		$ouputDir = 'output/src/';

		foreach ($xhtmlFiles as $xhtmlFile) {		

			if(preg_match('/.*\/999.*\.xhtml$/', $xhtmlFile)) continue;

			$texFile = basename($xhtmlFile);
			$texFile = str_replace('.xhtml', '.tex', $texFile);
			$texFile = $ouputDir . $texFile;
			// echo $texFile . "\n";

			$data = $this->xhtmlToTeX($xhtmlFile);

			// var_dump($data);
			if($data != ''){

				file_put_contents($texFile, $data);
			}
			else{

				echo "problem in XHTML to TeX conversion for : " . $texFile . "\n";
			}
		}
	}

	public function xhtmlToTeX($xhtmlFile){

		// echo $xhtmlFile . "\n";
		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHTMLFile($xhtmlFile);
		// libxml_clear_errors();

		$data = '';

		$bodyTag = $dom->getElementsByTagName('body');
		// var_dump($bodyTag);

		if (!is_null($bodyTag)) {
		 
		  foreach ($bodyTag as $element) {

		    // echo "<br/>". $element->nodeName. ": ";
		    $nodes = $element->childNodes;
		    foreach ($nodes as $node) {

		      if($node->nodeName == 'section')
		      	$data = $data . $this->parseSectionElement($node);
		    }
		  }
		}

		if($data != '') 
			return $data;
		else 
			return ''; 
	}

	public function parseSectionElement($section){

		$lines = '';
 	  	$nodes = $section->childNodes;

		if (!is_null($nodes)) {

		  foreach ($nodes as $node) {

		  	if($node->nodeName == 'img')
		  		$lines = $lines . "\n" . $this->parseImgElement($node);
		  	elseif (preg_match('/h[1-6]|p/', $node->nodeName)) 
		  		$lines = $lines . "\n" . $this->parseBlockElement($node); 
  		    }

  		    $lines = $lines . "\n";
		}	

		if($lines != '') 
			return $lines;
		else
			return '';

	}

	public function parseBlockElement($blockElement){

		// echo $blockElement->nodeName . "\n";

		$attributes = $this->getAttributesForElement($blockElement);

		foreach ($attributes as $key => $value) {
			
			if(in_array('hide', $value)) return ;			
		}

		$nodes = $blockElement->childNodes;
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
		  $line .= "\n";
		  //echo "\t --> " . $line . "\n";
		  
		  return $line;
		}

		return '';
	}

	public function parseImgElement($imgNode){

		// echo $imgNode->nodeName . "\n";	

		$attributes = $this->getAttributesForElement($imgNode);
		$line = "\\begin{center}\n";
		$line .= "\\includegraphics{\"" . $attributes['src'][0] . "\"}\n"; 
		$line .= "\\end{center}";

		$line = $this->generalReplacements($line);
		$line .= "\n\n";

		return $line;
		// echo "\t --> " . $line . "\n";
	} 

	public function parseInlineElement($inlineNode){

		$inlineNodeName = $inlineNode->nodeName;
		// echo $inlineNodeName . "\n";

		$attributes = $this->getAttributesForElement($inlineNode);

		if(array_key_exists('href', $attributes)){

			$footNoteText = $this->getFootNoteText($attributes);
			// echo "\t --> " . $inlineNodeName . ' -> ' . $attributes['href'][0] . ' -> ' . $footNoteText . "\n";
		}

		$tmpString = '';

		$nodes = $inlineNode->childNodes;

		if (!is_null($nodes)) {
		  foreach ($nodes as $node) {

		  	if($node->nodeName != '#text'){
				// echo $node->nodeName . "\n";
				// $tmpString .= $this->mapping[$node->nodeName . ".b"] . $this->parseInlineElement($node) . $this->mapping[$node->nodeName . ".a"];
		  		if($node->nodeName != 'a')
					$tmpString .= $this->mapping[$node->nodeName . ".b"]; 
	
				$inLine = $this->parseInlineElement($node);

				  if($attributes){
				  	// echo 'HH';	
				  	foreach ($attributes as $key => $values) {
				  			// echo "\t --> " . $key . ' -> ' . "\n";
					  		foreach($values as $value){
					  			if($key == 'href')
									$inLine = '\footnote{' . $footNoteText . '}';
						  		else
							  		$inLine = $this->attrMapping[$value . ".b"] . $inLine . $this->attrMapping[$value . ".a"];
					  		}
						}
					}

				$tmpString .= $inLine;	

		  		if($node->nodeName != 'a')
					$tmpString .=  $this->mapping[$node->nodeName . ".a"];	
		  	}
			else{

					$inLine = $node->nodeValue;

				  if($attributes){
				  	// echo 'HH';	
				  	foreach ($attributes as $key => $values) {
				  		  		
			  		  		foreach($values as $value){
			  		  			if($key == 'href')
			  						$inLine = '\footnote{' . $footNoteText . '}';
			  			  		else
			  				  		$inLine = $this->attrMapping[$value . ".b"] . $inLine . $this->attrMapping[$value . ".a"];
			  		  		}
						}
					}

				$tmpString .= $inLine;
			}
		  }
		}

		return $tmpString;
	}

	public function getAttributesForElement($element){

		// echo "\t --> " . $element->nodeName . "\n";
		$length = $element->attributes->length;
		$attrs = array();

		for ($i = 0; $i < $length; ++$i) {

		    $name = $element->attributes->item($i)->name;
		    
		    if(!preg_match('/epub:type|alt/',$name)){
			 
			    $value = $element->getAttribute($name);
			    $attrs[$name] = preg_split('/ /', $value);
			}
		}

		$attrs = array_filter($attrs);
		return $attrs;
	}

	public function getFootNoteText($attributes){

		// echo "\nHHH" . $attributes['href'][0] . "\n";

		if(preg_match('/999(.*?)\#(.*)/', $attributes['href'][0],$matches)){

			// echo "\t --> href -> " . $this->footnotes[$matches[2]];
			return $this->footnotes[$matches[2]];
		}

		return '';
	}

	public function generalReplacements($data){

		$data = preg_replace("/[\t ]+/", " ", $data);		
		$data = preg_replace("/\\\\begin\{(.*?)\}\n *\n/u", '\begin{' . "$1" . "}\n", $data);
		$data = preg_replace("/\n *\n\\\\end\{(.*?)\}/u", "\n" . '\end{' . "$1" . "}", $data);
		$data = preg_replace("/(\\\\\\\\)(\\\\)/u", "$1" . "\n" . "$2", $data);

		return $data;
	}

	public function debugAttributes(){

		return;
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


		// echo $paragraph->nodeName . "\n";

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
		  return $line;
		  // echo "\t --> " . $line . "\n";
		}
	}

}
?>