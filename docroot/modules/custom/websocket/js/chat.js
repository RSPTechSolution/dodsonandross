(function($) {
  var conn = new WebSocket('wss://3.233.171.147:3000');
  conn.onopen = function(e) {
    console.log('Connection established!');
  };

  conn.onmessage = function(e) {
    console.log(e.data);
    // $('.chat-messages').append('<p>' + e.data + '</p>');
  };

  // var $form = $('#chat-form');
  // $('body').on('submit', $form, function(e) {
  //   e.preventDefault();
  //   var textarea = $('#edit-chat-message');
  //   var message = textarea.val();
  //   conn.send(message);
  //   textarea.val('');
  // });

})(jQuery);
