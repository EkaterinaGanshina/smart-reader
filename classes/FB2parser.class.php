<?php
define("PAGE_LEN", 7000); // approximate symbols count for one page (including XML tags)

class FB2parser {
    // private class field
    private $bookId;

    // DB driver
    private $pdo;

    public function __construct($pdo, $id) {
        $this->pdo = $pdo;
        $this->bookId = $id;
    }

    /**
     * @param string $pathToFile Path to the fb2 file on server
     * @return bool|string True, if the book was parsed and saved successfully
     */
    public function parse($pathToFile){
        $bookText = '';
        $reader = new XMLReader();
        $reader->open($pathToFile); // path to file to parse
        
        // book parser
        while ($reader->read()) {
        	// find the first <section> document
        	if(($reader->nodeType == XMLReader::ELEMENT) and ($reader->localName == 'section')){
        		$content = $reader->readInnerXML();

        		// remove xmlns attributes, links, images
        		$content = preg_replace( array(
                    '#\sxmlns=[\'\"].*?[\'\"]#', 
                    '#\sxmlns:l=[\'\"].*?[\'\"]#', 
                    '#<a\s.*?>#', '#<\/a>#',
                    '#<image\s.*?>#'
                ), "", $content);

        		// edit XML tags
        		$content = str_replace( array(
                    '<empty-line', 'emphasis>', '<v>', '</v>', 'poem>',
                    '<stanza>', '</stanza>', '<title>', '</title>', '<section>', '</section' 
                ), array( 
                    '<br', 'em>', '<p>', '</p>', 'cite>',
                    '', '', '<h3 class="chapter-title">', '</h3>', '', '' 
                ), $content );

        		// save the result
        		$bookText .= $content;
        	}
        }                
        
        // prepare SQL query to add pages
        $stmt = $this->pdo->prepare('INSERT into `pages` 
                (`bookId`, `pageNumber`, `content`) VALUES 
                (:book, :page, :content)' );
        $stmt->bindParam(':book', $this->bookId, PDO::PARAM_INT);

        // cut the book text into pages
        $page = 1;
        while (mb_strlen($bookText) > PAGE_LEN) {
        	$tempPage = mb_substr($bookText, 0, PAGE_LEN);

        	$tempPageLen = mb_strlen($tempPage);

            // save remaining text
        	$remain = mb_substr($bookText, $tempPageLen - 1);

            // find text before closing tags p, br or cite
            preg_match_all('#.*(<\/p>|<\/cite>|<br\/>)?#', $remain, $matches);
            $additional = $matches[0][0];
        	$tempPage .= $additional;

            $additionalLen = mb_strlen($additional);
        	// remove excluded page from the book text
        	$bookText = mb_substr($bookText, $tempPageLen + $additionalLen - 1);
            
            // save the page to DB
            $stmt->bindParam(':page', $page, PDO::PARAM_INT);
            $stmt->bindParam(':content', $tempPage, PDO::PARAM_STR);
            $stmt->execute();
            
            $page++;
        }
        
        // save the number of pages to DB
        $stmt = $this->pdo->prepare( 'UPDATE `books` SET `pagesCount` = :num WHERE `BookID` = :id');
        $stmt->bindParam(':num', $page, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->bookId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
