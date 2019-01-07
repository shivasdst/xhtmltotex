<?php 

class Xhtmltotex{

	public $mapping;
	public $attrMapping;

	public $footnotes = array();

	public $numbered = True;
	public $bibliography = False;
	public $currentFile;

	public function __construct($id) {

		$this->loadDefaultMappings();
		$this->overrideMappings($id);
		$this->loadFootnotes($id);
		// var_dump($this->footnotes);
	}

	public function loadDefaultMappings(){

		$jsonPath = MAPPING . 'defaults.json';
		$this->loadMappings($jsonPath);
	}

	public function overrideMappings($id){

		$jsonPath = MAPPING . $id . '.json';
		$this->loadMappings($jsonPath);
	}

	public function loadMappings($jsonPath){

		if(file_exists($jsonPath)){

			$contents = json_decode(file_get_contents($jsonPath),true);
			
			if( isset($contents['attrmapping']) && (sizeof($contents['attrmapping']) > 0) ){

				foreach($contents['attrmapping'] as $key=>$value)
						$this->attrMapping[$key] = $value; 
			}			
			if( isset($contents['tagmapping']) && (sizeof($contents['tagmapping']) > 0) ){

				foreach($contents['tagmapping'] as $key=>$value)
						$this->mapping[$key] = $value; 
			}			
		}

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

			$this->currentFile = $xhtmlFile;

			if(preg_match('/.*\/999.*\.xhtml$/', $xhtmlFile)) continue;
			if(preg_match('/.*\/toc\.xhtml$/', $xhtmlFile)) continue;

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

					if($node->nodeName == 'section'){

						$data = $data . $this->parseSectionElement($node);
						$data = str_replace('ZZ38ZZ', '&', $data);
						$data = str_replace('ZZ35ZZ', '#', $data);
						$data = str_replace('ZZ95ZZ', '_', $data);
						$data = str_replace('ZZ37ZZ', '%', $data);
						$data = str_replace('<', '\textless ', $data);
						$data = str_replace('>', '\textgreater ', $data);
						$data = str_replace('\\general{\-}', '\-', $data);
						$data = str_replace('ZZ3CZZ', '<', $data);
						$data = str_replace('ZZ3EZZ', '>', $data);
					}
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
		$this->numbered = (isset($attributes['class']) && in_array('numbered', preg_split('/ /', $attributes['class'][0])))? True : False;

		if (!is_null($nodes)) {

			foreach ($nodes as $node) {

				if($node->nodeName == 'img')
					$lines = $lines . "\n" . $this->parseImgElement($node);
				elseif($node->nodeName == 'section'){

					$attributes = $this->getAttributesForElement($node);
					$this->numbered = (isset($attributes['class']) && in_array('numbered', preg_split('/ /', $attributes['class'][0])))? True : False;
					// echo $node->nodeName . "->\t->" . $this->numbered . "\n";
					$lines = $lines . "\n\n" . $this->parseSectionElement($node);
				}
				elseif (preg_match('/h[1-6]|p|ul|ol/', $node->nodeName)) 
					$lines = $lines . "\n" . $this->parseBlockElement($node);
				elseif($node->nodeName == 'table')
					$lines = $lines . "\n\n" . $this->parseTableElement($node);				
				elseif($node->nodeName == 'figure')
					$lines = $lines . "\n\n" . $this->parseFigureElement($node);		  		 
				elseif($node->nodeName != '#text'){
					// echo "\t --> " . $node->nodeName . "-->\n";
				}
				else{

					// echo "\t --> " . $node->nodeName . "--> not handled\n";	
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
		$itemSep = '';
		$optionalTitle = '';
		$captionStar = '';
		$dataItemFormat = '';
		$labelData = '';

		// var_dump($attributes);	

		foreach ($attributes as $key => $value) {
			
			// echo "\t --> " . $key . ' -> ' . $value[0] . "\n";
			if(in_array('hide', $value)) return ;	
			if(in_array('level1-title hide', $value)) return ;	
			if(in_array('bibliography', $value)) $this->bibliography = True;			
			if($key == 'id') $blockElementId = $value[0];			
			if($key == 'data-itemsep') {$itemSep = $value[0]; unset($attributes['data-itemsep']);}	
			if($key == 'title-option') {$optionalTitle = $value[0]; unset($attributes['title-option']);}			
			if($key == 'data-tex-caption-prefix') {$captionStar = $value[0]; unset($attributes[$key]);}			
			if($key == 'data-item-format') {$dataItemFormat = $value[0]; unset($attributes[$key]);}			
		}

		if(isset($attributes['id']) && preg_match('/h[1-6]/', $blockElement->nodeName))	
			$labelData = '\\label{' . $attributes['id'][0] . '}'; 

		if(isset($attributes['id'])) unset($attributes['id']);

		if( isset($attributes['data-tex']) && $blockElement->nodeName == 'p' ){

			if($attributes['data-tex'][0] == 'vfill')
				return '\\vfill' . "\n";
			elseif($attributes['data-tex'][0] == 'bigskip')
				return '\\bigskip' . "\n";			
			elseif($attributes['data-tex'][0] == 'smallskip')
				return '\\smallskip' . "\n";			
			elseif($attributes['data-tex'][0] == 'medskip')
				return '\\medskip' . "\n";
			elseif($attributes['data-tex'][0] == 'newpage')
				return '\\newpage' . "\n";
			else	
				return $blockElement->nodeValue . "\n";
		}
		if(isset($attributes['data-tex-vskip']))
			return $this->getVskipValue($attributes['data-tex-vskip'][0]);

		$nodes = $blockElement->childNodes;
		$line = '';

		if (!is_null($nodes)) {

			foreach ($nodes as $node) {

				// echo $node->nodeName . "\n";

				if($node->nodeName != '#text'){

					if( ($node->nodeName == 'span') || ($node->nodeName == 'sup') )
						$line .= $this->parseInlineElement($node);
					elseif($node->nodeName == 'a'){

						$tmpString = $this->parseInlineElement($node);
						$tmpAttrs = $this->getAttributesForElement($node); 
						if(isset($tmpAttrs['class'][0]) && $tmpAttrs['class'][0] == 'url'){
							$tmpString = str_replace('&', 'ZZ38ZZ', $tmpString);
							$tmpString = str_replace('#', 'ZZ35ZZ', $tmpString);
							$tmpString = str_replace('_', 'ZZ95ZZ', $tmpString);
							$tmpString = str_replace('%', 'ZZ37ZZ', $tmpString);
							// echo $tmpString . "\n";
						}
	
						$line .= $tmpString;
					}
					elseif( ($node->nodeName == 'li') || ($node->nodeName == 'ul') || ($node->nodeName == 'ol') )
						$line .=  $this->parseBlockElement($node);				
					elseif($node->nodeName == 'img')
						$line .= $this->parseImgElement($node);
					else{
						
						// echo "\n" . $node->nodeName . "\n";
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

							// echo "\n-->" . $line . "<--\n";								
						}
						elseif( ($blockElement->nodeName == 'ol') || ($blockElement->nodeName == 'ul') ){

							$tmpString = $this->attrMapping[$value . ".b"];	
							
							if($dataItemFormat != '')
								$tmpString = $tmpString . $dataItemFormat;
							if($itemSep != '')
								$line = $tmpString . "\n" . '\itemsep=' . $itemSep . "\n" . $line . $this->attrMapping[$value . ".a"];
							else
								$line = $tmpString . "\n" . $line . $this->attrMapping[$value . ".a"];
						} 
						else{

							$line = $this->attrMapping[$value . ".b"] . $line . $this->attrMapping[$value . ".a"];
						}
					}
				}
			}

			if (preg_match('/h[1-6]/', $blockElement->nodeName)) {

				// echo "h[1-h6]->" . $line . "\n";
				if($optionalTitle != '')
					$line = preg_replace("/(.*?)\{(.*)\}/", '$1[' . $optionalTitle. ']{$2}', $line);
	
				$line = preg_replace("/\\\\footnote/", '\protect\footnote', $line);
				$line = preg_replace("/\\\\endnote/", '\protect\endnote', $line);
				if($labelData != '')
					$line = $line . $labelData;
			}

			if( ($blockElement->nodeName == 'li') && !($attributes))
				$line = "\\item " . $line;

			if( ($blockElement->nodeName == 'ol') && ($this->bibliography) )
				$this->bibliography = False;	
			if( ($blockElement->nodeName == 'figcaption') ){
				
				if($captionStar != '')
					$line = "\n\\caption*{" . $line . "}";
				else
					$line = "\n\\caption{" . $line . "}";
			}			
			if( ($blockElement->nodeName == 'caption') ){
				
				if($captionStar != '')
					$line = "\n\\caption*{" . $line . "}";
				else
					$line = "\n\\caption{" . $line . "}";
			}

			$line = $this->generalReplacements($line);
			$line .= "\n";
			

			// echo "\t --> " . $line . "\n";

			return $line;
		}

		return '';
	}

	public function parseInlineElement($inlineNode){

		$inlineNodeName = $inlineNode->nodeName;
		$footcmd = '\footnote';
		$endnotemark = '';
		$crossRef = '';
		// echo $inlineNodeName . "\n";

		if($inlineNode->nodeName != '#text'){
			$attributes = $this->getAttributesForElement($inlineNode);
			// var_dump($attributes);

			if( array_key_exists('href', $attributes) && (preg_match('/^999\-aside/', $attributes['href'][0])) ){

				$footNoteText = $this->getFootNoteText($attributes);
				// echo "\t --> " . $inlineNodeName . ' -> ' . $attributes['href'][0] . ' -> ' . $footNoteText . "\n";
			}			

			if( array_key_exists('footertype', $attributes) && ($inlineNode->nodeName == 'a') && in_array('endnote', $attributes['footertype']) ){

				$footcmd = '\endnote';
				// echo "\t --> " . $inlineNodeName . ' -> ' . $attributes['footertype'][0] . ' -> ' . $footcmd . "\n";
				unset($attributes['footertype']);
				unset($attributes['id']);
			}			

			if( array_key_exists('footertype', $attributes) && ($inlineNode->nodeName == 'a') && in_array('footnote', $attributes['footertype']) ){

				$footcmd = '\footnote';
				// echo "\t --> " . $inlineNodeName . ' -> ' . $attributes['footertype'][0] . ' -> ' . $footcmd . "\n";
				unset($attributes['footertype']);
				unset($attributes['id']);
			}			

			if( array_key_exists('footertype', $attributes) && ($inlineNode->nodeName == 'a') && in_array('endnotemark', $attributes['footertype']) ){

				$endnotemark = '\endnotemark[\theendnote]';
				// echo "\t --> " . $inlineNodeName . ' -> ' . $attributes['footertype'][0] . ' -> ' . $footcmd . "\n";
				unset($attributes['footertype']);
				unset($attributes['id']);
			}
			if( ($inlineNode->nodeName == 'a') && isset($attributes['class']) && in_array('crossref', $attributes['class']) ){

				$crossRef = '\\ref{';
				$idRef = $attributes['href'][0];
				$idRef = str_replace('#', '', $idRef);
				// echo "\t --> " . $inlineNodeName . ' -> ' . $attributes['footertype'][0] . ' -> ' . $footcmd . "\n";
				$crossRef = $crossRef . $idRef . "}";
				// echo $crossRef . "\n";
				unset($attributes['class']);
				unset($attributes['href']);
				return $crossRef;
			}
			if( isset($attributes['data-tex']) && $inlineNode->nodeName == 'span' ){
				// var_dump($attributes['data-tex'][0]);
				// echo "\t data-tex -> " . $attributes['data-tex'][0] . "\n";
				
				if(isset($attributes['data-tex'])){	
		
					if($attributes['data-tex'][0] == 'hfill')
						return '\\hfill' . " ";
					elseif($attributes['data-tex'][0] == 'break')
						return '\\break' . " ";					
					elseif($attributes['data-tex'][0] == '-')
						return '\\-';
					else	
						return $attributes['data-tex'][0];
				}
			}
			if(isset($attributes['data-index-primary'])){

				$indexValue = $this->getIndexValue($attributes);
				return $indexValue;
			}
			if(isset($attributes['data-tex-hskip']))
				return $this->getHskipValue($attributes['data-tex-hskip'][0]);
			if(isset($attributes['data-tex-vskip']))
				return $this->getVskipValue($attributes['data-tex-vskip'][0]);							
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
					else{

						$tmpString .= $this->parseInlineElement($node);	
						// echo "a -> ". $tmpString . "\n";
					}
				}
				else{

						$tmpString .= $node->nodeValue;
						// echo $tmpString . "-->" . $node->nodeName . "\n";
				}
			}
		}

		if(isset($attributes)){
			// var_dump($attributes);
			// echo 'HH'. "->" . $node->nodeName . "\n";	
			foreach ($attributes as $key => $values) {

				// echo "\t --> " . $key . ' -> ' . "\n";
				foreach($values as $value){
					
					// echo "\t --> " . $key . ' -> ' . $value . "\n";
					if($key == 'href'){

						if($endnotemark != '')
							$tmpString = $endnotemark;
						elseif(preg_match('/^999\-aside/', $attributes['href'][0]) && $endnotemark == '' )
							$tmpString = $footcmd. '{' . $footNoteText . '}';

					}
					else{

						// echo "\t --> " . $key . ' -> ' . $value . "\n";
						$tmpString = $this->attrMapping[$value . ".b"] . $tmpString . $this->attrMapping[$value . ".a"];
						// echo $tmpString . "-->" . $node->nodeName . "\n";

					}
				}
			}
		}
		else{

			echo $crossRef . "\n";
		}

		// echo "\n.." . $tmpString . "..\n";
		return $tmpString;
	}

