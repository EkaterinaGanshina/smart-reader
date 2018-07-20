<?php

class FB2parser
{
    /**
     * Approximate count of symbols per one page (including XML tags)
     */
    private const PAGE_LENGTH = 7000;

    /**
     * Function parses uploaded FB2, saves it to string variable and then saves splitted pages to DB
     *
     * @param int Book ID
     * @param string $pathToFile Path to the fb2 file on server
     * @param PDO $pdo DB driver
     *
     * @return bool|string True, if the book was parsed and saved successfully
     */
    public static function parse($id, $pathToFile, $pdo)
    {
        $bookText = '';
        $reader = new XMLReader();
        $reader->open($pathToFile); // path to file to parse

        // book parser
        while ($reader->read()) {
            // find the first <section> document
            if (($reader->nodeType == XMLReader::ELEMENT) and ($reader->localName == 'section')) {
                $content = $reader->readInnerXml();

                // remove xmlns attributes, links, images
                $content = preg_replace(array(
                    '#\sxmlns=[\'\"].*?[\'\"]#',
                    '#\sxmlns:l=[\'\"].*?[\'\"]#',
                    '#<a\s.*?>#', '#<\/a>#',
                    '#<image\s.*?>#'
                ), "", $content);

                // edit XML tags
                $content = str_replace(array(
                    '<empty-line', 'emphasis>', '<v>', '</v>', 'poem>',
                    '<stanza>', '</stanza>', '<title>', '</title>', '<section>', '</section'
                ), array(
                    '<br', 'em>', '<p>', '</p>', 'cite>',
                    '', '', '<h3 class="chapter-title">', '</h3>', '', ''
                ), $content);

                // save the result
                $bookText .= $content;
            }
        }

        return self::saveParsedBook($id, $bookText, $pdo);
    }

    private static function saveParsedBook($id, $bookText, $pdo) {
        // prepare SQL query to add pages
        $stmt = $pdo->prepare('INSERT into `pages` 
                (`bookId`, `pageNumber`, `content`) VALUES 
                (:book, :page, :content)');
        $stmt->bindParam(':book', $id, PDO::PARAM_INT);

        // cut the book text into pages
        $page = 0;
        while (mb_strlen(trim($bookText)) > self::PAGE_LENGTH) {
            $page += 1;
            $tempPage = mb_substr($bookText, 0, self::PAGE_LENGTH);

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
        }

        // save the number of pages to DB
        $stmt = $pdo->prepare('UPDATE `books` SET `pagesCount` = :num WHERE `BookID` = :id');
        $stmt->bindParam(':num', $page, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
}
