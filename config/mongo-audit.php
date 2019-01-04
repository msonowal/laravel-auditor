<?php

return [

    /*
     * If set to false, no activities will be saved to the database.
     */
    'enabled' => env('AUDIT_ENABLED', true),

    /*
     * By default all the activities will be processed via queue
     * If set to false, all the activities will be processed instantly.
     */
    'use_queue' => env('AUDIT_MODE', true),

    /*
     * When the clean-command is executed, all recording activities older than
     * the number of days specified here will be deleted.
     */
    'delete_records_older_than_days' => 365,

    /*
     * When the clean-command is executed, all recording activities older than
     * the number of days specified above and if its beyond the max entries limit
     * those records will be deleted and mostly on the specified log name
     */
    'max_entries' => 50000, //50000 lakhs

    /*
     * If no log name is passed to the audit() helper
     * we use this default log name.
     */
    'default_log_name' => env('AUDIT_DEFAULT_LOG_NAME', 'default'),

    /*
     * If set to true, the subject returns soft deleted models.
     */
    'subject_returns_soft_deleted_models' => true,

    /*
     * This is the name of the database connection that will be used by the migration and
     * used by the Services.
     */
    'connection_name' => env('AUDIT_CONNECTION', 'audit'),
    /*
     * This is the name of the collection that will be created by the migration and
     * used by the Activity model.
     */
    'collection_name' => env('AUDIT_COLLECTION_NAME', 'audit_logs'),
];
