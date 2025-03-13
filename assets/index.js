// index.js
const express = require('express');
const session = require('express-session');
const app = express();

// Middleware for parsing JSON bodies
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Set up session middleware
app.use(session({
    secret: 'A1234567', 
    resave: false,
    saveUninitialized: true,
    cookie: { secure: false } 
}));

// Example route
app.get('/', (req, res) => {
    if (req.session.visits) {
        req.session.visits += 1;
    } else {
        req.session.visits = 1;
    }
    res.send(`Number of visits: ${req.session.visits}`);
});

// Start the server
app.listen(3000, () => {
    console.log('Server is running on http://localhost:3000');
});
