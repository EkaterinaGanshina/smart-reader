app.controller('FavoritesController', ['$scope', 'books', 'appUtils', function ($scope, books, appUtils) {
  $scope.alerter = Object.assign({}, appUtils.alert);

  books
    .getAllBooks(true)
    .then(function (response) {
      $scope.books = response.data;
    })
    .catch(function (error) {
      $scope.alerter.showAlert('error', error.data.message);
    });
}]);
