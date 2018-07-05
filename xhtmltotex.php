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
		"sup.a"=>"}",
		"tr.b"=>"",
		"tr.a"=>"\\\\",
		"td.b"=>"",
		"td.a"=>"",		
		"th.b"=>"\\textbf{",
		"th.a"=>"}",
		"li.b"=>"\\item ",
		"li.a"=>"",		
		"small.b"=>"{\\small ",
		"small.a"=>"}"	
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
		"vertical-delimiter.b" => "\\delimiter",
		"vertical-delimiter.a" => "",						
		"footnote-head.b" => "\\section*{",
		"footnote-head.a" => "}",
		"noindent.b" => "\\noindent\n",
		"noindent.a" => "",
		"num.b"=>"\\num{",
		"num.a"=>"}",
		"myquote.b" => "\n\\begin{myquote}\n",
		"myquote.a" => "\n\\end{myquote}\n",						
		"verse.b" => "\n\\begin{verse}\n",
		"verse.a" => "\n\\end{verse}\n",
		"quote-author.b" => "\n\\begin{flushright}\n",
		"quote-author.a" => "\n\\end{flushright}\n",
		"itemize.b" => "\n\\begin{itemize}\n",
		"itemize.a" => "\n\\end{itemize}\n",									
		"enumerate.b" => "\n\\begin{enumerate}\n",
		"enumerate.a" => "\n\\end{enumerate}\n",
		"verse-num.b"=>"\\versenum{",
		"verse-num.a"=>"}",		
		"bibliography.b"=>"\\begin{thebibliography}{99}\n",
		"bibliography.a"=>"\\end{thebibliography}\n",		
		"bibitem.b"=>"\\bibitem{",
		"bibitem.a"=>"}",		
		"publisher.b"=>"\\publisher{",
		"publisher.a"=>"}",		
		"place.b"=>"\\place{",
		"place.a"=>"}"											
		);

	public $footnotes = array();

	public $numbered = True;
	public $bibliography = False;

	public function __construct($id) {

		$this->loadFootnotes($id);
		// var_dump($this->footnotes);
	}

	public function loadFootnotes($id){

		$footNoteFile = $this->getFootNoteFile($id); 

		if($footNoteFile == '') return;

		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		if(!$dom->loadHTMLFile($footNoteFile))
			echo "not able to open the file\n";

		$asideElements = $dom->getElementsByTagName("aside");
		// var_dump($asideElements);

		if (!is_null($asideElements)) {

			foreach ($asideElements as $aside) {
				// echo "\n". $aside->nodeName. ": ";

				$idValue = $aside->getAttribute('id'); 

				$nodes = $aside->childNodes;
				$count = 0;
				foreach ($nodes as $node) {
					
					// echo "\n". $node->nodeName. ": ";
					// if (preg_match('/p|div/', $node->nodeName))
					if($node->nodeName != '#text')
						$node = $this->deleteFirstchildOrChar($node);
					if (preg_match('/p|div/', $node->nodeName)) {
						if($count > 0)	$this->footnotes[$idValue] .= "\n\n" . rtrim($this->parseBlockElement($node)); 
						else $this->footnotes[$idValue] = rtrim($this->parseBlockElement($node));
					}

					$count++;
				}
			}
		}
	}

	public function deleteFirstchildOrChar($blockElement){

		// echo "\n\nft" . $blockElement->nodeName . "ft\n\n";

		$nodes = $blockElement->childNodes;

		foreach ($nodes as $node) {

			if($node->nodeName !='#text')
				$attributes = $this->getAttributesForElement($node);
	
			if(isset($attributes['class']) && ($attributes['class'][0] == 'ftmark'))
				$blockElement->removeChild($node);

		}

		return $blockElement; 
	}

	public function getFootNoteFile($id){

		$xhtmlFiles = $this->getXhtmlFiles($id);
		$footNoteFile = preg_grep("/.*\/999-.*\.xhtml/", $xhtmlFiles);

		if(empty(array_filter($footNoteFile))) return '';
		else {

			foreach ($footNoteFile as $file) {
				return $file;
			}	
		}
	}

	public function getXhtmlFiles($id) {

		$allFiles = [];

		$folderPath = UNICODE_SRC . $id;

		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folderPath));

		foreach($iterator as $file => $object) {

			if(preg_match('/.*\/\d+.*\.xhtml$/',$file)) array_push($allFiles, $file);
		}

		sort($allFiles);

		return $allFiles;
	}	

	public function processFiles($id,$xhtmlFiles){

		$ouputDir = TEXOUT . $id . '/src/';

		if (!file_exists(TEXOUT . $id)) {

			mkdir(TEXOUT . $id, 0775);
			echo "Directory " . $id . " is created in TeX folder\n";
		}

		if (!file_exists(TEXOUT . $id . '/src/') && file_exists(TEXOUT . $id) ) {

			mkdir(TEXOUT . $id . '/src/', 0775);
			echo "src folder created in " . $id . " folder\n";
		}

		foreach ($xhtmlFiles as $xhtmlFile) {		

			if(preg_match('/.*\/999.*\.xhtml$/', $xhtmlFile)) continue;

			$texFile = basename($xhtmlFile);
			$texFile = str_replace('.xhtml', '.tex', $texFile);
			$texFile = $ouputDir . $texFile;
			// echo $texFile . "\n";

			$data = $this->xhtmlToTeX($xhtmlFile);

			if(preg_match('/.*\/(000a-title\.xhtml|000b-copyright\.xhtml)$/', $xhtmlFile)) 
				$data = "\\thispagestyle{empty}\n" . $data;

			// var_dump($data);
			if($data != ''){
				
				$data = preg_replace("/ *\n *\n *\n/u", "\n\n", $data);
				$data = preg_replace("/ *\n *\n *\n/u", "\n\n", $data);
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

		// echo "\t --> " . $section->nodeName . "\n";
		$lines = '';
		$nodes = $section->childNodes;

		$attributes = $this->getAttributesForElement($section);
		$this->numbered = (isset($attributes['class']) && in_array('numbered', $attributes['class']))? True : False;

		if (!is_null($nodes)) {

			foreach ($nodes as $node) {

				if($node->nodeName == 'img')
					$lines = $lines . "\n" . $this->parseImgElement($node);
				elseif($node->nodeName == 'section'){

					$attributes = $this->getAttributesForElement($node);
					$this->numbered = (isset($attributes['class']) && in_array('numbered', $attributes['class']))? True : False;
					// echo $node->nodeName . "->\t->" . $this->numbered . "\n";
					$lines = $lines . "\n\n" . $this->parseSectionElement($node);
				}
				elseif (preg_match('/h[1-6]|p|ul|ol/', $node->nodeName)) 
					$lines = $lines . "\n" . $this->parseBlockElement($node);
				elseif($node->nodeName == 'table')
					$lines = $lines . "\n\n" . $this->parseTableElement($node);		  		 
				elseif($node->nodeName != '#text'){
					// echo "\t --> " . $node->nodeName . "\n";
				}
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
		$blockElementId = '';

		// var_dump($attributes);	

		foreach ($attributes as $key => $value) {
			
			// echo "\t --> " . $key . ' -> ' . $value[0] . "\n";
			if(in_array('hide', $value)) return ;			
			if(in_array('bibliography', $value)) $this->bibliography = True;			
			if($key == 'id') $blockElementId = $value[0];			
		}

		if(isset($attributes['id'])) unset($attributes['id']);

		if( isset($attributes['data-tex']) && $blockElement->nodeName == 'p' ){

			return $blockElement->nodeValue . "\n";
		}


		$nodes = $blockElement->childNodes;
		$line = '';

		if (!is_null($nodes)) {

			foreach ($nodes as $node) {

				// echo $node->nodeName . "\n";

				if($node->nodeName != '#text'){

					if( ($node->nodeName == 'span') || ($node->nodeName == 'sup') )
						$line .= $this->parseInlineElement($node);				
					else if( ($node->nodeName == 'li') )
						$line .=  $this->parseBlockElement($node);				
					else{
			
						$line .= $this->mapping[$node->nodeName . ".b"] . $this->parseInlineElement($node) . $this->mapping[$node->nodeName . ".a"];
						// echo "\t --> " . $line . "\n";
					}
				}
				else{
						
					$line .= $node->nodeValue;				
					// echo $node->nodeValue . "\t --> " . $node->nodeName . "\n";
				}
			}

			if($attributes){

				foreach ($attributes as $key => $values) {

					foreach($values as $value){

						if( !($this->numbered) && preg_match('/h[1-6]/', $blockElement->nodeName)){
							
							$line = str_replace('{', '*{', $this->attrMapping[$value . ".b"]) . $line . $this->attrMapping[$value . ".a"];
							// echo "$line\n";
						}
						elseif($blockElement->nodeName == 'li'){
 							
 							if( ($blockElementId != '') && $this->bibliography)
								$line = $this->attrMapping[$value . ".b"] . $blockElementId . $this->attrMapping[$value . ".a"] . " " . $line;
							else
								$line = "\\item " . $line;		

							echo "\n-->" . $line . "<--\n";								
						}
						else{

							$line = $this->attrMapping[$value . ".b"] . $line . $this->attrMapping[$value . ".a"];
						}
					}
				}
			}

			if (preg_match('/h[1-6]/', $blockElement->nodeName)) {

				// echo "h[1-h6]->" . $line . "\n";
				$line = preg_replace("/\\\\footnote/", '\protect\footnote', $line);
			}

			if( ($blockElement->nodeName == 'li') && !($attributes))
				$line = "\\item " . $line;

			if( ($blockElement->nodeName == 'ol') && ($this->bibliography) )
				$this->bibliography = False;

			$line = $this->generalReplacements($line);
			$line .= "\n";
			

			// echo "\t --> " . $line . "\n";

			return $line;
		}

		return '';
	}

	public function parseInlineElement($inlineNode){

		$inlineNodeName = $inlineNode->nodeName;
		// echo $inlineNodeName . "\n";

		if($inlineNode->nodeName != '#text'){
			$attributes = $this->getAttributesForElement($inlineNode);
			// var_dump($attributes);

			if(array_key_exists('href', $attributes)){

				$footNoteText = $this->getFootNoteText($attributes);
				// echo "\t --> " . $inlineNodeName . ' -> ' . $attributes['href'][0] . ' -> ' . $footNoteText . "\n";
			}

			if( isset($attributes['data-tex']) && $inlineNode->nodeName == 'span' ){
				// var_dump($attributes['data-tex'][0]);
				// echo "\t data-tex -> " . $attributes['data-tex'][0] . "\n";
				return $attributes['data-tex'][0];
			}
		}
		$tmpString = '';

		$nodes = $inlineNode->childNodes;
		// var_dump($nodes);
		if (!is_null($nodes)) {

			foreach ($nodes as $node) {

				if($node->nodeName != '#text'){
					
					// echo $node->nodeName . "\n";
					if($node->nodeName != 'a')
						$tmpString .= $this->mapping[$node->nodeName . ".b"] . $this->parseInlineElement($node) . $this->mapping[$node->nodeName . ".a"];
					else
						$tmpString .= $this->parseInlineElement($node);	
				}
				else{

					if($node->nodeName != 'a')
						$tmpString .= $node->nodeValue;
					// echo $tmpString . "-->" . $node->nodeName . "\n";
				}
			}
		}

		if($attributes){
			// var_dump($attributes);
			// echo 'HH'. "->" . $node->nodeName . "\n";	
			foreach ($attributes as $key => $values) {

				// echo "\t --> " . $key . ' -> ' . "\n";
				foreach($values as $value){
					
					// echo "\t --> " . $key . ' -> ' . $value . "\n";
					if($key == 'href'){
						$tmpString = '\footnote{' . $footNoteText . '}';
						// echo $tmpString . "\n";
					}
					else{

						// echo "\t --> " . $key . ' -> ' . $value . "\n";
						$tmpString = $this->attrMapping[$value . ".b"] . $tmpString . $this->attrMapping[$value . ".a"];
						// echo $tmpString . "-->" . $node->nodeName . "\n";

					}
				}
			}
		}

		// echo "\n.." . $tmpString . "..\n";
		return $tmpString;
	}


	public function parseTableElement($tableElement){

		// echo $tableElement->nodeName . "\n";

		$attributes = $this->getAttributesForElement($tableElement);
		// var_dump($attributes);

		foreach ($attributes as $key => $value) {
			
			// echo "\t --> " . $key . ' -> ' . $value[0] . "\n";
			if(in_array('hide', $value)) return ;			
		}

		$nodes = $tableElement->childNodes;

		if(isset($attributes['data-tex']))
			$line = '\begin{tabular}{'. $attributes['data-tex'][0] .'}' . "\n";
		else
			$line = '\begin{tabular}{}';

		if (!is_null($nodes)) {

			foreach ($nodes as $node) {

				// echo $node->nodeName . "\n";	
				if( ($node->nodeName == 'tr') ) {
					$trValue = $this->parseTrElement($node);
					$trValue = preg_replace("/&\s$/", '', $trValue);
					$line .=  $trValue . '\\\\' . "\n";
				}
			}
		}

		$line .= '\\end{tabular}' . "\n";
		return $line;
	}	

	public function parseTrElement($trElement){

		// echo $trElement->nodeName . "\n";

		$attributes = $this->getAttributesForElement($trElement);
		// var_dump($attributes);

		foreach ($attributes as $key => $value) {
			
			// echo "\t --> " . $key . ' -> ' . $value[0] . "\n";
			if(in_array('hide', $value)) return ;			
		}

		$nodes = $trElement->childNodes;		
		$line = '';		

		if (!is_null($nodes)) {

			foreach ($nodes as $node) {

				// echo $node->nodeName . "\n";	
				if( ($node->nodeName == 'td') || ($node->nodeName == 'th') )
					$line .= $this->mapping[$node->nodeName . ".b"] . $node->nodeValue . $this->mapping[$node->nodeName . ".a"] . ' & ';
			}
		}

		return $line;		
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


	public function getAttributesForElement($element){

		// echo "\t Attr --> " . $element->nodeName . " Attr\n";

		$attrs = array();

		// if($element->nodeName == '#text') return ;

		$length = $element->attributes->length;

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
		// $data = preg_replace("/(\\\\\\\\)/u", "$1", $data);
		// $data = str_replace("/(\\\\\\\\)/u", "$1", $data);
		$data = str_replace("\\\\\\\\", '\\\\', $data);
		$data = str_replace("\\\\\\\\", '\\\\', $data);
		$data = preg_replace('/\\\\\\\\\n\\\\end\{(.*?)\}/u', "\n" . '\end{' . "$1" . "}", $data);
		// $data = str_replace('\\\\', '\\', $data);
		$data = preg_replace("/\\\\chapter\{\\\\num\{.*?\} *(.*)\}/u", '\chapter{' . "$1" . "}", $data);
		$data = preg_replace("/ ([?!;:,.])/u", "$1", $data);
		$data = str_replace("&", "\&", $data);
		$data = str_replace("\\\\&", "\&", $data);

		return $data;
	}

	public function debugAttributes(){

		return;
	}

	public function parseHeadingElement($headingNode){

		// echo $headingNode->nodeName . "\n";
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
		// echo "\t --> " . $line . "\n";
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