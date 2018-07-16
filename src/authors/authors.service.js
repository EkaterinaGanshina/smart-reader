app.factory('authors', ['$http', 'appConstants', 'appUtils', function ($http, appConstants, appUtils) {
  var getConfig = function(action) {
    return {
      params: {
        class: 'author',
        action: action
      }
    }
  };

  return {
    getAuthors() {
      return $http.get(appConstants.ajaxUrl, getConfig('get'));
    },

    addAuthor(author) {
      return $http.post(appConstants.ajaxUrl, appUtils.serialize(author), getConfig('add'));
    },

    editAuthor(author) {
      return $http.post(appConstants.ajaxUrl, appUtils.serialize(author), getConfig('edit'));
    },

    deleteAuthor(author) {
      return $http.post(appConstants.ajaxUrl, appUtils.serialize(author), getConfig('delete'));
    }
  }
}]);
