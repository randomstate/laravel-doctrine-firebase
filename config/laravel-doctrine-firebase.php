<?php

return [

    /*
     |------------------------------------------------------------
     | Aliases
     |------------------------------------------------------------
     |
     | Define the aliases that should be used when registering
     | auth guards and user providers within Laravel.
     |
     */
    'aliases' => [
        'user-provider' => 'firebase',
        'auth-guard' => 'firebase-token',
    ],

    /*
     |------------------------------------------------------------
     | Project Configuration
     |------------------------------------------------------------
     |
     | Configuration of any relevant details to a project
     | including ID and credentials.
     |
     */
    'project_id' => env("FIREBASE_PROJECT_ID", null),

];