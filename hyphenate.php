<?php

class Hyphenate{

	public function __construct() {

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

		$words = [];
		$lines = "";

		if (!file_exists(TEXOUT . $id)) {

			mkdir(TEXOUT . $id, 0775);
			echo "Directory " . $id . " is created in TeX folder\n";

		}

		foreach ($xhtmlFiles as $xhtmlFile) {		

			if(preg_match('/.*\/999.*\.xhtml$/', $xhtmlFile)) continue;

			$words = array_merge($words,$this->getKannadaWordsInFile($xhtmlFile));
		}

		array_filter($words);
		sort($words);

		$words = array_unique($words); 

		$hyphenatedWords = $this->hyphenateWords($words);

		$this->printDictionary($id,$hyphenatedWords);

	}

	public function hyphenateWords($wordsList){

		// var_dump($wordsList);

		$vyanjana = "ಕ|ಖ|ಗ|ಘ|ಙ|ಚ|ಛ|ಜ|ಝ|ಞ|ಟ|ಠ|ಡ|ಢ|ಣ|ತ|ಥ|ದ|ಧ|ನ|ಪ|ಫ|ಬ|ಭ|ಮ|ಯ|ರ|ಱ|ಲ|ವ|ಶ|ಷ|ಸ|ಹ|ಳ|ೞ";
		$swara_endings = "ಾ|ಿ|ೀ|ು|ೂ|ೃ|ೄ|ೆ|ೇ|ೈ|ೊ|ೋ|ೌ|ೕ|ೖ|ಂ|ಃ";
		$halanta = "್";
		$yogavahagalu = "ಂ|ಃ";
		$swara = "ಅ|ಆ|ಇ|ಈ|ಉ|ಊ|ಋ|ಎ|ಏ|ಐ|ಒ|ಓ|ಔ";
		$syllable = "($swara)($yogavahagalu)|($swara)|($vyanjana)($halanta)($vyanjana)($halanta)($vyanjana)($swara_endings)|($vyanjana)($halanta)($vyanjana)($halanta)($vyanjana)|($vyanjana)($halanta)($vyanjana)($swara_endings)|($vyanjana)($halanta)($vyanjana)|($vyanjana)($swara_endings)|($vyanjana)($halanta)|($vyanjana)";

		$hyphenatedWords = [];

		//~ $syllable = "($swara)|($swara)($yogavahagalu)|($vyanjana)|($vyanjana)($halanta)($vyanjana)($swara_endings)|";
		// $contents = file_get_contents("dictionary1.tex");
		// $wordsList = preg_split("/\n/",$contents);

		// echo "\\sethyphenation{kannada}{\n";

		foreach($wordsList as $word)
		{
			
			//~ echo $wordsList[$i] . "\n";
			$word = preg_replace("/($syllable)/", "$1-", $word);
			$word = preg_replace("/(.*?)-$/", "$1", $word);
			$word = preg_replace("/-ಂ/", "ಂ-", $word);
			$word = preg_replace("/-ಃ/", "ಃ-", $word);
			$word = preg_replace("/^(.*?)-(.*)$/","$1$2",$word);
			$word = preg_replace("/^(.*)-(.*)$/","$1$2",$word);
			$word = str_replace('--','-',$word);
			$word = str_replace('--','-',$word);

			$word = preg_replace("/^-/","",$word);

			$word = str_replace('ಗ-ಳಲ್ಲಿ','ಗಳಲ್ಲಿ',$word);
			$word = str_replace('ಗ-ಳನ್ನೂ','ಗಳನ್ನೂ',$word);
			$word = str_replace('ಗ-ಳನ್ನು','ಗಳನ್ನು',$word);
			$word = str_replace('ಗ-ಳಿಂದ','ಗಳಿಂದ',$word);
			$word = str_replace('ಗ-ಳ-ನ್ನೆಲ್ಲ','ಗಳನ್ನೆಲ್ಲ',$word);
			$word = str_replace('ಲಾ-ಗಿದೆ','ಲಾಗಿದೆ',$word);
			$word = str_replace('ಮಾ-ನವ','ಮಾನವ',$word);
			$word = str_replace('ಸಂ-ನ್ಯಾಸಿ','ಸಂನ್ಯಾಸಿ',$word);
			$word = str_replace('ವಿವೇ-ಕಾ-ನಂದ','ವಿವೇಕಾನಂದ',$word);
			$word = str_replace('ವಿವೇ-ಕಾ-ನಂ-ದ','ವಿವೇಕಾನಂದ',$word);
			$word = str_replace('ವು-ದನ್ನು','ವುದನ್ನು',$word);
			$word = str_replace('ಯಾ-ಗಿ-ದ್ದಾನೆ','ಯಾಗಿದ್ದಾನೆ',$word);
			$word = str_replace('ಯಾ-ಗಿಯೂ','ಯಾಗಿಯೂ',$word);
			$word = str_replace('ಶ್ಶ-ಕ್ತಿ','ಶ್ಶಕ್ತಿ',$word);
			$word = str_replace('ಶ್ಶು-ದ್ಧಿ','ಶ್ಶುದ್ಧಿ',$word);
			$word = str_replace('ಸ್ಸ-ತ್ತ್ವ-','ಸ್ಸತ್ತ್ವ-',$word);
			$word = str_replace('ಸ್ಸ-ತ್ವ-','ಸ್ಸತ್ವ-',$word);
			$word = str_replace('ವ-ರೆಲ್ಲ','ವರೆಲ್ಲ',$word);
			$word = str_replace('ವ-ರೆ-','ವರೆ-',$word);
			$word = str_replace('ಅಂಥ-ವ','ಅಂಥವ',$word);
			$word = str_replace('ವೆಂ-ದರೆ','ವೆಂದರೆ',$word);
			$word = str_replace('ವೇ-ನೆಂ-ದರೆ','ವೇನೆಂದರೆ',$word);
			$word = str_replace('ಕಾ-ರ-ಣ','ಕಾರಣ',$word);
			$word = str_replace('ದ-ಲ್ಲೆಲ್ಲ','ದಲ್ಲೆಲ್ಲ',$word);
			$word = str_replace('ಪ-ಕ್ಕ','ಪಕ್ಕ',$word);
			$word = str_replace('ಕಾ-ಳು','ಕಾಳು',$word);
			$word = str_replace('ಬೇ-ಳೆ','ಬೇಳೆ',$word);
			$word = str_replace('-ಡಾ-ನಂ-ದ','ಡಾನಂದ',$word);
			$word = str_replace('ಅಗ-ತ್ಯ','ಅಗತ್ಯ',$word);
			$word = str_replace('ಪ-ರೀ-ಕ್ಷೆ','ಪರೀಕ್ಷೆ-',$word);
			$word = str_replace('ಅಚ್ಚ-ರಿ','ಅಚ್ಚರಿ',$word);
			$word = str_replace('ಅಜ್ಞಾ-ನ','ಅಜ್ಞಾನ',$word);
			$word = str_replace('ಅಡ-ಗಿ','ಅಡಗಿ',$word);
			$word = str_replace('-ದಾ-ರಿ','-ದಾರಿ',$word);
			$word = str_replace('-ಗಿ-ಳಿ-','-ಗಿಳಿ-',$word);

			if(trim($word) != '')
				array_push($hyphenatedWords, $word);
		}


		return $hyphenatedWords;
	}

