<?php

/* 

Classes to convert the Title in a unique Title
Author : Vincent Vandegans
Date : 25/11/2025

*/

class titleGenerator
{
 
    private $description;
    private $title;
  
    public $originalTitle;
    public $language;
    public $ModifiedTitle;

    // Méthodes
    // constructor : what needs to run when the class is instanciated
    function __construct(){ } 

    function fullprocess($title = null, $description=null, $country=null, $language=null, $debug = "debug"){

        // prepare description        
        $this->originalTitle = $title;
        $this->title = $title; 
        $this->language = $language; 

        // prepare the stopwords list to reuse everywhere
        //$this->stopWords = self::fileToArray(__DIR__."/ressources/".$this->language."/stopwords.txt");
        //$this->emptyWords = self::fileToArray(__DIR__."/ressources/".$this->language."/emptywords.txt"); 


        self::prepareTitle();

        if($debug=="debug") echo $this->ModifiedTitle; else return $this->ModifiedTitle;
        

    }


    // Convert a file to an Array
    public function fileToArray($filename)
    {

        $linesArray = array();

        $file = file_get_contents($filename);
        $split = explode("\n", $file);

        foreach ($split as $string){
            if($string != "")
                $linesArray[] = $string;
            
        }
        return $linesArray;      
    }
    

    // prepare the title
    public function prepareTitle(){

    
        $this->ModifiedTitle = mb_convert_case($this->title, MB_CASE_LOWER, "UTF-8");
        $this->ModifiedTitle = self::removeBrakets($this->ModifiedTitle);
        $this->ModifiedTitle = self::keepAlphaChars($this->ModifiedTitle);

        //$this->ModifiedTitle = self::emptyWords($this->ModifiedTitle);
        //$this->ModifiedTitle = self::stopWords($this->ModifiedTitle);

        $titleWords = explode(" ", $this->ModifiedTitle);
        $titleWords = self::removeSmallWords($titleWords);
        $this->ModifiedTitle = implode(" ", $titleWords);

        $this->ModifiedTitle = self::maxSize($this->ModifiedTitle, 3, 5);        
        $this->ModifiedTitle = self::prettyprint($this->ModifiedTitle);
        $pieceOfTitleToAdd = self::randomWords($this->title, $this->ModifiedTitle, $nbrofWords = 2);
        $this->ModifiedTitle = $this->ModifiedTitle. " - ".$pieceOfTitleToAdd;
        $this->ModifiedTitle = self::unique_words($this->ModifiedTitle);

    }



    // Clean and fix some stuff before the final print out.
    public function prettyprint($text){

        // replace all FULL uppercase Words
        $text = preg_replace_callback(
        '/(\b[A-Z][A-Z]+\b)/',
        function ($matches){
            return ucfirst(strtolower($matches[0]));
        },$text); 

        // upercase the first letter of the sentence.
        $text = ucfirst($text);

        return $text;

    } 

  


    // remove the stopWords from the content
    public function stopWords($text){
        // remove stopwords
        foreach($this->stopWords as $word){
            
            $word = trim($word);
            $text = preg_replace("#\b".$word."\b#u", " ", $text);
          
        }      
        return $text;

    }


    // remove the stopWords from the content
    public function emptyWords($text){

        // remove empty words
        foreach($this->emptyWords as $word){
            
            $word = trim($word);
            $text = preg_replace("#\b".$word."\b#u", " ", $text);

        }        

        return $text;

    }


    // remove the figures and special chars. keep accents
    public function keepAlphaChars($text){

        // replace html entities by the real caracters
        $text = html_entity_decode($text);

        $text = preg_replace('#[^\p{L}\d ]#u', ' ', $text);

        return $text;
    }



    // Prendre 2 ngrams aléatoirement dans la description
    public function randomWords($description, $title, $nbrofWords){

        $description = str_replace($title, "", $description);
        $words = explode(" ", $description);
       
        $randWords = array_rand($words, $nbrofWords);
        $text = ""; $i=0;

        foreach($randWords as $randID){

            // add a comma between the extracted words
            if($i==0) $ending = ", ";else $ending = " ";
            $text .= strtolower($words[$randID]).$ending;

            $i++;
        }

        return trim($text);

    }



    // remove data in brakets
    public function removeBrakets($text){
        return preg_replace("/\([^)]+\)/","",$text); 
    }   


    // remove words with less than 3 chars
    public function removeSmallWords($wordsArray){

        for($i=0;$i<count($wordsArray);$i++){
            if(strlen($wordsArray[$i])< 3){
                unset($wordsArray[$i]);
                
            }
        }

        return $wordsArray;

    } 


    public function maxsize(string $title, int $minWords = 3, int $maxWords = 5): string
    {
        // Sécurise les bornes
        if ($minWords <= 0 || $maxWords < $minWords) {
            return $title;
        }

        // Trim + split ultra simple sur les espaces (rapide)
        $title = trim($title);
        if ($title === '') {
            return '';
        }

        $words = preg_split('/\s+/', $title);
        $wordCount = count($words);

        // Choisit un nombre aléatoire de mots entre min et max
        $limit = mt_rand($minWords, $maxWords);

        // Si le titre a moins de mots que le limite, on ne coupe pas
        if ($wordCount <= $limit) {
            return $title;
        }

        // Garde uniquement les N premiers mots
        $cut = array_slice($words, 0, $limit);

        return implode(' ', $cut);
    }

    function unique_words(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        // Split rapide sur les espaces multiples
        $words = preg_split('/\s+/', $text);

        $seen   = [];
        $result = [];

        foreach ($words as $word) {
            // On compare en minuscule pour les doublons,
            // mais on garde la forme originale du premier mot.
            $key = mb_strtolower($word, 'UTF-8');

            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $result[]   = $word;
            }
        }

        return implode(' ', $result);
    }

}
?>