	public function getVskipValue($vskipValue){

		$parts = preg_split('/:/', $vskipValue);
		if(sizeof($parts) == 1)
			return '\\' . $vskipValue . "\n";

		return '\\' . $parts[0] . ' ' . $parts[1] . "\n"; 
	}	

	public function getHskipValue($hskipValue){

		$parts = preg_split('/:/', $hskipValue);
		if(sizeof($parts) == 1)
			return '\\' . $hskipValue . " ";

		return '\\' . $parts[0] . ' ' . $parts[1] . " "; 
	}

	public function getIndexValue($attributes){

		// var_dump($attributes);
		$indexValue = '\index{';

		if(isset($attributes['data-index-primary-sort']))
			$indexValue .= $attributes['data-index-primary-sort'][0] . '@';
		$indexValue .= $attributes['data-index-primary'][0];


		if(isset($attributes['data-index-secondary'])){

			$indexValue .= '!';

			if(isset($attributes['data-index-secondary-sort']))
				$indexValue .= $attributes['data-index-secondary-sort'][0] . '@';

			$indexValue .= $attributes['data-index-secondary'][0];
		}

		$indexValue = str_replace('<b>', '\\textbf{', $indexValue);
		$indexValue = str_replace('</b>', '}', $indexValue);		
		$indexValue = str_replace('<i>', '\\textit{', $indexValue);
		$indexValue = str_replace('</i>', '}', $indexValue);		

		$indexValue = str_replace('&lt;b&gt;', '\\textbf{', $indexValue);
		$indexValue = str_replace('&lt;/b&gt;', '}', $indexValue);		
		$indexValue = str_replace('&lt;i&gt;', '\\textit{', $indexValue);
		$indexValue = str_replace('&lt;/i&gt;', '}', $indexValue);

		if(isset($attributes['data-range']))
			$indexValue .= $attributes['data-range'][0];
		
		$indexValue .= '}';		

		return $indexValue;
	}


