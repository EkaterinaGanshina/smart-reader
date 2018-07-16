<?php

header('Content-Type: application/json');
require_once 'global.php';

$class = $_REQUEST['class'];
$action = $_REQUEST['action'];
$result = null;

include_once 'classes/ValidateException.php';

if ($class == 'book') {
    include_once 'classes/Book.class.php';

    try {
        // book actions
        switch ($action) {
            case 'get':
                $book = new Book(['bookId' => $_REQUEST['id']], $pdo);
                $result = $book->getBook();

                foreach ($result as $key => &$value) {
                    $value = $value == null ? '' : $value;
                }
                unset($value);

                break;
            case 'getPage':
                $book = new Book(['bookId' => $_REQUEST['id']], $pdo);
                $page = $_REQUEST['page'];

                $result = $book->getPage($page);
                break;
            case 'upload':
                $result = uploadBook($_REQUEST['data'], $_FILES['file'], $pdo);
                break;
            case 'edit':
                // we check if there is new cover file and upload it first
                $result = prepareEditedBook($_REQUEST['data'], $_FILES['file'], $pdo);
                break;
            case 'delete':
                $book = new Book($_REQUEST, $pdo);
                $result = $book->deleteBook();
                break;
            case 'fav':
                $book = new Book($_REQUEST, $pdo);
                $result = $book->changeFav();
                break;
            case 'getAll':
            default:
                $book = new Book(array(), $pdo);
                $result = $book->getAllBooks($_REQUEST['needFav'] == 'true');
                break;
        }
    } catch (ValidateException $e) {
        http_response_code(422);
        $result = [
            'status' => false,
            'message' => $e->getMessage(),
        ];
        echo json_encode($result);
        die();
    } catch (PDOException $e) {
        http_response_code(409);

        $result = [
            'status' => false,
            'message' => 'Произошла ошибка при сохранении данных в БД',
        ];
    }

} elseif ($class == 'author') {
    include_once 'classes/Author.class.php';

    try {
        // author actions
        switch ($action) {
            case 'add':
                $author = new Author($_REQUEST, $pdo);
                $result = $author->addAuthor();
                break;
            case 'edit':
                $author = new Author($_REQUEST, $pdo);
                $result = $author->editAuthor();
                break;
            case 'delete':
                $author = new Author($_REQUEST, $pdo);
                $result = $author->deleteAuthor();
                break;
            case 'get':
            default:
                $author = new Author(array(), $pdo);
                $result = $author->getAuthors();

                foreach ($result as &$author) {
                    foreach ($author as $key => &$value) {
                        $value = $value == null ? '' : $value;
                    }
                }
                unset($author);
                unset($value);

                break;
        }
    } catch (ValidateException $e) {
        http_response_code(422);
        $result = [
            'status' => false,
            'message' => $e->getMessage(),
        ];
    } catch (PDOException $e) {
        http_response_code(409);

        $message = '';
        if (strpos($e->getMessage(), 'Integrity constraint violation') === false) {
            $message = 'Произошла ошибка при сохранении данных в БД';
        } else {
            $message = 'Удаление невозможно. К удаляемому автору привязаны книги';
        }

        $result = [
            'status' => false,
            'message' => $message,
        ];
    }
}

echo json_encode($result);

/**
 * @param array $data New book data
 * @param array $files Array with files to upload. Its first element must be fb2 file of the book,
 * the second file is its cover
 * @param PDO $pdo DB driver
 * @return array Array with results of uploading the book, its cover (if present) and the book id
 * @throws ValidateException
 */
function uploadBook($data, $files, $pdo)
{
    include_once 'classes/FB2parser.class.php';

    $newBook = $data;
    $book = new Book($newBook, $pdo);
    $result = [
        'book' => false,
        'cover' => null
    ];

    // first, we must get ID of the new book by saving it to DB
    $saveResult = $book->saveNewBook();
    if ($saveResult['status']) {
        $newBook['bookId'] = $saveResult['bookId'];
    } else {
        return $saveResult;
    }

    // if fb2 file is loaded successfully, parse it
    if (is_uploaded_file($files['tmp_name'][0])) {
        $fb = new FB2parser($pdo, $newBook['bookId']);
        $result['book'] = $fb->parse($files['tmp_name'][0]);
    }

    // if book and its cover are loaded successfully, move loaded cover to the special folder
    if ($result['book'] && is_uploaded_file($files['tmp_name'][1])) {
        $path = 'covers/' . $newBook['bookId'] . '-' . $files['name'][1];
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
    };

    return [
        'status' => false,
        'message' => 'При сохранении книги произошла ошибка'
    ];
}

/**
 * @param array $data Edited book data
 * @param array $file New cover for the edited book
 * @param PDO $pdo DB driver
 * @return array Results of saving either the cover and the book info
 * @throws ValidateException
 */
function prepareEditedBook($data, $file, $pdo) {
    $book = new Book($data, $pdo);
    $editResult = $book->editBook();

    if (!$editResult['status']) {
        return [
            'status' => false,
            'message' => 'При сохранении книги произошла ошибка'
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
    } elseif ($editResult['status']) {
        return [
            'status' => true,
            'message' => 'Книга успешно сохранена'
        ];
    }
}
