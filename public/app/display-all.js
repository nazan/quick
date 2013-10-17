/*
 window.setTimeout(function() {
 window.location.reload();
 }, 1000);
 */

$(window).load(function() {
    var queuesHolder = $('#queues_holder').first();

    var templateTag = $('#queues-underscore-template').first();
    var wsep = templateTag.data('wsep'); // Websocket end point.

    var template = _.template(templateTag.html());

    var renderData = function() {
        $.ajax({
            url: '/queue',
            type: 'GET',
            dataType: 'json',
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(textStatus);
            },
            success: function(data, textStatus, jqXHR) {
                queuesHolder.html(template({queues: data}));
            }
        });
    };
    
    renderData();

    var conn = new ab.Session(
            wsep,
            function() {
                conn.subscribe('any', function(topic, data) {
                    console.log('Change detected in queue "' + topic + '"');

                    renderData();
                });

                console.log('Connection established.');
            },
            function() {            // When the connection is closed
                console.warn('WebSocket connection closed');
            },
            {
                'skipSubprotocolCheck': true
            });
});