	public function parseFigureElement($figureElement){

		$attributes = $this->getAttributesForElement($figureElement);
		$floatParams = '';
		$floatEnv = '';
		$imgLine = '';
		$floatCenter = '';
		// var_dump($attributes);

		foreach ($attributes as $key => $value) {
			
			// echo "\t --> " . $key . ' -> ' . $value[0] . "\n";
			if($key == 'data-tex-float-params') {$floatParams = $value[0]; unset($attributes[$key]);}
			if($key == 'data-tex-float-env') {$floatEnv = $value[0]; unset($attributes[$key]);}
			if($key == 'data-tex-float-center') {$floatCenter = '\\' . $value[0]; unset($attributes[$key]);}
		}

		$nodes = $figureElement->childNodes;

		if($floatEnv != '')
			$line = '\\begin{' . $floatEnv . "}";
		else
			$line = $this->mapping[$figureElement->nodeName . ".b"];

		if( $floatParams != '' )
			$line = $line . $floatParams . "\n";
		else
			$line = $line . "\n";

		if (!is_null($nodes)) {

			foreach ($nodes as $node) {

				// echo $node->nodeName . "\n";
				if( ($node->nodeName == 'img') ){

					$imgLine = $this->parseImgElement($node);

					if($floatCenter != '')
						$line .= $floatCenter . "\n" . $imgLine;	
					else
						$line .= $imgLine;	
				}
				elseif( ($node->nodeName == 'figcaption') ){

					$line .= $this->parseBlockElement($node);
					// echo $line . "\n";
				}
			}
		}

		if(isset($attributes)){
			// var_dump($attributes);
			// echo 'HH'. "->" . $node->nodeName . "\n";	
			foreach ($attributes as $key => $values) {

				// echo "\t --> " . $key . ' -> ' . "\n";
				foreach($values as $value){
					
					// echo "\t --> " . $key . ' -> ' . $value . "\n";
					if($key == 'id'){

						$label = "\\label{" . $value . "}";
						// echo $line . "\n";
						$line = preg_replace("/(\\\\caption\*?\{.*\})/",  "$1" . $label, $line);	
					}
				}
			}
		}

		if($floatEnv != '')
			$line = $line . '\\end{' . $floatEnv ."}\n";		
		else
			$line = $line . $this->mapping[$figureElement->nodeName . ".a"] . "\n";		

		$line = preg_replace("/\n\n\\\\caption/u", "\n" . '\\caption', $line);
		$line = preg_replace("/}\\\\end\{figure\}/u", "}\n" . '\\end{figure}', $line);

		return $line;
	}

