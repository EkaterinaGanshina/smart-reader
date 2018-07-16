app.controller('BookController', ['$scope', 'books', '$routeParams', '$modal', '$window', 'appUtils',
  function ($scope, books, $routeParams, $modal, $window, appUtils) {
    $scope.alerter = Object.assign({}, appUtils.alert);
    var modalInstance; // modal window

    function showErrorAlert(message) {
      $scope.alerter.showAlert('error', message);

      // we want alert to hide automatically
      setTimeout(function () {
        $scope.alerter.clearAlert();
        $scope.$apply();
      }, 4000)
    }

    // get book info
    books
      .getBook($routeParams.bookId)
      .then(function (response) {
        $scope.book = response.data;
      })
      .catch(function (error) {
        showErrorAlert(error.data.message)
      });

    // method opens the modal window
    $scope.open = function () {
      modalInstance = $modal.open({
        animation: false,
        templateUrl: 'delete-book',
        windowClass: 'app-modal-window',
        scope: $scope,
        resolve: {
          book: function () {
            return $scope.book;
          }
        }
      });

      modalInstance.result.then(function (selectedItem) {
        $scope.selected = selectedItem;
      });
    };

    // if user hits 'OK' button in the modal window
    $scope.deleteBook = function () {
      books.deleteBook($scope.book)
        .then(function (response) {
          modalInstance.close();

          if (response.data.status) {
            $window.location.href = '/#/books';
          } else {
            showErrorAlert('При удалении книги произошла ошибка')
          }
        })
        .catch(function (error) {
          modalInstance.close();
          showErrorAlert(error.data.message)
        });
    };

    // if user hits 'Cancel' button in the modal window
    $scope.cancelModal = function () {
      modalInstance.dismiss('cancel');
    };

    // method toggles the flag if the book is in favorites
    $scope.toggleFav = function () {
      var oldValue = $scope.book.isFavorite;
      $scope.book.isFavorite = oldValue === '0' ? '1' : '0';

      books
        .toggleFavorite($scope.book)
        .then(function (response) {
          console.log(response)
        })
        .catch(function (error) {
          $scope.book.isFavorite = oldValue;
          showErrorAlert(error.data.message)
        });
    }
  }]);
