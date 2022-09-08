<?php

return [
    'error_messages' => [
        'already_in_progress' => 'An API request with the same Idempotency-Key is already in progress.',
        'mismatch' => 'There was a mismatch between this request\'s parameters and the '.
                      'parameters of a previously stored request with the same '.
                      'Idempotency-Key.',
    ],
];