	public function parseTableElement($tableElement){

		// echo $tableElement->nodeName . "\n";

		$attributes = $this->getAttributesForElement($tableElement);
		$hline = "";
		$firstRowFlag = 0;
		$floatParams = '';
		// var_dump($attributes);

		foreach ($attributes as $key => $value) {
			
			// echo "\t --> " . $key . ' -> ' . $value[0] . "\n";
			if(in_array('hide', $value)) return ;	
			if($key == 'data-tex-hrule') {$hline = '\\' . $value[0]; $firstRowFlag=1;}
			if($key == 'data-tex-float-params') {$floatParams = $value[0]; unset($attributes[$key]);}
		}

		$nodes = $tableElement->childNodes;

		if(isset($attributes['data-tex'])){
			$attributes['data-tex'] = str_replace('<', 'ZZ3CZZ', $attributes['data-tex']);
			$attributes['data-tex'] = str_replace('>', 'ZZ3EZZ', $attributes['data-tex']);
		}


		if( isset($attributes['data-table']) && isset($attributes['data-tex']) )
			$line = '\begin{'. $attributes['data-table'][0] .'}'. $attributes['data-tex'][0] . "\n";
		else
			$line = '\begin{tabular}{}' . "\n";

		if($hline != ''){

			$line .= $hline . "\n";			
		}


		if (!is_null($nodes)) {

			foreach ($nodes as $node) {

				// echo $node->nodeName . "\n";	
				if( ($node->nodeName == 'tr') ) {

					$trAttributes = $this->getAttributesForElement($node);	
					$endrow = '\\\\';

					if(isset($trAttributes['data-endrow']))
						$endrow = '\\' . $trAttributes['data-endrow'][0];

					$trValue = $this->parseTrElement($node);
					$trValue = preg_replace("/&\s$/", '', $trValue);
					$line .=  $trValue . $endrow . "\n";
					
					if($hline != ''){
						
						$line .= $hline . "\n";	 
					}

					$firstRowFlag = 0;
				}
				elseif( ($node->nodeName == 'caption') ){

					$line = $this->parseBlockElement($node) . $line;
					 // echo $node->nodeName . "\n";
				}				
				else{

					// echo $node->nodeName . "\n";
				}
			}
		}

		if(isset($attributes['data-table'][0]))
			$line .= '\end{'. $attributes['data-table'][0] . '}' . "\n";
		else
			$line = '\end{tabular}{}';

		if($floatParams != '')
			$line = '\\begin{table}' . $floatParams . "\n" . $line . "\\end{table}\n";

		$line = preg_replace("/\n\n\\\\caption/u", "\n" . '\\caption', $line);

		if(isset($attributes['id'])){

			$label = "\\label{" . $attributes['id'][0] . "}";
			// echo $label . "\n";
			$line = preg_replace("/(\\\\caption\*?\{.*\})/",  "$1" . $label, $line);	
		}

		$line = str_replace("%", "\\%", $line);
		return $line;
	}	

