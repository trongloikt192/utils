<?php
// Post status
const POST_STATUS_INIT = 'init';
const POST_STATUS_UPDATE = 'update';
const POST_STATUS_PENDING = 'pending';
const POST_STATUS_SYNCING = 'syncing';
const POST_STATUS_SYNC_ERR = 'sync-err';
const POST_STATUS_PUBLISHED = 'published';
const POST_STATUS_DRAFT = 'draft';

// Job status
const JOB_STATUS_INITIAL = 0;
const JOB_STATUS_PENDING = 1;
const JOB_STATUS_PROCESSING = 2;
const JOB_STATUS_DONE = 3;
const JOB_STATUS_ERROR = 4;

/*===========================================================================*/
/*                                   CACHE                                   */
/*===========================================================================*/
// check api valid, defend request from other site
const X_API_KEY_PREFIX = 'XApiKey:'; // tomorrow

// Environment .env name
const ENVIRONMENT_LOCAL = 'local';
const ENVIRONMENT_DEV   = 'development';
const ENVIRONMENT_PROD  = 'production';

