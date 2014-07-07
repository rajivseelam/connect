<?php

return array(

    'route' => 'profile',

    'google' => array(

        'clients' => array(

            'default' => array(
                'client_id' => 'XXXXXXXXXXXXXXXXXXX',
                'client_secret' => 'XXXXXXXXXXXXXXXXXXX',
                'redirect_uri' => 'XXXXXXXXXXXXXXX',
            ),

        ),

        'scopes' => array(

            'default' => array(
                "https://www.googleapis.com/auth/userinfo.email",
                "https://www.googleapis.com/auth/userinfo.profile"
            ),

        ),

    ),


    'github' => array(

        'clients' => array(

            'default' => array(
                'client_id' => 'XXXXXXXXXXXXXX',
                'client_secret' => 'XXXXXXXXXXXXXXXXXXXX',
                'redirect_uri' => 'XXXXXXXXXXXXXXXXXXX',
            ),
        ),

        'scopes' => array(

            'default' => array(
                "user:email"
            ),

        ),

    ),

);