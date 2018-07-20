<?php

include_once 'ValidateException.php';

class Author {
    // private class fields
    private $authorId;
    private $name;
    private $lastName;
    private $birthYear;
    private $deceaseYear;
    private $description;

    // DB driver
    private $pdo;

    /**
     * Author constructor.
     * @param array $data Information about the author
     * @param PDO $pdo DB driver
     */
    function __construct($data, $pdo)
    {
        $this->authorId = $data['authorId'] ?? "";
        $this->name = $data['name'] ?? "";
        $this->lastName = $data['lastName'] ?? "";
        $this->birthYear = $data['birthYear'] ?? "";
        $this->deceaseYear = $data['deceaseYear'] ?? "";
        $this->description = $data['description'] ?? "";
        $this->pdo = $pdo;
    }

    /**
     * Get data of all the authors from DB
     * @return array|string Array of authors or error string
     */
    public function getAuthors()
    {
        $stmt = $this->pdo->prepare('SELECT 
            `authorId`, `name`, `lastName`, `birthYear`, `deceaseYear`, `description`
            FROM authors');
        $stmt->execute();
        $result = $stmt->fetchAll();

        foreach ($result as &$author) {
            foreach ($author as $key => &$value) {
                $value = $value ?? '';
            }
            unset($value);
        }
        unset($author);

        return $result;
    }

    /**
     * @return array|string
     * @throws ValidateException
     */
    public function saveAuthor()
    {
        // we need to validate author data
        $this->validateAuthor();

        $isNewAuthor = !is_numeric($this->authorId);
        if ($isNewAuthor) {
            $query = 'INSERT INTO `authors` 
                (`name`, `lastName`, `birthYear`, `deceaseYear`, `description`) VALUES 
                (:name, :lastName, :birth, :decease, :description)';
        } else {
            $query = 'UPDATE `authors` SET 
                `name` = :name, 
                `lastName` = :lastName, 
                `birthYear` = :birth,
                `deceaseYear` = :decease,
                `description` = :description
            WHERE `authorId` = :id';
        }

        $stmt = $this->pdo->prepare($query);

        if (!$isNewAuthor) {
            $stmt->bindParam(':id', $this->authorId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':name', trim($this->name)? trim($this->name) : null, PDO::PARAM_STR);
        $stmt->bindParam(':lastName', trim($this->lastName), PDO::PARAM_STR);
        $stmt->bindParam(':birth', $this->birthYear, PDO::PARAM_INT);
        $stmt->bindValue(':decease', $this->deceaseYear ? $this->deceaseYear : null, PDO::PARAM_INT);
        $stmt->bindValue(':description', trim($this->description) ? trim($this->description) : null, PDO::PARAM_INT);
        $result = $stmt->execute();

        if ($isNewAuthor) {
            $this->authorId = $this->pdo->lastInsertId();
        }

        return [
            'status' => $result,
            'message' => $result ? 'Информация успешно сохранена' : 'При сохранении автора произошла ошибка',
            'authorId' => $this->authorId
        ];
    }

    public function deleteAuthor()
    {
        $stmt = $this->pdo->prepare( 'DELETE FROM `authors` 
            WHERE `authorId` = :id' );
        $stmt->bindParam(':id', $this->authorId, PDO::PARAM_INT);
        $status = $stmt->execute();

        return [
            'status' => $status,
            'message' => $status ? 'Автор успешно удален' : 'При удалении автора произошла ошибка'
        ];
    }

    /**
     *
     * @throws \ValidateException
     */
    private function validateAuthor()
    {
        $errors = [];

        // check the author's name
        if ($this->name) {
            $isStrlen = mb_strlen($this->name) <= 30;
            preg_match('/^([а-яА-ЯЁёa-zA-Z0-9 ]+)$/u', $this->name, $matches);
            $isCorrect = $matches[0] == $this->name;
            if (!($isCorrect && $isStrlen)) {
                $errors['name'] = 'Некорректное имя';
            }
        }

        // check the author's last name
        if ($this->lastName) {
            $isStrlen = mb_strlen($this->lastName) <= 50;
            preg_match('/^([а-яА-ЯЁёa-zA-Z0-9 ]+)$/u', $this->lastName, $matches);
            $isCorrect = $matches[0] == $this->lastName;
            if (!($isCorrect && $isStrlen)) {
                $errors['lastName'] = 'Некорректная фамилия';
            }
        } else {
            $errors['lastName'] = 'Фамилия является обязательным полем';
        }

        // check the author's birth year
        if ($this->birthYear) {
            $isCorrect = 99 < $this->birthYear && $this->birthYear < date('Y');

            if (!$isCorrect) {
                $errors['birthYear'] = 'Некорректный год рождения';
            } elseif ($this->deceaseYear && $this->birthYear == $this->deceaseYear) {
                $errors['birthYear'] = 'Год рождения не может быть равен году смерти';
            } elseif ($this->deceaseYear && $this->birthYear > $this->deceaseYear) {
                $errors['birthYear'] = 'Год рождения не может быть больше года смерти';
            }
        } else {
            $errors['birthYear'] = 'Год рождения является обязательным полем';
        }

        // check the author's decease year
        if ($this->deceaseYear) {
            $isCorrect = 99 < $this->deceaseYear && $this->deceaseYear <= date('Y');

            if (!$isCorrect) {
                $errors['deceaseYear'] = 'Некорректный год смерти';
            }
        }

        // check description of the author
        if ($this->description) {
            $isStrlen = mb_strlen($this->description) <= 250;
            if (!$isStrlen) {
                $errors['description'] = 'Некорректное описание';
            }
        }

        if (count($errors)) {
            $message = implode('; ', $errors);
            throw new ValidateException($message);
        }
    }
}