	public function printDictionary($id,$words){

		$dictionaryFile = TEXOUT . $id . '/dictionary.tex';

		$string = "\\sethyphenation{kannada}{\n";

		foreach($words as $word){

			$string .= $word . "\n";
		}

		$string .= "}\n";

		if(file_put_contents($dictionaryFile, $string))
			echo "Exceptional hyphenation dictionary created for " . $id . "\n";

	}

	public function getKannadaWordsInFile($xhtmlFile){

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

					$data .= ' ' . $this->parseElement($node);
				}
			}
		}

		$data = preg_replace("/\n/", " ", $data);
		$data = preg_replace('/\s+/', ' ', $data);
		$data = trim($data);
		// echo $data . "\n";

		if($data != '') {

			$words = explode(' ', $data);
			return $words;
		}
		else 
			return ' '; 
	}


	public function parseElement($element){

		// echo $element->nodeName . "\n";
		
		if($element->nodeName != '#text'){
		
			$attributes = $this->getAttributesForElement($element);

			foreach ($attributes as $key => $value) {
				
				// echo "\t --> " . $key . ' -> ' . $value[0] . "\n";
				if(in_array('hide', $value)) return ' ';			
				if(in_array('en', $value)) return ' ';			
			}
		}

		$nodes = $element->childNodes;
		$line = '';

		if (!is_null($nodes)) {

			foreach ($nodes as $node) {

				// echo $node->nodeName . "\n";

				if($node->nodeName != '#text'){

					$line .= ' ' . $this->parseElement($node);				
				}
				else{
						
					$line .= $node->nodeValue;				
					// echo $node->nodeValue . "\t --> " . $node->nodeName . "\n";
				}
			}

			$line = $this->generalReplacements($line);
			// $line .= "\n";
			// echo "\t --> " . $line . "\n";

			return $line;
		}

		return '';
	}

	public function getAttributesForElement($element){

		// echo "\t getAttributesForElement --> " . $element->nodeName . "\n";
		
		if($element->nodeName != '#text')
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

	public function generalReplacements($data){

		$data = preg_replace("/[\t ]+/", " ", $data);		
		$data = preg_replace("/([0-9೦-೯?!;:,.&“”‘’`'\"()\*\–=।॥])/u", "", $data);

		return $data;
	}


}

?>