(function () {
  try {
    // initWidget is called when a SoundCloud iframe is found on the page
    var initWidget = function (w) {
      var currentSound, act, pos, q1, q2, q3, go, lab;
      var cat = 'SoundCloud';
      var widget = SC.Widget(w);

      // Events.READY is dispatched when the widget has been loaded
      widget.bind(SC.Widget.Events.READY, function () {

        // Get the title of the currently playing sound
        widget.getCurrentSound(function (cs) {
          lab = cs['title'];
        });

        // Fire a dataLayer event when Events.PLAY is dispatched
        // widget.bind(SC.Widget.Events.PLAY, function () {
        //   act = 'Play';
        //   sendDl(cat, act, lab);
        //   console.log(act);
        // });
        var countPlay = 0;
      widget.bind(SC.Widget.Events.PLAY, function () {
          act = 'Play';
        if (countPlay == 0){
            sendDl(cat, act, lab);
            console.log(act);
            countPlay += 1;
          }
      });
      widget.bind(SC.Widget.Events.CLICK_DOWNLOAD, function () {
        down = "CLICK_DOWNLOAD";
        sendDl(cat, lab, down);
        console.log(down);
      });
      widget.bind(SC.Widget.Events.OPEN_SHARE_PANEL, function () {
        share = "OPEN_SHARE_PANEL";
        sendDl(cat, lab, share);
        console.log(share);
      });


        // Fire a dataLayer event when Events.PAUSE is dispatched
        // The only exception is when the sound ends, and the auto-pause is not reported
        widget.bind(SC.Widget.Events.PAUSE, function (obj) {
          pos = Math.round(obj['relativePosition'] * 100);
          if (pos !== 100) {
            act = 'Pause';
            sendDl(cat, act, lab);
          }
        });

        // As the play progresses, send events at 25%, 50% and 75%
        widget.bind(SC.Widget.Events.PLAY_PROGRESS, function (obj) {
          go = false;
          pos = Math.round(obj['relativePosition'] * 100);
          if (pos === 25 && !q1) {
            act = '25%';
            q1 = true;
            go = true;
          }
          if (pos === 50 && !q2) {
            act = '50%';
            q2 = true;
            go = true;
          }
          if (pos === 75 && !q3) {
            act = '75%';
            q3 = true;
            go = true;
          }
          if (go) {
            sendDl(cat, act, lab);
          }
        });

        // When the sound finishes, send an event at 100%
        widget.bind(SC.Widget.Events.FINISH, function () {
          act = '100%';
          q1 = q2 = q3 = false;
          sendDl(cat, act, lab);
        });
      });
    };

    // Generic method for pushing the dataLayer event
    // Use a Custom Event Trigger with "scEvent" as the event name
    // Remember to create Data Layer Variables for eventCategory, eventAction, and eventLabel
    var sendDl = function (cat, act, lab, share, down) {
      window.dataLayer.push({
        'event': 'scEvent',
        'eventCategory': cat,
        'eventAction': act,
        'eventLabel': lab,
        'eventShare': share,
        'eventDownload': down,
      });
    };


    // For each SoundCloud iFrame, initiate the API integration
    var i, len;
    var iframes = document.querySelectorAll('iframe[src*="api.soundcloud.com"]');
    for (i = 0, len = iframes.length; i < len; i += 1) {
      initWidget(iframes[i]);
    }
  } catch (e) { console.log('Error with SoundCloud API: ' + e.message); }
})();