	public function parseTrElement($trElement){

		// echo $trElement->nodeName . "\n";

		$attributes = $this->getAttributesForElement($trElement);
		// var_dump($attributes);
		$endrow = '\\';

		foreach ($attributes as $key => $value) {
			
			// echo "\t --> " . $key . ' -> ' . $value[0] . "\n";
			if(in_array('hide', $value)) return ;
			if($key == 'data-endrow') $endrow = "\\" . $value[0];
		}

		$nodes = $trElement->childNodes;		
		$line = '';		

		if (!is_null($nodes)) {

			foreach ($nodes as $node) {

				// echo $node->nodeName . "\n";	
				if( ($node->nodeName == 'td') || ($node->nodeName == 'th') )
					$line .= $this->mapping[$node->nodeName . ".b"] . $this->parseTdThElement($node) . $this->mapping[$node->nodeName . ".a"] . ' & ';
			}
		}

		return $line;		
	}	

	public function parseTdThElement($tdElement){

		// echo $trElement->nodeName . "\n";

		$attributes = $this->getAttributesForElement($tdElement);
		$multicol = '';

		foreach ($attributes as $key => $value) {
			
			// echo "\t --> " . $key . ' -> ' . $value[0] . "\n";
			if($key == 'data-colspan') $multicol = '\multicolumn{'. $value[0] .'}';
			if($key == 'data-align') $multicol .= '{'. $value[0] . '}';
		}

		$nodes = $tdElement->childNodes;		
		$line = '';		

		if (!is_null($nodes)) {

			foreach ($nodes as $node) {

				// echo $node->nodeName . "\n";	
				if( ($node->nodeName != '#text'))
					$line .= $this->mapping[$node->nodeName . ".b"] . $this->parseInlineElement($node) . $this->mapping[$node->nodeName . ".a"];
				else
					$line .= $node->nodeValue;
			}
		}

		if($multicol != '') 
			return $multicol . '{' . $line . '}';

		return $line;		
	}

