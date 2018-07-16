var app = angular.module('SmartReaderApp', ['ngRoute', 'ngFileUpload', 'ui.bootstrap'])
  .config(function($httpProvider){
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
  });

app.config(function ($routeProvider) {
  $routeProvider
    .when('/books', {
      controller: 'BookshelfController',
      templateUrl: '/src/books/bookshelf/bookshelf.html'
    })
    .when('/books/upload', {
      controller: 'UploadBookController',
      templateUrl: '/src/books/upload/uploadBook.html'
    })
    .when('/books/:bookId', {
      controller: 'BookController',
      templateUrl: '/src/books/book/book.html'
    })
    .when('/books/:bookId/pages/:pageId', {
      controller: 'PageController',
      templateUrl: '/src/books/pages/page.html'
    })
    .when('/books/:bookId/edit', {
      controller: 'EditBookController',
      templateUrl: '/src/books/edit/editBook.html'
    })
    .when('/authors', {
      controller: 'AuthorsController',
      templateUrl: '/src/authors/authors.html'
    })
    .when('/favorites', {
      controller: 'FavoritesController',
      templateUrl: '/src/books/favorites/favorites.html'
    })
    .otherwise({
      redirectTo: '/books'
    });
});
