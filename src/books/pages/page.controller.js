app.controller('PageController', ['$scope', 'books', '$routeParams', '$sce', '$window', 'appUtils',
  function ($scope, books, $routeParams, $sce, $window, appUtils) {
    $scope.userPage = '';
    $scope.isOkBtnDisabled = true;
    $scope.alerter = Object.assign({}, appUtils.alert);

    // use these properties to create the URLs for buttons
    $scope.currentBookId = parseInt($routeParams.bookId);
    $scope.currentPage = parseInt($routeParams.pageId);
    $scope.nextPage = $scope.currentPage + 1;
    $scope.previousPage = $scope.currentPage - 1;
    $scope.isPageNumberCorrect = false;

    books
      .getPage($routeParams.bookId, $routeParams.pageId)
      .then(function (response) {
        if (!response.data) {
          $scope.alerter.showAlert('error', 'Не удалось загрузить страницу');
        } else {
          $scope.page = response.data.result.content;
          $scope.pagesNumber = parseInt(response.data.result.pagesCount, 10);
          $scope.isPageNumberCorrect = $scope.currentPage > 0 && $scope.currentPage <= $scope.pagesNumber;
          console.log($scope.isPageNumberCorrect)
        }
      })
      .catch(function (error) {
        $scope.alerter.showAlert('error', error.data.message);
      });

    $scope.renderHtml = function (html) {
      return $sce.trustAsHtml(html);
    };

    $scope.checkPageNumber = function () {
      var page = $scope.userPage.trim();

      $scope.isOkBtnDisabled = !(page.search(/^\d{1,5}$/) > -1 && parseInt(page, 10) <= $scope.pagesNumber);
      $scope.userPage = page;
    };

    $scope.goToPage = function () {
      $window.location.href = '/#/books/' + $scope.currentBookId + '/pages/' + $scope.userPage.trim();
    }
  }]);
