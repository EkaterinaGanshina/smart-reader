<?php

class UploadHandler
{
    // constants
    private const FILENAME_LENGTH = 12; // length of the generated string for file name

    /**
     * Function handles upload of the new book and its cover
     *
     * @param array $data New book data
     * @param array $files Array with files to upload. Its first element must be fb2 file of the book,
     * the second file is its cover
     * @param PDO $pdo DB driver
     *
     * @return array Array with results of uploading the book, its cover (if present) and the book id
     *
     * @throws ValidateException
     */
    public static function uploadBook($data, $files, $pdo)
    {
        include_once 'FB2parser.class.php';

        $newBook = $data;
        $book = new Book($newBook, $pdo);
        $result = [
            'book' => false,
            'cover' => null
        ];

        // first, we must get ID of the new book by saving it to DB
        $saveResult = $book->saveBook();
        if ($saveResult['status']) {
            $newBook['bookId'] = $book->getId();
        } else {
            return $saveResult;
        }

        // if fb2 file is loaded successfully, parse it
        if (is_uploaded_file($files['tmp_name'][0])) {
            $result['book'] = FB2parser::parse($newBook['bookId'], $files['tmp_name'][0], $pdo);
        }

        // if book and its cover are loaded successfully, move loaded cover to the special folder
        if ($result['book'] && is_uploaded_file($files['tmp_name'][1])) {
            $path = 'covers/' . self::getFileName($newBook['bookId'], $files['name'][1]);
            $destination = "{$_SERVER['DOCUMENT_ROOT']}/{$path}";
            $moveResult = move_uploaded_file($files['tmp_name'][1], $destination);

            // and update DB
            $result['cover'] = $moveResult ? $book->addBookCover($path) : false;
        }

        if ($result['book'] && ($result['cover'] || $result['cover'] == null)) {
            return [
                'status' => true,
                'message' => 'Книга успешно сохранена',
                'id' => $newBook['bookId']
            ];
        }

        return [
            'status' => false,
            'message' => 'При сохранении книги произошла ошибка'
        ];
    }

    /**
     * Function generates string which is safe to use for file name
     *
     * @param int|string $id Uploaded book ID
     * @param string $name Old name of the uploaded file
     *
     * @return string Generated string
     */
    private static function getFileName($id, $name) {
        $exploded = explode('.', $name);
        $extension = $exploded[count($exploded) - 1];
        $random = substr(md5(rand()), 0, self::FILENAME_LENGTH);

        return "{$id}-{$random}.{$extension}";
    }

    /**
     * Function calls the editing method for the book and also checks if new cover was added
     *
     * @param array $data Edited book data
     * @param array $file New cover for the edited book
     * @param PDO $pdo DB driver
     *
     * @return array Results of saving either the cover and the book info
     *
     * @throws ValidateException
     */
    public static function prepareEditedBook($data, $file, $pdo)
    {
        $book = new Book($data, $pdo);
        $editResult = $book->saveBook();

        if (!$editResult['status']) {
            return [
                'status' => false,
                'message' => $editResult['message']
            ];
        }

        if ($editResult['status'] && is_uploaded_file($file['tmp_name'])) {
            $status = false;
            $oldCover = $_SERVER['DOCUMENT_ROOT'] . '/' . $data['cover'];

            // first, we need to remove old cover, if it is not the default placeholder
            if (file_exists($oldCover) && strpos($oldCover, 'placeholder.jpg') == false) {
                unlink($oldCover);
            }

            // then save the new one
            $path = 'covers/' . $data['bookId'] . '-' . $file['name'];
            $destination = "{$_SERVER['DOCUMENT_ROOT']}/{$path}";
            $moveResult = move_uploaded_file($file['tmp_name'], $destination);

            // update DB
            if ($moveResult) {
                $coverResult = $book->addBookCover($path);
                $status = $coverResult['status'];
            }

            return [
                'status' => $status,
                'message' => $status ? 'Книга успешно сохранена' : 'При сохранении обложки произошла ошибка'
            ];
        }

        return [
            'status' => true,
            'message' => 'Книга успешно сохранена'
        ];
    }
}
