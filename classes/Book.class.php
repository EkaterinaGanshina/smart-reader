<?php

include_once 'ValidateException.php';

class Book
{
    // constants
    private const DEFAULT_COVER = 'assets/img/placeholder.jpg';

    // private class fields
    private $bookId;
    private $authorId;
    private $title;
    private $cover;
    private $annotation;
    private $publishYear;
    private $publishHouse;
    private $isFavorite;

    // DB driver
    private $pdo;
    
    /**
     * Book constructor.
     * @param array $data Book data
     * @param PDO $pdo DB driver
     */
    public function __construct($data, $pdo)
    {
        $this->bookId = $data['bookId'] ?? "";
        $this->authorId = $data['authorId'] ?? "";
        $this->title = $data['title'] ?? "";
        $this->cover = $data['cover'] ?? self::DEFAULT_COVER;
        $this->annotation = $data['annotation'] ?? "";
        $this->publishYear = $data['publishYear'] ?? "";
        $this->publishHouse = $data['publishHouse'] ?? "";
        $this->isFavorite = $data['isFavorite'] ?? "0";
        $this->pdo = $pdo;
    }

    /**
     * @return array
     * @throws ValidateException
     */
    public function saveNewBook()
    {
        // we need to validate book data
        $this->validateBook();

        $stmt = $this->pdo->prepare('INSERT INTO `books` 
                (`authorId`, `title`,`annotation`, `publishHouse`, `publishYear`, `cover`) VALUES 
                (:author, :title, :annotation, :house, :year, :cover)');

        $stmt->bindParam(':author', $this->authorId, PDO::PARAM_INT);
        $stmt->bindParam(':title', $this->title, PDO::PARAM_STR);
        $stmt->bindValue(':annotation', trim($this->annotation) ? trim($this->annotation) : null, PDO::PARAM_STR);
        $stmt->bindValue(':house', trim($this->publishHouse) ? trim($this->publishHouse) : null, PDO::PARAM_STR);
        $stmt->bindValue(':year', $this->publishYear ? $this->publishYear : null, PDO::PARAM_INT);
        $stmt->bindParam(':cover', $this->cover, PDO::PARAM_STR);
        $result = $stmt->execute();

        // save id of the last inserted row
        $this->bookId = $this->pdo->lastInsertId();

        return [
            'status' => $result,
            'message' => $result ? 'Информация успешно сохранена' : 'При сохранении книги произошла ошибка',
            'bookId' => $this->bookId
        ];
    }

    public function addBookCover($coverPath)
    {
        $this->cover = $coverPath;
        $stmt = $this->pdo->prepare( 'UPDATE `books` SET 
                    `cover` = :cover
                WHERE `bookId` = :id');
        $stmt->bindParam(':id', $this->bookId, PDO::PARAM_INT);
        $stmt->bindParam(':cover', $this->cover, PDO::PARAM_STR);
        $status = $stmt->execute();

        return [
            'status' => $status,
            'message' => $status ? 'Обложка книги успешно сохранена' : 'При сохранении обложки произошла ошибка'
        ];
    }
    
    public function getAllBooks($needFav)
    {
        $res = array();
        $andCondition = $needFav ? " AND books.isFavorite = 1" : "";
        $i = 0;

        $query = "SELECT books.bookId, books.title, books.cover, books.pagesCount, 
                  authors.authorId, authors.name, authors.lastName 
                  FROM books, authors 
                  WHERE books.authorId = authors.authorId {$andCondition}";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        while($row = $stmt->fetch()) {
            $res[$i] = $row;
            $i++;
        }

        return $res;
    }

    public function getBook()
    {
        $stmt = $this->pdo->prepare('SELECT books.bookId, books.title, books.publishHouse, books.publishYear,
                                     books.annotation, books.cover, books.pagesCount, books.isFavorite, 
                                     authors.authorId, authors.name, authors.lastName
                                     FROM books, authors WHERE books.bookId = :id AND books.authorId = authors.authorId');
        $stmt->bindParam(':id', $this->bookId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch();
    }
    
    public function getPage($pageNum = 1)
    {
        $stmt = $this->pdo->prepare('SELECT books.bookId, books.pagesCount, pages.content 
                                     FROM books, pages
                                     WHERE books.bookId = :id AND pages.bookId = :id AND pages.PageNumber = :num');
        $stmt->bindParam(':id', $this->bookId, PDO::PARAM_INT);
        $stmt->bindParam(':num', $pageNum, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_LAZY);
    }

    /**
     * @return array
     * @throws ValidateException
     */
    public function editBook()
    {
        // we need to validate book data
        $this->validateBook();

        $stmt = $this->pdo->prepare('UPDATE `books` SET 
                `authorId` = :author,
                `title` = :title, 
                `publishHouse` = :house, 
                `publishYear` = :year,
                `annotation` = :annotation
            WHERE `bookId` = :id');
        $stmt->bindParam(':id', $this->bookId, PDO::PARAM_INT);
        $stmt->bindParam(':author', $this->authorId, PDO::PARAM_INT);
        $stmt->bindParam(':title', $this->title, PDO::PARAM_STR);
        $stmt->bindValue(':annotation', trim($this->annotation)? trim($this->annotation) : null, PDO::PARAM_STR);
        $stmt->bindValue(':house', trim($this->publishHouse) ? trim($this->publishHouse) : null, PDO::PARAM_STR);
        $stmt->bindValue(':year', $this->publishYear ? $this->publishYear : null, PDO::PARAM_INT);
        $status = $stmt->execute();

        return [
            'status' => $status,
            'message' => $status ? 'Книга успешно сохранена' : 'При обновлении книги произошла ошибка'
        ];
    }

    public function deleteBook()
    {
        // delete the cover of the book
        $oldCover = $_SERVER['DOCUMENT_ROOT'] . '/' . $this->cover;
        if (file_exists($oldCover) && strpos($oldCover, 'placeholder.jpg') == false) {
            unlink($oldCover);
        }

        // delete all the pages of the book
        $stmt = $this->pdo->prepare('DELETE FROM `pages`
            WHERE `bookId` = :id');
        $stmt->bindParam(':id', $this->bookId, PDO::PARAM_INT);
        $pagesResult = $stmt->execute();

        if($pagesResult) {
            // delete the record about the book
            $stmt = $this->pdo->prepare('DELETE FROM `books`
                WHERE `bookId` = :id');
            $stmt->bindParam(':id', $this->bookId, PDO::PARAM_INT);
            $bookResult = $stmt->execute();
            $status = $pagesResult && $bookResult;

            return [
                'status' => $status,
                'message' => $status ? 'Книга успешно удалена' : 'При удалении книги произошла ошибка'
            ];
        } else {
            return [
                'status' => false,
                'message' => 'При удалении книги произошла ошибка'
            ];
        }
    }

    public function changeFav()
    {
        $stmt = $this->pdo->prepare('UPDATE `books` SET 
                `isFavorite` = :fav 
            WHERE `bookId` = :id');
        $stmt->bindParam(':id', $this->bookId, PDO::PARAM_INT);
        $stmt->bindParam(':fav', $this->isFavorite, PDO::PARAM_INT);
        $status = $stmt->execute();

        return [
            'status' => $status,
            'message' => $status ? 'Список избранного успешно обновлен' : 'При обновлении избранного произошла ошибка'
        ];
    }

    /**
     * @throws \ValidateException
     */
    private function validateBook()
    {
        $errors = [];

        // check the title of the book
        if ($this->title) {
            $isStrlen = mb_strlen($this->title) <= 100;
            preg_match('/^([а-яА-ЯЁёa-zA-Z0-9 ]+)$/u', $this->title, $matches);
            $isCorrect = $matches[0] == $this->title;
            if (!($isCorrect && $isStrlen)) {
                $errors['title'] = 'Некорректное название книги';
            }
        } else {
            $errors['title'] = 'Название книги является обязательным полем';
        }

        // check if author ID is an integer value
        if ($this->authorId) {
            if (!is_numeric($this->authorId)) {
                $errors['authorId'] = 'Автор указан некорректно';
            }
        } else {
            $errors['authorId'] = 'Необходимо указать автора книги';
        }

        // check the publish house of the book
        if ($this->publishHouse) {
            $isStrlen = mb_strlen($this->publishHouse) <= 50;
            preg_match('/^([а-яА-ЯЁёa-zA-Z0-9 \"]+)$/u', $this->publishHouse, $matches);
            $isCorrect = $matches[0] == $this->publishHouse;
            if (!($isCorrect && $isStrlen)) {
                $errors['publishHouse'] = 'Некорректное название издательства';
            }
        }

        // check the publish year of the book
        if ($this->publishYear) {
            $isCorrect = 99 < $this->publishYear && $this->publishYear < date('Y');
            if (!($isCorrect && $isStrlen)) {
                $errors['publishYear'] = 'Некорректный год издания книги';
            }
        }

        if (count($errors)) {
            $message = implode('; ', $errors);
            throw new ValidateException($message);
        }
    }
}
