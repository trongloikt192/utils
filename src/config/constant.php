<?php
// Post status
define('POST_STATUS_INIT'           , 'init');
define('POST_STATUS_UPDATE'         , 'update');
define('POST_STATUS_PENDING'        , 'pending');
define('POST_STATUS_SYNCING'        , 'syncing');
define('POST_STATUS_SYNC_ERR'       , 'sync-err');
define('POST_STATUS_PUBLISHED'      , 'published');
define('POST_STATUS_DRAFT'          , 'draft');

// Job status
define('JOB_STATUS_INITIAL'       , 0);
define('JOB_STATUS_PENDING'       , 1);
define('JOB_STATUS_PROCESSING'    , 2);
define('JOB_STATUS_DONE'          , 3);
define('JOB_STATUS_ERROR'         , 4);




// Cache
// check api valid, defend request from other site
define('X_API_KEY_PREFIX', 'XApiKey:'); // tomorrow



