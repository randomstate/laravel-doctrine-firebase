# Laravel-Doctrine Firebase

This micro-package has the sole responsibility of integrating Firebase Auth with your Laravel app.
It does not integrate directly with Firebase but instead is responsible for verifying and synchronising your
users with your local database when using Laravel-Doctrine.

You should use the front-end javascript snippet (or similar library) supplied by Google to generate JWT tokens
for your users that are then passed to Laravel as a Bearer token in the Authorization header.

See https://firebase.google.com/docs/web/setup for more details on integrating with your Javascript frontend.