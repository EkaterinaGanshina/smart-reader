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
                break;
            case 'getPage':
                $book = new Book(['bookId' => $_REQUEST['id']], $pdo);
                $page = $_REQUEST['page'];

                $result = $book->getPage($page);
                break;
            case 'upload':
                include_once 'classes/UploadHandler.class.php';

                $result = UploadHandler::uploadBook($_REQUEST['data'], $_FILES['file'], $pdo);
                break;
            case 'edit':
                include_once 'classes/UploadHandler.class.php';

                $result = UploadHandler::prepareEditedBook($_REQUEST['data'], $_FILES['file'], $pdo);
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
            case 'edit':
                $author = new Author($_REQUEST, $pdo);
                $result = $author->saveAuthor();
                break;
            case 'delete':
                $author = new Author($_REQUEST, $pdo);
                $result = $author->deleteAuthor();
                break;
            case 'get':
            default:
                $author = new Author(array(), $pdo);
                $result = $author->getAuthors();
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
