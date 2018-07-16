app.controller('UploadBookController', ['$scope', '$http', '$window', 'authors', 'books', 'appUtils',
  function ($scope, $http, $window, authors, books, appUtils) {
    $scope.files = [];
    $scope.isPreloaderShown = false; // if the "submit" button is disabled and preloader is shown
    $scope.alerter = Object.assign({}, appUtils.alert);

    function showErrorAlert(message) {
      $scope.alerter.showAlert('error', message);

      // we want alert to hide automatically
      setTimeout(function () {
        $scope.alerter.clearAlert();
        $scope.$apply();
      }, 5000)
    }

    authors.getAuthors()
      .then(function (response) {
        $scope.authors = response.data;
      })
      .catch(function () {
        showErrorAlert('Ошибка при получении списка авторов. Попробуйте обновить страницу')
      });

    $scope.submit = function () {
      if (!$scope.uploadBook.$valid || !$scope.files[0]) {
        return;
      }

      // make the "submit" button disabled and show the preloader
      $scope.isPreloaderShown = true;

      books
        .uploadBook($scope.book, $scope.files)
        .then(function (response) {
          $scope.isPreloaderShown = false;

          if (response.data.status) {
            $window.location.href = '/#/books/' + response.data.id;
          } else {
            showErrorAlert('При сохранении книги произошла ошибка')
          }
        })
        .catch(function (error) {
          $scope.isPreloaderShown = false;
          showErrorAlert(error.data.message)
        });
    };
  }]);
