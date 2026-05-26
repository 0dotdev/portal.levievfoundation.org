<?php

return [

    'title' => 'Verify your email address',

    'heading' => 'Verify your email address',

    'actions' => [

        'resend_notification' => [
            'label' => 'Resend it',
        ],

    ],

    'messages' => [
        'notification_not_received' => 'Not received the email we sent?',
        'notification_sent' => 'We\'ve sent an email to :email containing instructions on how to verify your email address. <br/> <br/> If you don\'t see the email in your inbox, please check your junk/spam folder.',
    ],

    'notifications' => [

        'notification_resent' => [
            'title' => "We sent you an email. If you don't see it, please check your junk/spam folder.",
        ],

        'notification_resend_throttled' => [
            'title' => 'Too many resend attempts',
            'body' => 'Please try again in :seconds seconds.',
        ],

    ],

];
