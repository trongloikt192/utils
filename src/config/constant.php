<?php
// Post status
const POST_STATUS_INIT      = 'init';
const POST_STATUS_UPDATE    = 'update';
const POST_STATUS_PENDING   = 'pending';
const POST_STATUS_SYNCING   = 'syncing';
const POST_STATUS_SYNC_ERR  = 'sync-err';
const POST_STATUS_PUBLISHED = 'published';
const POST_STATUS_DRAFT     = 'draft';

// Job status
const JOB_STATUS_INITIAL    = 0;
const JOB_STATUS_PENDING    = 1;
const JOB_STATUS_PROCESSING = 2;
const JOB_STATUS_DONE       = 3;
const JOB_STATUS_ERROR      = 4;

// Watermark size for image width sm & md
const WATERMARK_SM_WIDTH = 400;         //pixel
const WATERMARK_MD_WIDTH = 800;         //pixel

// Rclone type
const RCLONE_TYPE_GDRIVE   = 'drive';
const RCLONE_TYPE_ONEDRIVE = 'onedrive';
const RCLONE_TYPE_1FICHIER = 'fichier';
const RCLONE_TYPE_NITROFLARE = 'nitroflare';
const RCLONE_TYPE_RAPIDGATOR = 'rapidgator';

/*===========================================================================*/
/*                                   CACHE                                   */
/*===========================================================================*/
// check api valid, defend request from other site
const X_API_KEY_PREFIX = 'XApiKey:';    // tomorrow

// Environment .env name
const ENVIRONMENT_LOCAL = 'local';
const ENVIRONMENT_DEV   = 'development';
const ENVIRONMENT_PROD  = 'production';