	public function parseImgElement($imgNode){

		// echo $imgNode->nodeName . "\n";	

		$attributes = $this->getAttributesForElement($imgNode);
		// var_dump($attributes);

		if(isset($attributes['data-scale']))
			$line = "\\includegraphics[scale=". $attributes['data-scale'][0] ."]{\"" . $attributes['src'][0] . "\"}"; 
		else
			$line = "\\includegraphics{\"" . $attributes['src'][0] . "\"}"; 


		$line = $this->generalReplacements($line);
		// $line .= "\n\n";

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

				$attrs[$name] = [];
				$value = $element->getAttribute($name);

				// if($name == 'class')
				// 	$attrs[$name] = preg_split('/ /', $value);
				// else
					array_push($attrs[$name],$value);
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

		//single and double quotes
		$data = str_replace('&lsquo;', '‘', $data);
		$data = str_replace('&rsquo;', '’', $data);
		$data = str_replace('&ldquo;', '“', $data);
		$data = str_replace('&rdquo;', '”', $data);		

		// $data = str_replace('_', '\_', $data);
		// $data = str_replace('{', '\{', $data);
		// $data = str_replace('}', '\}', $data);

		$data = str_replace("&", "\&", $data);
		$data = str_replace('#', '\#', $data);
		$data = str_replace("\\\\#", '\#', $data);
		$data = str_replace("\\\\&", "\&", $data);
		$data = str_replace("ಶ್ರೀ", "ಶ‍್ರೀ", $data);
		$data = str_replace("%", "\\%", $data);

		//for sanskrit and hindi texts
		$data = str_replace(" ।", "~।", $data);
		$data = str_replace(" ॥", "~॥", $data);

		//below line is for rkmath mysore books
		 $data = str_replace("-", "–", $data);
		 $data = str_replace('\–', '\-', $data);
		 
		 //replace degree symbol(°) with \circ
		 $data = str_replace('°', '\circ', $data);



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
