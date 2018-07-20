app.constant('appConstants', {
  ajaxUrl: 'actions.php',
});

app.constant('appUtils', {
  serialize(object) {
    return Object.keys(object).map(key => key + '=' + encodeURIComponent(object[key])).join('&')
  },

  alert: {
    success: '',
    error: '',

    showAlert(type, message) {
      this[type] = message;
    },

    clearAlert() {
      this.success = '';
      this.error = ''
    }
  }
});