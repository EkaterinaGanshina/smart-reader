app.factory('books', ['$http', 'appConstants', 'appUtils', 'Upload', function ($http, appConstants, appUtils, Upload) {
  // params for ajax requests
  var getConfig = function (action, params) {
    var config = {
      params: {
        class: 'book',
        action: action
      }
    };

    if (typeof params !== 'undefined') {
      Object.assign(config.params, params);
    }

    return config
  };

  return {
    // get all books
    getAllBooks(needFav) {
      var fav = needFav || false;
      return $http.get(appConstants.ajaxUrl, getConfig('getAll', {needFav: fav}));
    },

    // get single book info
    getBook(id) {
      return $http.get(appConstants.ajaxUrl, getConfig('get', {id: id}));
    },

    // get single page of the book
    getPage(bookId, page) {
      return $http.get(appConstants.ajaxUrl, getConfig('getPage', {id: bookId, page: page}));
    },

    // upload new book using ng-file-upload
    uploadBook(newBook, files) {
      // return promise
      return Upload.upload({
        url: appConstants.ajaxUrl,
        method: 'POST',
        sendFieldsAs: 'form',
        file: files,
        data: {
          class: 'book',
          action: 'upload',
          data: newBook
        }
      })
    },

    // save edited book info
    editBook(editedBook, cover) {
      return Upload.upload({
        url: appConstants.ajaxUrl,
        method: 'POST',
        sendFieldsAs: 'form',
        file: cover,
        data: {
          class: 'book',
          action: 'edit',
          data: editedBook
        }
      })
    },

    // delete selected book
    deleteBook(book) {
      return $http.post(appConstants.ajaxUrl, appUtils.serialize(book), getConfig('delete'));
    },

    // add/remove the book to/from favorites
    toggleFavorite(book) {
      return $http.post(appConstants.ajaxUrl, appUtils.serialize(book), getConfig('fav'));
    }
  }
}]);