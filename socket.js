var app = require('express')();
var server = require('http').Server(app);
var io = require('socket.io')(server);

server.listen(8443, function () {
    console.log('HTTP Socket Server Starter. Listening: 8443');
});

io.on('connection', (socket) => {
    socket.on('disconnect', function () {
        console.log('user disconnected');
    });

    socket.on('message', (message) => {
        console.log("Message Received: " + message);
        io.emit('message', message);
    });
});