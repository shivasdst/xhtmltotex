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

		//~ $subWordspatterns = $this->subWordPatterns($words);		

		$hyphenatedWords = $this->splitAtAllSyllables($words);
		$hyphenatedWords = $this->removeExtreemHyphens($hyphenatedWords);
		$hyphenatedWords = $this->checkLength($hyphenatedWords);
		$hyphenatedWords = $this->removeHyphenBeforevottu($hyphenatedWords);
		$hyphenatedWords = $this->wordEndings($hyphenatedWords);

		//~ $this->dumpJunk($words);

		$this->printDictionary($id,$hyphenatedWords);

	}

	public function subWordPatterns($words){
		
		$subPatterns = array_slice($words, 0, 500);

		foreach ($subPatterns as $subword) {
			
			if(mb_strlen($subword, 'UTF-8') > 2)
				$words = preg_replace('/'. $subword .'/', '-' . $subword . '-', $words);

		}
		file_put_contents('/home/sriranga/Desktop/test.txt', implode("\n", $words));
		// var_dump($words);
	}

	public function dumpJunk($wordsList){

		foreach($wordsList as $word){

			$word = str_replace('-', '', $word);

			$characters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY);
	
			// echo $word . "\n";

			foreach ($characters as $character) {
				
				if(!((ord($character) >= ord('ಁ')) && (ord($character) <= ord('ೲ'))))
					echo "$word\t -> " . $character . "\n";
			}

		}


	}

	public function splitAtAllSyllables($wordsList){

		// var_dump($wordsList);

		$vyanjana = "ಕ|ಖ|ಗ|ಘ|ಙ|ಚ|ಛ|ಜ|ಝ|ಞ|ಟ|ಠ|ಡ|ಢ|ಣ|ತ|ಥ|ದ|ಧ|ನ|ಪ|ಫ|ಬ|ಭ|ಮ|ಯ|ರ|ಱ|ಲ|ವ|ಶ|ಷ|ಸ|ಹ|ಳ|ೞ";
		$swara_endings = "ಾ|ಿ|ೀ|ು|ೂ|ೃ|ೄ|ೆ|ೇ|ೈ|ೊ|ೋ|ೌ|ೕ|ೖ|ಂ|ಃ";
		$halanta = "್";
		$yogavahagalu = "ಂ|ಃ";
		$swara = "ಅ|ಆ|ಇ|ಈ|ಉ|ಊ|ಋ|ಎ|ಏ|ಐ|ಒ|ಓ|ಔ";
		$syllable = "($swara)($yogavahagalu)|($swara)|($vyanjana)(($halanta)($vyanjana))*($swara_endings)?($yogavahagalu)?";

		$hyphenatedWords = [];

		foreach($wordsList as $word)
		{
			$wordCopy = $word;	
			//~ echo $wordsList[$i] . "\n";
			$word = preg_replace("/($syllable)/", "$1-", $word);
			$word = preg_replace("/-ಂ/", "ಂ-", $word);
			$word = preg_replace("/-ಃ/", "ಃ-", $word);
			$word = str_replace('--','-',$word);
			$word = preg_replace("/-್/","್",$word);
			$word = preg_replace("/(.*?)-$/", "$1", $word);
			$word = preg_replace("/^-/","",$word);

			if(trim($word) != '')
				array_push($hyphenatedWords, $word);
				//~ array_push($hyphenatedWords, $wordCopy . "->" . $word);
		}

		return $hyphenatedWords;
	}


	public function removeExtreemHyphens($wordsList){
		
		$hyphenatedWords = [];
		
		foreach($wordsList as $word)
		{
			
			$wordCopy = $word;	

			$word = preg_replace("/^(.*?)-(.*)$/","$1$2",$word);
			$word = preg_replace("/^(.*?)-(.*)$/","$1$2",$word);
			$word = preg_replace("/^(.*)-(.*?)$/","$1$2",$word);

			if(trim($word) != '')
				array_push($hyphenatedWords, $word);

				//~ array_push($hyphenatedWords, $wordCopy . "->" . $word);

		}	
	
		return $hyphenatedWords;	
	}

	public function checkLength($wordsList){
		
		$hyphenatedWords = [];
		
		foreach($wordsList as $word)
		{	
			$wordCopy = $word;	

			if(sizeof(preg_split('/\-/',$word)) == 2)
				$word = str_replace('-','',$word);

			if(trim($word) != '')
				array_push($hyphenatedWords, $word);
				//~ array_push($hyphenatedWords, $wordCopy . "->" . $word);
		}	
	
		return $hyphenatedWords;	
	}


	public function removeHyphenBeforevottu($wordsList){

		$hyphenatedWords = [];
		$vyanjana = "ಕ|ಖ|ಗ|ಘ|ಙ|ಚ|ಛ|ಜ|ಝ|ಞ|ಟ|ಠ|ಡ|ಢ|ಣ|ತ|ಥ|ದ|ಧ|ನ|ಪ|ಫ|ಬ|ಭ|ಮ|ಯ|ರ|ಱ|ಲ|ವ|ಶ|ಷ|ಸ|ಹ|ಳ|ೞ";
		$halanta = "್";
		$yogavahagalu = "ಂ|ಃ";

		
		foreach($wordsList as $word)
		{	
			$wordCopy = $word;	

			$word = preg_replace("/-(($vyanjana)($halanta)($vyanjana))/","$1",$word);
			$word = preg_replace("/($yogavahagalu)-/","$1",$word);

			if(trim($word) != '')
				array_push($hyphenatedWords, $word);

				//~ array_push($hyphenatedWords, $wordCopy . "->" . $word);

		}	
	
		return $hyphenatedWords;
	}

	public function wordEndings($wordsList){

		$hyphenatedWords = [];

		foreach($wordsList as $word)
		{	
			$wordCopy = $word;	
			
			if(preg_match('/\-/',$word))	
				$word = $this->replaceWordEndings($word);
			
			if(trim($word) != '')
				array_push($hyphenatedWords, $word);

				//~ array_push($hyphenatedWords, $wordCopy . "->" . $word);


		}	
	
		return $hyphenatedWords;
	}

	public function replaceWordEndings($wordCopy){

		$word = str_replace('-','',$wordCopy);

		$word = preg_replace('/ಕೊಂಡ$/','-ಕೊಂಡ',$word);
		$word = preg_replace('/ಕೊಂಡು$/','-ಕೊಂಡು',$word);
		$word = preg_replace('/ಕೊಳ್ಳು$/','-ಕೊಳ್ಳು',$word);
		$word = preg_replace('/ಕೊಳ್ಳಬಲ್ಲ$/','-ಕೊಳ್ಳ-ಬಲ್ಲ',$word);
		$word = preg_replace('/ಕೊಳ್ಳಬಹುದು$/','-ಕೊಳ್ಳ-ಬಹುದು',$word);
		$word = preg_replace('/ಕೆಗೆ$/','-ಕೆಗೆ',$word);
					
		$word = preg_replace('/ಗಲೇ$/','-ಗಲೇ',$word);
		$word = preg_replace('/ಗಳ$/','-ಗಳ',$word);
		$word = preg_replace('/ಗಳಾಗಲಿ$/','-ಗಳಾಗಲಿ',$word);
		$word = preg_replace('/ಗಳಾಗಿ$/','-ಗಳಾಗಿ',$word);
		$word = preg_replace('/ಗಳಲ್ಲ$/','-ಗಳಲ್ಲ',$word);
		$word = preg_replace('/ಗಳಲ್ಲಿ$/','-ಗಳಲ್ಲಿ',$word);
		$word = preg_replace('/ಗಳನ್ನೂ$/','-ಗಳನ್ನೂ',$word);
		$word = preg_replace('/ಗಳನ್ನು$/','-ಗಳನ್ನು',$word);
		$word = preg_replace('/ಗಳಿಗೂ$/','-ಗಳಿಗೂ',$word);
		$word = preg_replace('/ಗಳಿಂದ$/','-ಗಳಿಂದ',$word);
		$word = preg_replace('/ಗಳಿಂದಲೇ$/','-ಗಳಿಂದಲೇ',$word);
		$word = preg_replace('/ಗಳನ್ನೆಲ್ಲ$/','-ಗಳನ್ನೆಲ್ಲ',$word);
		$word = preg_replace('/ಗಳಿಸಿ$/','-ಗಳಿಸಿ',$word);
		$word = preg_replace('/ಗಳಿವೆ$/','-ಗಳಿವೆ',$word);
		$word = preg_replace('/ಗಳನ್ನೇ$/','-ಗಳನ್ನೇ',$word);
		$word = preg_replace('/ಗಳಾಗಿ$/','-ಗಳಾಗಿ',$word);
		$word = preg_replace('/ಗಳಿಗೆ$/','-ಗಳಿಗೆ',$word);
		$word = preg_replace('/ಗಳು$/','-ಗಳು',$word);
		$word = preg_replace('/ಗಳೂ$/','-ಗಳೂ',$word);
		$word = preg_replace('/ಗಳೆದ$/','-ಗಳೆದ',$word);
		$word = preg_replace('/ಗಳೆದು$/','-ಗಳೆದು',$word);
		$word = preg_replace('/ಗಾಗಿ$/','-ಗಾಗಿ',$word);
		$word = preg_replace('/ಗಾದ$/','-ಗಾದ',$word);
		$word = preg_replace('/ಗಿರಲಿ$/','-ಗಿರಲಿ',$word);
		$word = preg_replace('/ಗಳಿಗಿಂತಲೂ$/','-ಗಳಿ-ಗಿಂತಲೂ',$word);
		$word = preg_replace('/ಗಿಂತಲೂ$/','-ಗಿಂತಲೂ',$word);
		$word = preg_replace('/ಗಳಲ್ಲಿನ$/','-ಗಳ-ಲ್ಲಿನ',$word);
		$word = preg_replace('/ಗಳಲ್ಲಿರುವ$/','-ಗಳ-ಲ್ಲಿರುವ',$word);
		$word = preg_replace('/ಗಳೆಲ್ಲ$/','-ಗಳೆಲ್ಲ',$word);
		$word = preg_replace('/ಗಳೆಲ್ಲಾ$/','-ಗಳೆಲ್ಲಾ',$word);
		$word = preg_replace('/ಗಳೇ$/','-ಗಳೇ',$word);
		$word = preg_replace('/ಗೊಳಗಾದ$/','-ಗೊಳ-ಗಾದ',$word);
		$word = preg_replace('/ಗಿತು$/','-ಗಿತು',$word);
		$word = preg_replace('/ಗಿದ್ದ$/','-ಗಿದ್ದ',$word);
		$word = preg_replace('/ಗಿದೆ$/','-ಗಿದೆ',$word);
		$word = preg_replace('/ಗಿತ್ತು$/','-ಗಿತ್ತು',$word);
		$word = preg_replace('/ಗುತ್ತ$/','-ಗುತ್ತ',$word);
		$word = preg_replace('/ಗುವ$/','-ಗುವ',$word);
		$word = preg_replace('/ಗಿಂತ$/','-ಗಿಂತ',$word);
		$word = preg_replace('/ಗಳಿಂದಲೇ$/','-ಗಳಿಂದಲೇ',$word);
		$word = preg_replace('/ಗೊಂದು$/','-ಗೊಂದು',$word);

		$word = preg_replace('/ಟಾಗುವ$/','-ಟಾಗುವ',$word);

		$word = preg_replace('/ತಲ್ಲವೇ$/','-ತಲ್ಲವೇ',$word);
		$word = preg_replace('/ತಿಲ್ಲ$/','-ತಿಲ್ಲ',$word);
		$word = preg_replace('/ತೆಂಬ$/','-ತೆಂಬ',$word);
		
		$word = preg_replace('/ದಾಗ$/','-ದಾಗ',$word);
		$word = preg_replace('/ದಾಗಲೇ$/','-ದಾಗಲೇ',$word);
		$word = preg_replace('/ದಾಗಲೂ$/','-ದಾಗಲೂ',$word);
		$word = preg_replace('/ದರು$/','-ದರು',$word);
		$word = preg_replace('/ದಕ್ಕೆ$/','-ದಕ್ಕೆ',$word);
		$word = preg_replace('/ದಳು$/','-ದಳು',$word);
		$word = preg_replace('/ದಲ್ಲಿ$/','-ದಲ್ಲಿ',$word);
		$word = preg_replace('/ದಲ್ಲೇ$/','-ದಲ್ಲೇ',$word);
		$word = preg_replace('/ದಲ್ಲೆ$/','-ದಲ್ಲೆ',$word);
		$word = preg_replace('/ದಲ್ಲಿರುವ$/','-ದಲ್ಲಿರುವ',$word);
		$word = preg_replace('/ದಿಂದ$/','-ದಿಂದ',$word);
		$word = preg_replace('/ದಿಂದಲೆ$/','-ದಿಂದಲೆ',$word);
		$word = preg_replace('/ದಲ್ಲವೆ$/','-ದಲ್ಲವೆ',$word);
		$word = preg_replace('/ದಿಲ್ಲವೆ$/','-ದಿಲ್ಲವೆ',$word);
		$word = preg_replace('/ದಿಲ್ಲವೇ\?$/','-ದಿಲ್ಲವೇ\?',$word);
		$word = preg_replace('/ದಿದ್ದರೆ$/','-ದಿದ್ದರೆ',$word);
		$word = preg_replace('/ದಲ್ಲೆಲ್ಲ$/','-ದಲ್ಲೆಲ್ಲ',$word);
		$word = preg_replace('/ದಂತೆ$/','-ದಂತೆ',$word);
		$word = preg_replace('/ದಾಗ$/','-ದಾಗ',$word);
		$word = preg_replace('/ದೆಂತು$/','-ದೆಂತು',$word);
		$word = preg_replace('/ದಷ್ಟೆ$/','-ದಷ್ಟೆ',$word);
		$word = preg_replace('/ದುಂಟು$/','-ದುಂಟು',$word);
		$word = preg_replace('/ದೆಂದರೆ$/','-ದೆಂದರೆ',$word);
		$word = preg_replace('/ದಂ-ತ್ತಿಲ್ಲ$/','-ದಂತ್ತಿಲ್ಲ',$word);
		$word = preg_replace('/ದಂತ್ತಿಲ್ಲ$/','-ದಂತ್ತಿಲ್ಲ',$word);
		$word = preg_replace('/ದಂತಿಲ್ಲ$/','-ದಂತಿಲ್ಲ',$word);
		$word = preg_replace('/ದಂ-ತಿಲ್ಲ$/','-ದಂತಿಲ್ಲ',$word);
		$word = preg_replace('/ದಂತೆ$/','-ದಂತೆ',$word);
		$word = preg_replace('/ದನೇ$/','-ದನೇ',$word);
		$word = preg_replace('/ದನ್ನು$/','-ದನ್ನು',$word);
		$word = preg_replace('/ದನ್ನೆ$/','-ದನ್ನೆ',$word);
		$word = preg_replace('/ದರಿಂದ$/','-ದರಿಂದ',$word);
		$word = preg_replace('/ದರು$/','-ದರು',$word);
		$word = preg_replace('/ದರೂ$/','-ದರೂ',$word);
		$word = preg_replace('/ದರೆ$/','-ದರೆ',$word);
		$word = preg_replace('/ದಲೇ$/','-ದಲೇ',$word);
		$word = preg_replace('/ದಲ್ಲವೆ$/','-ದಲ್ಲವೆ',$word);
		$word = preg_replace('/ದವ$/','-ದವ',$word);
		$word = preg_replace('/ದಿದ್ದ$/','-ದಿದ್ದ',$word);
		$word = preg_replace('/ದಿದ್ದರೆ$/','-ದಿದ್ದರೆ',$word);
		$word = preg_replace('/ದಿರ$/','-ದಿರ',$word);
		$word = preg_replace('/ದುಂಟು$/','-ದುಂಟು',$word);
		$word = preg_replace('/ದೆಂತು$/','-ದೆಂತು',$word);
		$word = preg_replace('/ದೆಂದು$/','-ದೆಂದು',$word);
		
		$word = preg_replace('/ನವರು$/','-ನವರು',$word);
		$word = preg_replace('/ನಲ್ಲೂ$/','-ನಲ್ಲೂ',$word);
		$word = preg_replace('/ನಾಗಿಬಿಡುತ್ತಾನೆ$/','-ನಾಗಿ-ಬಿಡು-ತ್ತಾನೆ',$word);
		$word = preg_replace('/ನಾದ$/','-ನಾದ',$word);
		$word = preg_replace('/ನಾಗ$/','-ನಾಗ',$word);
		$word = preg_replace('/ನಾಗಿ$/','-ನಾಗಿ',$word);
		$word = preg_replace('/ನಾಗಿಯೇ$/','-ನಾಗಿಯೇ',$word);
		$word = preg_replace('/ನಾಗಿಬಿಡುತ್ತಾನೆ$/','-ನಾಗಿ-ಬಿಡುತ್ತಾನೆ',$word);
		$word = preg_replace('/ನಲ್ಲಿ$/','-ನಲ್ಲಿ',$word);
		$word = preg_replace('/ನಲ್ಲಿರುವ$/','-ನಲ್ಲಿರುವ',$word);
		$word = preg_replace('/ನಿಂದ$/','-ನಿಂದ',$word);
		$word = preg_replace('/ನಿಗೆ$/','-ನಿಗೆ',$word);
		$word = preg_replace('/ನಿದ್ದ$/','-ನಿದ್ದ',$word);
		$word = preg_replace('/ನನ್ನು$/','-ನನ್ನು',$word);
		$word = preg_replace('/ನೊಂದಿ$/','-ನೊಂದಿ',$word);
		$word = preg_replace('/ನೊಬ್ಬ$/','-ನೊಬ್ಬ',$word);
		
		$word = preg_replace('/ಬಹುದು$/','-ಬಹುದು',$word);
		$word = preg_replace('/ಬೇಕು$/','-ಬೇಕು',$word);
		
		$word = preg_replace('/ಯಲ್ಲ$/','-ಯಲ್ಲ',$word);
		$word = preg_replace('/ಯಲ್ಲವೆ$/','-ಯಲ್ಲವೆ',$word);
		$word = preg_replace('/ಯಲ್ಲವೇ$/','-ಯಲ್ಲವೇ',$word);
		$word = preg_replace('/ಯಲ್ಲಿನ$/','-ಯಲ್ಲಿನ',$word);
		$word = preg_replace('/ಯಾಗಿ$/','-ಯಾಗಿ',$word);
		$word = preg_replace('/ಯಾಗಿದ್ದಾನೆ$/','-ಯಾಗಿದ್ದಾನೆ',$word);
		$word = preg_replace('/ಯಾಗಿಯೂ$/','-ಯಾಗಿಯೂ',$word);			
		$word = preg_replace('/ಯಾಗು$/','-ಯಾಗು',$word);			
		$word = preg_replace('/ಯಾಗುವಂತೆ$/','-ಯಾಗುವಂತೆ',$word);			
		$word = preg_replace('/ಯನ್ನು$/','-ಯನ್ನು',$word);
		$word = preg_replace('/ಯಲ್ಲಿ$/','-ಯಲ್ಲಿ',$word);
		$word = preg_replace('/ಯಲ್ಲಿದೆ$/','-ಯಲ್ಲಿದೆ',$word);
		$word = preg_replace('/ಯಲ್ಲಿದ್ದ$/','-ಯಲ್ಲಿದ್ದ',$word);
		$word = preg_replace('/ಯಾತ$/','-ಯಾತ',$word);
		$word = preg_replace('/ಯಾದ$/','-ಯಾದ',$word);
		$word = preg_replace('/ಯಾದರೂ$/','-ಯಾದರೂ',$word);
		$word = preg_replace('/ಯಾಗಿಯೇ$/','-ಯಾಗಿಯೇ',$word);
		$word = preg_replace('/ಯಿಂದ$/','-ಯಿಂದ',$word);
		$word = preg_replace('/ಯಾಯಿತು$/','-ಯಾಯಿತು',$word);
		$word = preg_replace('/ಯಾಗದೆ$/','-ಯಾಗದೆ',$word);
		$word = preg_replace('/ಯಾಗಲಿ$/','-ಯಾಗಲಿ',$word);
		$word = preg_replace('/ಯಾತ$/','-ಯಾತ',$word);
		$word = preg_replace('/ಯಾಗಲೇ$/','-ಯಾಗಲೇ',$word);
		$word = preg_replace('/ಯಲ್ಲಿದೆ$/','-ಯಲ್ಲಿದೆ',$word);
		$word = preg_replace('/ಯಿತು$/','-ಯಿತು',$word);
		$word = preg_replace('/ಯನ$/','-ಯನ',$word);
		$word = preg_replace('/ಯನ್ನೂ$/','-ಯನ್ನೂ',$word);
		$word = preg_replace('/ಯಂತೆ$/','-ಯಂತೆ',$word);
		$word = preg_replace('/ಯಂತಿತ್ತು$/','-ಯಂತಿತ್ತು',$word);
		$word = preg_replace('/ಯೊಂದು$/','-ಯೊಂದು',$word);

		$word = preg_replace('/ರಂತ$/','-ರಂತ',$word);
		$word = preg_replace('/ರಾದ$/','-ರಾದ',$word);
		$word = preg_replace('/ರನ್ನಾಗಿ$/','-ರನ್ನಾಗಿ',$word);
		$word = preg_replace('/ರನ್ನೂ$/','-ರನ್ನೂ',$word);
		$word = preg_replace('/ರಾಗಲಿ$/','-ರಾಗಲಿ',$word);
		$word = preg_replace('/ರಾಗಲೀ$/','-ರಾಗಲೀ',$word);
		$word = preg_replace('/ರಾಗಿ$/','-ರಾಗಿ',$word);
		$word = preg_replace('/ರಾಗಲು$/','-ರಾಗಲು',$word);
		$word = preg_replace('/ರಾಗು$/','-ರಾಗು',$word);
		$word = preg_replace('/ರಿಂದ$/','-ರಿಂದ',$word);
		$word = preg_replace('/ರಿಗೂ$/','-ರಿಗೂ',$word);
		$word = preg_replace('/ರಿಗೆ$/','-ರಿಗೆ',$word);
		$word = preg_replace('/ರಲಿ$/','-ರಲಿ',$word);
		$word = preg_replace('/ರಲ್ಲ$/','-ರಲ್ಲ',$word);
		$word = preg_replace('/ರಲ್ಲಿ$/','-ರಲ್ಲಿ',$word);
		$word = preg_replace('/ರುವುದೆಲ್ಲ$/','-ರು-ವುದೆಲ್ಲ',$word);
		$word = preg_replace('/ರಾಗುವಿರಿ$/','-ರಾಗು-ವಿರಿ',$word);
		$word = preg_replace('/ರಾದರು$/','-ರಾದರು',$word);
		$word = preg_replace('/ರಾದರೂ$/','-ರಾದರೂ',$word);
		$word = preg_replace('/ರಾದುದು$/','-ರಾದುದು',$word);
		$word = preg_replace('/ರಿರಲಿ$/','-ರಿರಲಿ',$word);
		$word = preg_replace('/ರಾಗಬೇಡಿ$/','-ರಾಗ-ಬೇಡಿ',$word);
		$word = preg_replace('/ರೊಂದಿಗೆ$/','-ರೊಂದಿಗೆ',$word);
		$word = preg_replace('/ರುವ$/','-ರುವ',$word);
		$word = preg_replace('/ರೆಂದು$/','-ರೆಂದು',$word);

		$word = preg_replace('/ಲಾರ$/','-ಲಾರ',$word);
		$word = preg_replace('/ಲಾರರು$/','-ಲಾರರು',$word);
		$word = preg_replace('/ಲಾರದ$/','-ಲಾರದ',$word);
		$word = preg_replace('/ಲಾರದು$/','-ಲಾರದು',$word);
		$word = preg_replace('/ಲಾಗಿದೆ$/','-ಲಾಗಿದೆ',$word);
		$word = preg_replace('/ಲಾಗ$/','-ಲಾಗ',$word);
		$word = preg_replace('/ಲಾಗು$/','-ಲಾಗು',$word);
		$word = preg_replace('/ಲಾಗದು$/','-ಲಾಗದು',$word);
		$word = preg_replace('/ಲಾದ$/','-ಲಾದ',$word);
		$word = preg_replace('/ಲಿದೆ$/','-ಲಿದೆ',$word);
		$word = preg_replace('/ಲಿಲ್ಲ$/','-ಲಿಲ್ಲ',$word);

		$word = preg_replace('/ವವ$/','-ವವ',$word);
		$word = preg_replace('/ವವನಲ್ಲೂ$/','-ವವನಲ್ಲೂ',$word);
		$word = preg_replace('/ವವರ$/','-ವವರ',$word);
		$word = preg_replace('/ವವರನ್ನು$/','-ವವರನ್ನು',$word);
		$word = preg_replace('/ವವರಂತೆ$/','-ವವ-ರಂತೆ',$word);
		$word = preg_replace('/ವವನೇ$/','-ವವನೇ',$word);
		$word = preg_replace('/ವುದಕ್ಕೆ$/','-ವುದಕ್ಕೆ',$word);
		$word = preg_replace('/ವುದ$/','-ವುದ',$word);
		$word = preg_replace('/ವುದನ್ನು$/','-ವುದನ್ನು',$word);
		$word = preg_replace('/ವುದು$/','-ವುದು',$word);
		$word = preg_replace('/ವರೆಲ್ಲ$/','-ವರೆಲ್ಲ',$word);
		$word = preg_replace('/ವರೊ$/','-ವರೊ',$word);
		$word = preg_replace('/ವರು$/','-ವರು',$word);
		$word = preg_replace('/ವೆಂದರೆ$/','-ವೆಂದರೆ',$word);
		$word = preg_replace('/ವೇನೆಂದರೆ$/','-ವೇನೆಂದರೆ',$word);
		$word = preg_replace('/ವನ$/','-ವನ',$word);
		$word = preg_replace('/ವನು$/','-ವನು',$word);
		$word = preg_replace('/ವನ್ನು$/','-ವನ್ನು',$word);
		$word = preg_replace('/ವನ್ನೂ$/','-ವನ್ನೂ',$word);
		$word = preg_replace('/ವನ್ನೇ$/','-ವನ್ನೇ',$word);
		$word = preg_replace('/ವಾಗಿ$/','-ವಾಗಿ',$word);
		$word = preg_replace('/ವಾಗಿತ್ತು$/','-ವಾಗಿತ್ತು',$word);
		$word = preg_replace('/ವಲ್ಲ$/','-ವಲ್ಲ',$word);
		$word = preg_replace('/ವಲ್ಲವೇ$/','-ವಲ್ಲವೇ',$word);
		$word = preg_replace('/ವುವು$/','-ವುವು',$word);
		$word = preg_replace('/ವುದೆ$/','-ವುದೆ',$word);
		$word = preg_replace('/ವುದಿಲ್ಲ$/','-ವುದಿಲ್ಲ',$word);
		$word = preg_replace('/ವಂತ$/','-ವಂತ',$word);
		$word = preg_replace('/ವಂತೆ$/','-ವಂತೆ',$word);
		$word = preg_replace('/ವಾದ$/','-ವಾದ',$word);
		$word = preg_replace('/ವಾದರೆ$/','-ವಾದರೆ',$word);
		$word = preg_replace('/ವಾಗಿವೆ$/','-ವಾಗಿವೆ',$word);
		$word = preg_replace('/ವಾದುದನ್ನು$/','-ವಾದು-ದನ್ನು',$word);
		$word = preg_replace('/ವಾದುದು$/','-ವಾದುದು',$word);
		$word = preg_replace('/ವಾದರೂ$/','-ವಾದರೂ',$word);
		$word = preg_replace('/ವಾದುವು$/','-ವಾದುವು',$word);
		$word = preg_replace('/ವೊಂದೇ$/','-ವೊಂದೇ',$word);
		$word = preg_replace('/ವಾಗಬೇಕು$/','-ವಾಗ-ಬೇಕು',$word);
		$word = preg_replace('/ವಾಗಿದೆ$/','-ವಾಗಿದೆ',$word);
		$word = preg_replace('/ವಾಗ$/','-ವಾಗ',$word);
		$word = preg_replace('/ವಿದೆ$/','-ವಿದೆ',$word);
		$word = preg_replace('/ವಾಗಿ$/','-ವಾಗಿ',$word);
		$word = preg_replace('/ವಾಗು-/','-ವಾಗು-',$word);
		$word = preg_replace('/ವಾಗು-ವ$/','-ವಾಗುವ',$word);
		$word = preg_replace('/ವೆಂಬ$/','-ವೆಂಬ',$word);
		$word = preg_replace('/ವೆಂದು$/','-ವೆಂದು',$word);
		$word = preg_replace('/ವೆವೊ$/','-ವೆವೊ',$word);
		$word = preg_replace('/ವೆನ್ನುವ$/','-ವೆನ್ನುವ',$word);
		$word = preg_replace('/ವಂತಿಲ್ಲ$/','-ವಂತಿಲ್ಲ',$word);
		$word = preg_replace('/ವಾಗಿನ$/','-ವಾಗಿನ',$word);
		$word = preg_replace('/ವಾಗಿಯೂ$/','-ವಾಗಿಯೂ',$word);
		$word = preg_replace('/ವಾಗಿಯೇ$/','-ವಾಗಿಯೇ',$word);
		$word = preg_replace('/ವರೆಗೂ$/','-ವರೆಗೂ',$word);
		$word = preg_replace('/ವರೆಗೆ$/','-ವರೆಗೆ',$word);
		$word = preg_replace('/ವಿತ್ತರೂ$/','-ವಿತ್ತರೂ',$word);
		$word = preg_replace('/ವಿತ್ತು$/','-ವಿತ್ತು',$word);
		$word = preg_replace('/ವಿಲ್ಲ$/','-ವಿಲ್ಲ',$word);
		$word = preg_replace('/ವಿನ$/','-ವಿನ',$word);
		$word = preg_replace('/ವಿದ್ದರೂ$/','-ವಿದ್ದರೂ',$word);
		$word = preg_replace('/ವಿರಬೇಕಾದುದು/','-ವಿರ-ಬೇಕಾ-ದುದು',$word);
		$word = preg_replace('/ವಿಲ್ಲದಿದ್ದರೂ/','-ವಿಲ್ಲ-ದಿದ್ದರೂ',$word);
		$word = preg_replace('/ವಾ-ಯಿತು$/','-ವಾಯಿತು',$word);
		$word = preg_replace('/ವಾಗಿರುವ$/','-ವಾಗಿರುವ',$word);
		$word = preg_replace('/ವಾಗುವಂಥ$/','-ವಾಗು-ವಂಥ',$word);		
		
		$word = preg_replace('/ಸಿದ$/','-ಸಿದ',$word);
		$word = preg_replace('/ಸಿದೆ$/','-ಸಿದೆ',$word);
		$word = preg_replace('/ಸಿಯೇ$/','-ಸಿಯೇ',$word);
		$word = preg_replace('/ಸಲೇ$/','-ಸಲೇ',$word);
		$word = preg_replace('/ಸುತ್ತ$/','-ಸುತ್ತ',$word);
		$word = preg_replace('/ಸುವ$/','-ಸುವ',$word);
		
		$word = preg_replace('/ಳಂತೆ$/','-ಳಂತೆ',$word);
		
		$word = str_replace('--','-',$word);

		if(preg_match('/\-/',$word)){
			
			if(preg_match('/್\-/',$word))
				return $wordCopy;
				
			return $word;
		}
		else
			return $wordCopy;	
	}

	public function printDictionary($id,$words){

		$dictionaryFile = TEXOUT . $id . '/dictionary_tmp.tex';

		$string = "\\sethyphenation{kannada}{\n";

		foreach($words as $word){
			
			//~ $word = $this->generalReplacements($word); 
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
		$data = preg_replace("/\\\\-/", '', $data);
		$data = preg_replace("/\\\\- /", '', $data);
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
				if($key == 'data-tex') return ' ';			
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
		$data = preg_replace("/([0-9೦-೯[:punct:]“”‘’–—।॥­​a-zA-Z‍])/u", "", $data);

		return $data;
	}


}

?>
