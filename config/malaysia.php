<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Malaysia States
    |--------------------------------------------------------------------------
    |
    | Canonical list of Malaysian states / federal territories sourced from
    | https://github.com/farhan-syah/Malaysia-Cities-JSON (states.json).
    | Frontend copy lives in `resources/js/data/malaysia-states.json` with
    | full id/lat/long metadata; this config keeps only the names used for
    | backend validation so both layers stay in sync.
    |
    */

    'source' => 'https://github.com/farhan-syah/Malaysia-Cities-JSON/tree/master',

    'states' => [
        'Pulau Pinang',
        'Kedah',
        'Perlis',
        'Sabah',
        'Johor',
        'Sarawak',
        'Perak',
        'Pahang',
        'Selangor',
        'Terengganu',
        'Kelantan',
        'Federal Territory of Kuala Lumpur',
        'Malacca',
        'Federal Territory of Labuan',
        'Federal Territory of Putrajaya',
        'Negeri Sembilan',
    ],

];
