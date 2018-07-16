app.controller('EditBookController', ['$scope', '$routeParams', 'authors', 'books', '$window', 'appUtils',
  function ($scope, $routeParams, authors, books, $window, appUtils) {
    $scope.files = [];
    $scope.isPreloaderShown = false;
    $scope.alerter = Object.assign({}, appUtils.alert);

    function showErrorAlert(message) {
      $scope.alerter.showAlert('error', message);
      document.querySelector('.upload-book').scrollIntoView();

      // we want alert to hide automatically
      setTimeout(function () {
        $scope.alerter.clearAlert();
        $scope.$apply();
      }, 5000)
    }

    books
      .getBook($routeParams.bookId)
      .then(function (response) {
        $scope.book = response.data;

        authors
          .getAuthors()
          .then(function (response) {
            $scope.authors = response.data;
          })
          .catch(function (error) {
            showErrorAlert(error.data.message)
          });
      });

    $scope.save = function () {
      var cover = typeof $scope.files[0] === 'undefined' ? false : $scope.files[0];

      $scope.isPreloaderShown = true;

      books
        .editBook($scope.book, cover)
        .then(function (response) {
          $scope.isPreloaderShown = false;

          if (response.data.status) {
            $window.location.href = '/#/books/' + $routeParams.bookId;
          } else {
            showErrorAlert('При сохранении данных произошла ошибка')
          }
        })
        .catch(function (error) {
          $scope.isPreloaderShown = false;
          showErrorAlert(error.data.message)
        });
    }
  }]);