app.controller('AuthorsController', ['$scope', 'authors', 'appUtils', function ($scope, authors, appUtils) {
  $scope.selected = {};
  $scope.isCollapsed = true;
  $scope.authorIndex = 0;
  $scope.alerter = Object.assign({}, appUtils.alert);

  function showErrorAlert(message) {
    $scope.alerter.showAlert('error', message);

    // we want alert to hide automatically
    setTimeout(function () {
      $scope.alerter.clearAlert();
      $scope.$apply();
    }, 4000)
  }

  authors
    .getAuthors()
    .then(function (response) {
      $scope.authors = response.data;
    })
    .catch(function (error) {
      showErrorAlert(error.data.message)
    });

  // get template title for each row (each author), 'display' or 'edit'
  $scope.getTemplate = function(author) {
    return author.authorId === $scope.selected.authorId ? 'edit' : 'display';
  };

  $scope.addAuthor = function(newAuthor) {
    authors
      .addAuthor(newAuthor)
      .then(function(response) {
        if (response.data.status) {
          $scope.newAuthor = {};
          $scope.isCollapsed = true;

          newAuthor.authorId = response.data.authorId;
          $scope.authors.push(newAuthor);
        } else {
          showErrorAlert('При сохранении данных произошла ошибка')
        }
      })
      .catch(function (error) {
        showErrorAlert(error.data.message)
      });
  };

  $scope.editAuthor = function ($index, author) {
    $scope.selected = angular.copy(author);
    $scope.authorIndex = $index;
  };

  $scope.saveAuthor = function (index) {
    authors
      .editAuthor($scope.selected)
      .then(function (response) {
        $scope.reset();

        if (response.data.status) {
          $scope.authors[index] = angular.copy($scope.selected);
        } else {
          showErrorAlert('При сохранении данных произошла ошибка')
        }
      })
      .catch(function (error) {
        showErrorAlert(error.data.message)
      });
  };

  $scope.reset = function () {
    $scope.selected = {};
  };

  $scope.deleteAuthor = function (authorIndex) {
    authors
      .deleteAuthor($scope.authors[authorIndex])
      .then(function (response) {
        if (response.data.status) {
          $scope.authors.splice(authorIndex, 1);
        } else {
          showErrorAlert('При удалении автора произошла ошибка')
        }
      })
      .catch(function (error) {
        showErrorAlert(error.data.message)
      });
  }
}]);
